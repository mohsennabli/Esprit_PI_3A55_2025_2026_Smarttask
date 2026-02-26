<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
class Inscription
{

    public const STATUT_EN_COURS  = 'en_cours';
    public const STATUT_COMPLETEE = 'completee';
    public const STATUT_ABANDONNEE = 'abandonnee';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: [self::STATUT_EN_COURS, self::STATUT_COMPLETEE, self::STATUT_ABANDONNEE],
        message: 'Statut invalide.'
    )]
    private ?string $statut = self::STATUT_EN_COURS;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La progression est obligatoire.')]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'La progression doit être comprise entre {{ min }} et {{ max }} %.'
    )]
    private ?int $progression = 0;

    #[ORM\Column]
    private bool $certificat = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'iduser', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: 'La formation est obligatoire.')]
    private ?Formation $formation = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
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

    public function getProgression(): ?int
    {
        return $this->progression;
    }

    public function setProgression(int $progression): static
    {
        $this->progression = $progression;
        return $this;
    }

    public function isCertificat(): bool
    {
        return $this->certificat;
    }

    public function setCertificat(bool $certificat): static
    {
        $this->certificat = $certificat;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }
}
