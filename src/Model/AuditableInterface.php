<?php

namespace Codyas\Audit\Model;

interface AuditableInterface
{
    public function getId() : ?int;
}