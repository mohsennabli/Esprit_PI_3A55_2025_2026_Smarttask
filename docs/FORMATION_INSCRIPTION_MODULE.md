# Module Formations & Inscriptions – Fonctionnalités avancées

Ce document décrit les trois blocs fonctionnels ajoutés au module Formations/Inscriptions : gestion automatique des statuts, rappels par email et tableau de bord analytique.

---

## 1. Gestion automatique des statuts

### Comportement

- **Formations passées** : les formations dont la date de fin est dépassée sont automatiquement marquées comme **terminées** (`statut = terminee`).
- **Absences** : les inscriptions encore en **en_cours** pour une formation déjà terminée sont marquées **absentes** (`statut = absent`).
- **Capacité** : si une formation a une **capacité** renseignée et que le nombre d’inscriptions l’atteint, les **nouvelles inscriptions sont refusées** (message d’erreur au moment de la création). Aucun champ “inscriptions closes” n’est stocké : la règle est calculée à la volée.

### Commande

```bash
php bin/console app:formation:status
```

À exécuter **une fois par jour** (cron). Exemple :

```cron
0 1 * * * cd /chemin/vers/projet && php bin/console app:formation:status
```

---

## 2. Rappels (Smart Reminder System)

### Règles métier

- **3 jours avant** : envoi d’un email à tous les inscrits dont la formation commence dans 3 jours.
- **24 h avant** : envoi d’un email à tous les inscrits dont la formation commence le lendemain.
- **Suivi absent** : après qu’une inscription a été marquée **absente** (par la commande de statut), envoi d’un email de suivi **une seule fois** (traçage via `absentFollowUpSentAt`).

### Commande

```bash
php bin/console app:formation:reminders
```

À exécuter **une fois par jour**. Pour que les absents reçoivent le suivi après avoir été marqués absents, il est conseillé d’exécuter d’abord la commande de statut, puis celle des rappels. Exemple cron :

```cron
0 1 * * * cd /chemin/vers/projet && php bin/console app:formation:status
5 1 * * * cd /chemin/vers/projet && php bin/console app:formation:reminders
```

### Services et templates

- **FormationReminderService** : détermine les inscriptions concernées (3 jours, 24 h, absents sans suivi) et appelle le mailer.
- **FormationInscriptionMailer** : envoi effectif des emails (méthodes `sendReminder3DaysBefore`, `sendReminder24hBefore`, `sendAbsentFollowUp`).
- Templates :
  - `email/formation_reminder_3days.html.twig`
  - `email/formation_reminder_24h.html.twig`
  - `email/formation_absent_followup.html.twig`

---

## 3. Tableau de bord (analytics)

### Accès

- URL : **/formation/dashboard**
- Réservé aux utilisateurs avec le rôle **ROLE_MANAGER**.

### Indicateurs affichés

- **Total formations** : nombre total de formations.
- **Total inscriptions** : nombre total d’inscriptions.
- **Taux de complétion** : part (en %) des inscriptions avec le statut **complétée**.
- **Formation la plus demandée** : formation ayant le plus d’inscriptions (avec lien vers la fiche).
- **Formations à venir (7 jours)** : formations actives dont la date de début est dans les 7 prochains jours.

### Technique

- **FormationAnalyticsService** : agrégations et requêtes (totaux, taux, formation la plus populaire, formations à venir).
- **FormationDashboardController** : une action `dashboard()` qui appelle le service et rend `front/formation/dashboard.html.twig`.

---

## Modèle de données (résumé des changements)

- **Formation**
  - `capacity` (nullable int) : nombre max d’inscriptions ; si atteint (et formation active, non passée), plus de nouvelles inscriptions.
  - Méthode `acceptsNewInscriptions()` : `true` si formation active, date de début non passée et (pas de capacité ou inscriptions &lt; capacité).
- **Inscription**
  - Statut **absent** : ajout de la constante `STATUT_ABSENT = 'absent'` et du choix dans le formulaire.
  - `absentFollowUpSentAt` (datetime nullable) : date d’envoi du mail de suivi absent (évite les doublons).

---

## Commandes à planifier (récap)

| Commande | Rôle | Fréquence conseillée |
|----------|------|------------------------|
| `app:formation:status` | Clôturer les formations passées, marquer les absents | 1 fois / jour |
| `app:formation:reminders` | Envoyer rappels 3 j / 24 h et suivis absents | 1 fois / jour (après status) |

---

## Fichiers principaux

- **Entités** : `Formation.php` (capacity, `acceptsNewInscriptions`), `Inscription.php` (STATUT_ABSENT, `absentFollowUpSentAt`).
- **Services** : `FormationStatusService`, `FormationReminderService`, `FormationAnalyticsService`, `FormationInscriptionMailer` (extensions).
- **Commandes** : `FormationStatusCommand`, `SendFormationRemindersCommand` (remplacée par `app:formation:reminders`).
- **Contrôleurs** : `FormationDashboardController`, mise à jour de `InscriptionController` (vérification `acceptsNewInscriptions` à la création).
- **Repositories** : `FormationRepository` (findEndedStillActive, findStartingBetween, findUpcomingThisWeek, findMostPopular), `InscriptionRepository` (findEnCoursForEndedFormations, findAbsentWithoutFollowUpSent).
- **Migration** : `Version20260305120000` (colonnes `formation.capacity`, `inscription.absent_follow_up_sent_at`).

Pour appliquer la migration si elle n’a pas encore été exécutée :

```bash
php bin/console doctrine:migrations:migrate
# ou pour exécuter uniquement la migration formations/inscriptions :
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260305120000'
```
