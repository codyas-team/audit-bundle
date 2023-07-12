<?php

namespace Codyas\Audit\Message;

class AuditableMessage
{
    public function __construct(
        protected readonly array $payload,
    ) {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}