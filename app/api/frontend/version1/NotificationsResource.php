<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Notification;
use App\Model\Service\NotificationService;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;

/**
 * CRUD resource for notifications.
 * @author Tomáš Markacz
 */
class NotificationsResource extends FrontendResource
{

	/** @var EntityManager */
	private $em;

	/** @var NotificationService */
	private $notificationService;

	/**
	 * @param SessionService $sessionService
	 * @param EntityManager $em
	 * @param NotificationService $notificationService
	 */
    public function __construct(SessionService $sessionService, EntityManager $em, NotificationService $notificationService)
    {
		parent::__construct($sessionService);

		$this->em                  = $em;
		$this->notificationService = $notificationService;
    }

	/**
	 * Returns all notifications.
	 * If query param unread is specified, only unread notifications are returned.
	 * @return Response
	 */
	public function readAll()
	{
		$this->assumeLoggedIn();

		$user = $this->getActiveSession()->user;

		if ($this->request->getQuery('unread', FALSE)) {
			$notifications = $this->em->getDao(Notification::class)->findBy([
				'user' => $user,
				'read' => FALSE
			], ['created' => 'ASC']);

		} else {
			$notifications = $this->em->getDao(Notification::class)->findBy(['user' => $user], ['created' => 'ASC']);
		}

		$data = array_map(function (Notification $notification) {
			if ($notification->song) {
				$target = [
					'song' => [
						'id'    => $notification->song->id,
						'title' => $notification->song->title
					]
				];

			} elseif ($notification->songbook) {
				$target = [
					'songbook' => [
						'id'   => $notification->songbook->id,
						'name' => $notification->songbook->name
					]
				];

			} else {
				$target = NULL;
			}

			return [
				'id'      => $notification->id,
				'created' => self::formatDateTime($notification->created),
				'read'    => $notification->read,
				'text'    => $notification->text,
				'target'  => $target
			];
		}, $notifications);

		return Response::json($data);
	}

	/**
	 * Marks all unread notifications as read.
	 */
	public function updateAll()
	{
		$this->assumeLoggedIn();

		$user = $this->getActiveSession()->user;

		$data = $this->request->getData();

		if ($data !== ['read' => TRUE]) {
			return Response::json([
				'error' => 'NOT_IMPLEMENTED',
				'message' => 'Not implemented.'
			])->setHttpStatus(Response::HTTP_BAD_REQUEST);
		}

		/** @var Notification[] $notifications */
		$notifications = $this->em->getDao(Notification::class)->findBy([
			'user' => $user,
			'read' => FALSE
		]);

		foreach ($notifications as $notification) {
			$notification->read = TRUE;
		}

		$this->em->flush();
	}

}
