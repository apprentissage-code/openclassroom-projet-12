<?php

namespace App\Repository;

use App\Entity\Advice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advice>
 */
class AdviceRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Advice::class);
  }

  public function findByMonth(int $month): array
  {
    $conn = $this->getEntityManager()->getConnection();

    $sql = '
        SELECT *
        FROM advice a
        WHERE a.months::jsonb @> :monthJson
    ';

    $stmt = $conn->prepare($sql);
    $resultSet = $stmt->executeQuery([
      'monthJson' => json_encode([$month]),
    ]);

    return $resultSet->fetchAllAssociative();
  }
}
