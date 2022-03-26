<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class CommentRepository extends ServiceEntityRepository
{
    public const COMMENTS_PER_PAGE = 5;

    private const DAYS_BEFORE_REMOVAL = 1;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this
            ->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', 'published')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::COMMENTS_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query);
    }

    public function countOldRejectedOrSpam(): int
    {
        return $this
            ->getOldRejectedOrSpamQueryBuilder()
            ->select('count(1)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldRejectedOrSpam(): int
    {
        return $this
            ->getOldRejectedOrSpamQueryBuilder()
            ->delete()
            ->getQuery()
            ->execute();
    }

    private function getOldRejectedOrSpamQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.state in (:state_rejected, :state_spam)')
            ->andWhere('c.createdAt < :date')
            ->setParameter('state_rejected', 'rejected')
            ->setParameter('state_spam', 'spam')
            ->setParameter('date', new \DateTimeImmutable(-self::DAYS_BEFORE_REMOVAL . ' days'));
    }
}
