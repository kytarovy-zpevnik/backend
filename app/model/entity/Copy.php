<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 *
 * @property-read int $id
 * @property User $user
 *
 * Sharing entity.
 * @author Jiří Mantlík
 */

class Copy extends BaseEntity
{

    use Identifier;

}