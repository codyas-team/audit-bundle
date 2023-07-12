Codyas Audit Bundle
===================

Codyas Audit is a Symfony bundle that allows tracking changes to Doctrine entities.
It is specifically designed to track changes from one or more master classes, in such a way that when 
changes are detected in any of the related (and previously configured) entities, the changes are serialized 
from the master class and the revision is created in the database.

**Features**

  * Changes are detected and handled using the Doctrine Lifecycle Subscribers.
  * Allows customized serialization of the entities, that later are stored in the revision index when the audit is triggered.
  * Compute difference between audits.
  * Revisions are stored using compression.
  * Changes are computed after the request is completed and the response was sent to the user.
 
**Requirements**
  * PHP >= 8.1
  * Symfony >= 5.0

Installation
------------
First your need to allow extra contrib to configure the bundle via Flex, run the following in your project root:

``` bash 
composer config extra.symfony.allow-contrib true
```
Then install the bundle: 

``` bash
composer require codyas/audit-bundle
```

Configuration
-------------
Most of the project config should be done by Flex. If due to any reason the configuration isn't automatically done, configure the bundle manually:

Register the bundle:

```php
# config/bundles.php

return [
    // ...
    Codyas\Audit\AuditBundle::class => ['all' => true],
    // ...
];
```
Configure the bundle:

```yaml
# config/packages/audit.yaml

audit:
  
  doctrine:
    ## Set the manager to use for the audits. If a non default manager/connection is preferred, you need to configure the
    ## Doctrine DBAL and ORM mappings settings to tell the bundle to use the desired manager/connection.
    manager: "default"
  
  serialization:
    ## You can customize the group name you want to use for serialize the revision. Defaults to "audit"  
    group_name: "audit"
```

Register the Doctrine compressed data type

```yaml
doctrine:
  dbal:
    types:
      compressed_json: Codyas\Audit\Doctrine\DBAL\Types\CompressedJsonType
  
  ## ... Adjust the ORM mapping section to your needs
  orm:
    entity_managers:
      default:
        mappings:
          AuditBundle:
            is_bundle: true
```

Concepts
--------

 * **Master entity**: The entity that will be used as serialization root when a data change is detected either in the master entity or in any of it related entities.
 * **Slave entity**: Entities that have a dependant relationship to the master entity. The relation with the master class doesn't need to be direct, as changes in entities with any degree of relationship with the master entity can also be detected and handled.  
 * **Auditable entity**: An entity that does not follows the master-slave approach and you want to keep a changelog of its data. 

Usage
-----

In a **master entity**  you must implement the interfaces ``AuditableInterface`` and ``MasterAuditableInterface`` as well as enable and define
the serialization groups on the fields you want to store in the revisions. By default, the serialization group is ``audit`` but you can
override this in bundle settings.

```php
<?php

namespace App\Entity;

use Codyas\Audit\Model\AuditableInterface;
use Codyas\Audit\Model\MasterAuditableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AcmeRepository::class)]
class Acme implements AuditableInterface, MasterAuditableInterface
{

}
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("audit")]
    private ?int $id = null;

    #[Groups("audit")]
    #[ORM\OneToMany(mappedBy: 'acmeInstance', targetEntity: AcmeDependant::class)]
    private Collection $dependants;

```

In a **slave entity** you must configure the serialization groups and implement ``AuditableInterface`` and ``SlaveAuditableInterface``. Finally, you need to implement 
``getMaster()`` and ``getMasterNamespace()`` methods, returning respectively the master entity instance and namespace.  

```php
<?php

namespace App\Entity;

use Codyas\Audit\Model\AuditableInterface;
use Codyas\Audit\Model\SlaveAuditableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AcmeDependant::class)]
class AcmeDependant implements AuditableInterface, SlaveAuditableInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("audit")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dependants')]
    private ?Acme $acmeInstance = null;
    
    public function getMaster(): ?MasterAuditableInterface
    {
        return $this->acmeInstance;
    }

    public function getMasterNamespace(): ?string
    {
        return Acme::class;
    }

```