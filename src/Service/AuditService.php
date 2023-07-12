<?php

namespace Codyas\Audit\Service;

use Codyas\Audit\Constants;
use Codyas\Audit\Entity\AuditLog;
use Codyas\Audit\Exception\IncomparableAuditsException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Swaggest\JsonDiff\Exception;
use Swaggest\JsonDiff\JsonDiff;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class AuditService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(
        ManagerRegistry               $manager,
        private Security              $security,
        private ParameterBagInterface $parameterBag,
        private PaginatorInterface    $paginator
    )
    {
        $config = $this->parameterBag->get('codyas_audit_config');
        $this->em = $manager->getManager($config['doctrine']['manager']);
    }

    public function log(string $entityType, string $entityId, string $action, array $eventData, Request $request): void
    {
        $user = $this->security->getUser();
        $log = new AuditLog();
        $log->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setAction($action)
            ->setEventData($eventData)
            ->setDeleted($action === Constants::AUDIT_ACTION_DELETE)
            ->setBlameUserId($user?->getId())
            ->setBlameUserName($user?->getUserIdentifier())
            ->setRequestRoute($request->get('_route'))
            ->setIpAddress($request->getClientIp())
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $this->em->persist($log);

    }

    public function getPaginatedAudits(string $recordNamespace, int $recordId, ?int $page = 0, ?int $pageSize = 10): PaginationInterface
    {
        $query = $this->em->getRepository(AuditLog::class)->getAudits($recordNamespace, $recordId);

        return $this->paginator->paginate($query, $page, $pageSize);
    }

    public function getLastAudit(string $recordNamespace, int $recordId): ?AuditLog
    {
        return $this->em->getRepository(AuditLog::class)->getLastAudit($recordNamespace, $recordId);
    }

    public function getAudit(int $auditId): ?AuditLog
    {
        return $this->em->getRepository(AuditLog::class)->find($auditId);
    }

    /**
     * @throws Exception
     */
    public function getAuditsDiffs(int $newAuditId, int $baseAuditId): ?JsonDiff
    {
        $newAudit = $this->getAudit($newAuditId);
        $baseAudit = $this->getAudit($baseAuditId);
        if (!$newAudit || !$baseAudit) {
            throw new IncomparableAuditsException("One of the given audits does not exist.");
        }

        if (!$newAudit->isSameScope($baseAudit)){
            throw new IncomparableAuditsException("The scope of given audits does not match.");
        }

        return new JsonDiff($baseAudit->getEventData(), $newAudit->getEventData());
    }

    public function getLatestChanges(string $recordNamespace, int $recordId): JsonDiff
    {
        $auditRepository = $this->em->getRepository(AuditLog::class);
        $latestAudits = $auditRepository->getAudits($recordNamespace, $recordId, null, 2)->getResult();
        if (!$latestAudits){
            return new JsonDiff([], []);
        }
        if (count($latestAudits) !== 2){
            return new JsonDiff([], $latestAudits[0]->getEventData());
        }
        return new JsonDiff($latestAudits[1]->getEventData(), $latestAudits[0]->getEventData());
    }

    public function persistBatch(): void
    {
        $this->em->flush();
    }
}