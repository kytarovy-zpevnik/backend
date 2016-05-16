<?php

namespace App\Model\Service;

use App\Model\Entity\Notification;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

/**
 * Service for advanced operations with notifications.
 * @author Tomáš Markacz
 */
class NotificationService extends Object
{

	/** @var EntityManager */
    private $em;

	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * Creates notification for given user with given type.
	 * If subject supplied, notification will link to it.
     * If mentioned user supplied, notification will link to it.
	 * @param User $user
	 * @param string $type
	 * @param Song|Songbook $subject
     * @param User $mentionedUser
	 * @return \App\Model\Entity\Notification
	 */
	public function notify(User $user, $type, $subject = NULL, $mentionedUser = NULL)
	{
		$notification = new Notification;

		$notification->user = $user;
		$notification->type = $type;
        $notification->text = '';
        $notification->mentionedUser = $mentionedUser;

        $notification->created = new DateTime();
        $notification->read = false;

		if ($subject instanceof Song) {
			$notification->song = $subject;

		} elseif ($subject instanceof Songbook) {
			$notification->songbook = $subject;

		} elseif ($subject !== NULL) {
			throw new InvalidArgumentException('Invalid subject given. Song or Songbook entity expected.');
		}

		$this->em->persist($notification);

		return $notification;
	}

}
