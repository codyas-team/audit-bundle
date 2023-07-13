<?php

namespace Codyas\Audit\EventSubscriber;


use Codyas\Audit\Constants;
use Codyas\Audit\Model\AuditableInterface;
use Codyas\Audit\Model\MasterAuditableInterface;
use Codyas\Audit\Model\SlaveAuditableInterface;
use Codyas\Audit\Service\AuditService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AuditSubscriber implements EventSubscriberInterface
{
    public array $entityInsertBuffer = [];
    public array $entityUpdateBuffer = [];
    public array $entityRemovalBuffer = [];
    private array $config;

    private array $enqueuedItems = [];

    public function __construct(
        private readonly AuditService           $auditLogger,
        private readonly SerializerInterface    $serializer,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly EntityManagerInterface $em
    )
    {
        $this->config = $this->parameterBag->get('codyas_audit_config');
    }

    #[AsEventListener(event: KernelEvents::TERMINATE,)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        if ($_ENV['APP_ENV'] === 'dev' && str_contains($request->getRequestUri(), "_wdt")) {
            return;
        }
        if (!$this->entityInsertBuffer && !$this->entityUpdateBuffer && !$this->entityRemovalBuffer) {
            return;
        }

        $dispatchMessage = false;

        if ($this->entityInsertBuffer) {
            $this->enqueueInsertions($this->entityInsertBuffer, $dispatchMessage, $request);
        }
        if ($this->entityUpdateBuffer) {
            $this->enqueueUpdates($this->entityUpdateBuffer, $dispatchMessage, $request);
        }
        if ($this->entityRemovalBuffer) {
            $this->enqueueRemovals($this->entityRemovalBuffer, $dispatchMessage, $request);
        }
    }

    private function enqueueInsertions(array $entityInsertBuffer, bool $dispatchMessage, Request $request): void
    {
        $this->executeAudit($entityInsertBuffer, Constants::AUDIT_ACTION_INSERT, $request);
    }

    private function enqueueUpdates(array $entityUpdateBuffer, bool $dispatchMessage, Request $request): void
    {
        $this->executeAudit($entityUpdateBuffer, Constants::AUDIT_ACTION_UPDATE, $request);
    }

    private function enqueueRemovals(array $entityRemoveBuffer, bool $dispatchMessage, Request $request): void
    {
        $this->executeAudit($entityRemoveBuffer, Constants::AUDIT_ACTION_DELETE, $request);
    }

    /**
     * @param AuditableInterface[] $iterableSource
     * @param string $action
     * @param Request $request
     * @return void
     */
    private function executeAudit(array $iterableSource, string $action, Request $request): void
    {
        foreach ($iterableSource as $item) {
            $auditableItem = $item;
            $this->em->refresh($auditableItem);
            $itemClass = get_class($auditableItem);
            if ($item instanceof SlaveAuditableInterface) {
                $auditableItem = $item->getMaster();
                if (!$auditableItem?->getId()) {
                    continue;
                }
                $itemClass = $item->getMasterNamespace();
                if (in_array($action, [Constants::AUDIT_ACTION_DELETE, Constants::AUDIT_ACTION_INSERT])) {
                    $action = Constants::AUDIT_ACTION_UPDATE;
                }
            }
            $itemKey = "{$itemClass}:{$auditableItem?->getId()}";
            if (in_array($itemKey, $this->enqueuedItems)) {
                continue;
            }
            $serialization = [];
            if ($action !== Constants::AUDIT_ACTION_DELETE) {
                $serialization = $this->serializer->normalize($auditableItem, null, [
                    AbstractNormalizer::GROUPS => [$this->config['serialization']['group_name']]
                ]);
            }

            $this->auditLogger->log(
                $itemClass,
                $auditableItem->getId(),
                $action,
                $serialization,
                $request
            );

            $this->enqueuedItems[] = $itemKey;
        }
        $this->auditLogger->persistBatch();
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        $inserts = $unitOfWork->getScheduledEntityInsertions();
        $updates = $unitOfWork->getScheduledEntityUpdates();
        foreach ($inserts as $item) {
            if ($item instanceof MasterAuditableInterface) {
                array_unshift($this->entityInsertBuffer, $item);
            } else if ($item instanceof AuditableInterface) {
                $this->entityInsertBuffer[] = $item;
            }
        }
        foreach ($updates as $item) {
            if ($item instanceof AuditableInterface) {
                $this->entityUpdateBuffer[] = $item;
            }
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $auditableItem = $args->getObject();
        if ($auditableItem instanceof MasterAuditableInterface) {
            array_unshift($this->entityRemovalBuffer, clone $auditableItem);
        } else if ($auditableItem instanceof AuditableInterface) {
            $this->em->refresh($auditableItem);
            $this->entityRemovalBuffer[] = $auditableItem;
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::preRemove
        ];
    }
}