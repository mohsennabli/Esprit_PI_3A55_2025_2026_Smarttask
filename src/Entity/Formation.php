<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    public const NIVEAU_DEBUTANT      = 'debutant';
    public const NIVEAU_INTERMEDIAIRE = 'intermediaire';
    public const NIVEAU_AVANCE        = 'avance';

    public const STATUT_ACTIVE   = 'active';
    public const STATUT_TERMINEE = 'terminee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure ou égale à la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'La durée doit être un nombre positif (en heures).')]
    private ?int $duree = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le niveau est obligatoire.')]
    #[Assert\Choice(
        choices: [self::NIVEAU_DEBUTANT, self::NIVEAU_INTERMEDIAIRE, self::NIVEAU_AVANCE],
        message: 'Niveau invalide.'
    )]
    private ?string $niveau = self::NIVEAU_DEBUTANT;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'La catégorie ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $categorie = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: [self::STATUT_ACTIVE, self::STATUT_TERMINEE],
        message: 'Statut invalide.'
    )]
    private ?string $statut = self::STATUT_ACTIVE;

    /** @var Collection<int, Inscription> */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'formation', cascade: ['remove'])]
    private Collection $inscriptions;

    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /** @return Collection<int, Inscription> */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setFormation($this);
        }
        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            if ($inscription->getFormation() === $this) {
                $inscription->setFormation(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}
