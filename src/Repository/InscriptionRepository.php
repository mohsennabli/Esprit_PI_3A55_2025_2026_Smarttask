<?php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function searchAndSort(
        ?string $search = null,
        ?string $statut = null,
        ?int $formationId = null,
        ?string $sortBy = 'id',
        ?string $order = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.user', 'u')
            ->addSelect('u')
            ->leftJoin('i.formation', 'f')
            ->addSelect('f');

        if ($search !== null && $search !== '') {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search OR f.titre LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('i.statut = :statut')->setParameter('statut', $statut);
        }

        if ($formationId !== null) {
            $qb->andWhere('i.formation = :fid')->setParameter('fid', $formationId);
        }

        $allowedSort = ['id', 'dateInscription', 'statut', 'progression'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }

        $qb->orderBy('i.' . $sortBy, $order === 'DESC' ? 'DESC' : 'ASC');

        return $qb->getQuery()->getResult();
    }
}
