<?php

namespace FrontendApi;

use App\Model\Entity\Session;
use App\Model\Service\SessionService;
use App\Model\Service\NotificationService;
use Kdyby\Doctrine\EntityManager;
use DateTime;
use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Resource\Resource;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;

/**
 * Resource with session information.
 * @author Tomáš Markacz
 */
class FrontendResource extends Resource
{

	/** @desc Slug for admin role. */
	const ROLE_ADMIN = 'admin';

	/** @var SessionService */
	protected $sessionService;

	/** @var Session */
	private $activeSession;

    /** @var EntityManager */
    protected $em;

    /** @var NotificationService */
    protected $notificationService;

	/**
	 * @param SessionService $serviceService
     * @param NotificationService $notificationService
     * @param EntityManager $em
	 */
	public function __construct(SessionService $serviceService, NotificationService $notificationService, EntityManager $em)
	{
		$this->sessionService = $serviceService;
        $this->notificationService = $notificationService;
        $this->em = $em;
	}

	/**
	 * @param Request $request
	 * @return Response
	 */
	public function handle(Request $request)
	{
		if ($request->getData() != null && !is_array($request->getData())) {
			return Response::blank()->setHttpStatus(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
		}

		return parent::handle($request);
	}

	/**
	 * Returns active session.
	 * @return Session|FALSE
	 */
	protected function getActiveSession()
	{
		if ($this->activeSession !== NULL) {
			return $this->activeSession;
		}

		$token = $this->request->getHeader('x-session-token');

		return $this->activeSession = (
			$token
				? $this->sessionService->getActiveSession($token)
				: FALSE
		);
	}

	/**
	 * @throws AuthenticationException
	 */
	protected function assumeLoggedIn()
	{
		$session = $this->getActiveSession();

		if (!$session) {
			throw new AuthenticationException('Api user not logged in.');
		}
	}

	/**
	 * @throws AuthorizationException
	 */
	protected function assumeAdmin()
	{
		$this->assumeLoggedIn();

		if ($this->getActiveSession()->user->role->slug !== self::ROLE_ADMIN) {
			throw new AuthorizationException('Action not allowed.');
		}
	}

	/**
	 * @param DateTime $dateTime
	 * @return string
	 */
	protected static function formatDateTime($dateTime)
	{
		return $dateTime instanceof DateTime
			? $dateTime->format('Y-m-d H:i:s')
			: NULL;
	}

}
