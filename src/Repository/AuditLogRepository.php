<?php

namespace Codyas\Audit\Repository;

use Codyas\Audit\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 *
 * @method AuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuditLog[]    findAll()
 * @method AuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuditLogRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function getAudits(string $recordNamespace, int $recordId, ?array $auditIds = [], ?int $limit = null): Query
    {
        $qb = $this->createQueryBuilder('audit');

        $qb
            ->where('audit.entityType = :recordNamespace')
            ->andWhere('audit.entityId = :recordId')
            ->setParameters([
                'recordNamespace' => $recordNamespace,
                'recordId' => $recordId
            ])
            ->orderBy('audit.createdAt', 'DESC');

        if ($auditIds) {
            $qb
                ->andWhere('audit.id IN (:auditIds)')
                ->setParameter('auditIds', $auditIds);
        }

        $query = $qb->getQuery();
        if ($limit) {
            return $query->setMaxResults($limit);
        }
        return $query;
    }

    public function getLastAudit(string $recordNamespace, int $recordId): ?AuditLog
    {
        $qb = $this->createQueryBuilder('audit');

        $qb
            ->where('audit.entityType = :recordNamespace')
            ->andWhere('audit.entityId = :recordId')
            ->orderBy('audit.createdAt', 'DESC')
            ->setParameters([
                'recordNamespace' => $recordNamespace,
                'recordId' => $recordId
            ]);

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

}