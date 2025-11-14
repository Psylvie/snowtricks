<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /*
     * Methode pour la pagination des commentaires
     */
    public function findPaginatedComments(int $page = 1, int $limit = 10, int $trickId)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.trick = :trick')
            ->setParameter('trick', $trickId)
            ->orderBy('c.createdAt', 'DESC');

        $offset = ($page - 1) * $limit;

        return $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPaginatedComments(int $trickId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.trick = :trick')
            ->setParameter('trick', $trickId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

?>
