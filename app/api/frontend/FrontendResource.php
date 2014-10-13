<?php

namespace FrontendApi;

use App\Model\Entity\Session;
use App\Model\Service\SessionService;
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

	/**
	 * @param SessionService $serviceService
	 */
	public function __construct(SessionService $serviceService)
	{
		$this->sessionService = $serviceService;
	}

	/**
	 * Returns active session.
	 * @return Session|FALSE
	 */
	protected function getActiveSession()
	{
		static $activeSession = NULL;

		if ($activeSession !== NULL) {
			return $activeSession;
		}

		$token = $this->request->getHeader('x-session-token');

		if (!$token) {
			return $activeSession = FALSE;
		}

		return $activeSession = $this->sessionService->getActiveSession($token);
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

}
