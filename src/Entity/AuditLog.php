<?php

namespace Codyas\Audit\Entity;

use Codyas\Audit\Doctrine\DBAL\Types\CompressedJsonType;
use Codyas\Audit\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: "cd_audits_logs")]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $entityType = null;

    #[ORM\Column]
    private ?int $entityId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $blameUserId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $blameUserName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $action = null;

    #[ORM\Column]
    private ?bool $deleted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestRoute = null;

    #[ORM\Column(type: CompressedJsonType::NAME)]
    private array $eventData = [];

    #[ORM\Column(length: 255)]
    private ?string $ipAddress = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBlameUserId(): ?int
    {
        return $this->blameUserId;
    }

    public function setBlameUserId(?int $blameUserId): static
    {
        $this->blameUserId = $blameUserId;

        return $this;
    }

    public function getBlameUserName(): ?string
    {
        return $this->blameUserName;
    }

    public function setBlameUserName(?string $blameUserName): static
    {
        $this->blameUserName = $blameUserName;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getRequestRoute(): ?string
    {
        return $this->requestRoute;
    }

    public function setRequestRoute(?string $requestRoute): static
    {
        $this->requestRoute = $requestRoute;

        return $this;
    }

    public function getEventData()
    {
        return $this->eventData;
    }

    public function setEventData($eventData): static
    {
        $this->eventData = $eventData;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function isSameScope(AuditLog $challengeAudit): bool
    {
        return $this->entityId === $challengeAudit->getEntityId() && $this->entityType === $challengeAudit->getEntityType();
    }


}