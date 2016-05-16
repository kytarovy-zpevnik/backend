<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property User $user
 * @property bool $showName
 * @property bool $notifyWish
 * @property bool $notifyRating
 * @property bool $notifyComment
 * @property bool $notifyAction
 * @property bool $notifyAdmin
 * @property bool $notifyTaken
 *
 * UserSettings entity.
 * @author Jiří Mantlík
 */

class UserSettings extends BaseEntity
{

    use Identifier;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="App\Model\Entity\User", inversedBy="settings")
     */
    protected $user;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $showName;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyWish;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyRating;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyComment;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyAction;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyAdmin;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $notifyTaken;

    public function __construct()
    {
        $this->showName = true;
        $this->notifyWish = true;
        $this->notifyRating = true;
        $this->notifyComment = true;
        $this->notifyAction = true;
        $this->notifyAdmin = true;
        $this->notifyTaken = true;
    }

}