<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function searchAndSort(
        ?string $search = null,
        ?string $niveau = null,
        ?string $statut = null,
        ?string $sortBy = 'id',
        ?string $order = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('f');

        if ($search !== null && $search !== '') {
            $qb->andWhere('f.titre LIKE :search OR f.categorie LIKE :search OR f.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($niveau !== null && $niveau !== '') {
            $qb->andWhere('f.niveau = :niveau')->setParameter('niveau', $niveau);
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('f.statut = :statut')->setParameter('statut', $statut);
        }

        $allowedSort = ['id', 'titre', 'dateDebut', 'dateFin', 'niveau', 'statut', 'duree'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }

        $qb->orderBy('f.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');

        return $qb->getQuery()->getResult();
    }
}
