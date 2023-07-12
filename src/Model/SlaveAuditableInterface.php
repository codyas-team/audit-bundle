<?php

namespace Codyas\Audit\Model;

interface SlaveAuditableInterface
{
    public function getMaster() : ?MasterAuditableInterface;
    public function getMasterNamespace() : ?string;
}