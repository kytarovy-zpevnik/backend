<?php

namespace App\Model\Service;

use App\Model\Entity\Notification;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

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
	 * Creates notification for given user with given text.
	 * If subject supplied, notification will link to it.
	 * @param User $user
	 * @param string $text
	 * @param Song|Songbook $subject
	 * @return \App\Model\Entity\Notification
	 */
	public function notify(User $user, $text, $subject = NULL)
	{
		$notification = new Notification;

		$notification->user = $user;
		$notification->text = $text;

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
