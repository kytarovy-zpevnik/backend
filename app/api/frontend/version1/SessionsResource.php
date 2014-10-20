<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Session;
use App\Model\Entity\User;
use App\Model\Service\SessionService;
use App\Model\Service\UserService;
use App\SecurityException;
use DateTime;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Resource\Resource;

/**
 * @todo Fill desc.
 * @author Tomáš Markacz
 */
class SessionsResource extends Resource
{

	/** @var EntityManager */
	private $em;

	/** @var SessionService */
	private $sessionService;

	/** @var UserService */
	private $userService;

	/**
	 * @param EntityManager $em
	 * @param SessionService $sessionService
	 * @param UserService $userService
	 */
	public function __construct(EntityManager $em, SessionService $sessionService, UserService $userService)
	{
		$this->em             = $em;
		$this->sessionService = $sessionService;
		$this->userService    = $userService;
	}

	/**
	 * Creates authenticated user session.
	 * @return Response
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function create()
	{
		$data = $this->request->getData();

		/** @var User $user */
		$result = $this->em->getDao(User::class)
			->createQueryBuilder('u') // I need to search by username OR email
			->where('u.username = :identifier OR u.email = :identifier')
			->setParameter('identifier', $data['user']['identifier'])
			->getQuery()
			->getResult();

		if (!$result) {
			return response::json([
				'error'   => 'UNKNOWN_IDENTIFIER',
				'message' => 'No user account with given identifier as username or email address found.'
			])->setHttpStatus(Response::HTTP_NOT_FOUND);
		}

		$user = reset($result); // first item

		try {
			if (!$this->userService->verifyPasswordHash($user->passwordHash, $data['user']['password'])) {
				return response::json([
					'error'   => 'INVALID_CREDENTIAL',
					'message' => 'Client supplied invalid credential.'
				])->setHttpStatus(Response::HTTP_FORBIDDEN);
			}

		} catch (SecurityException $e) { // correct password, just rehash
			$user->passwordHash = $this->userService->getPasswordHash($data['password']);
		}

		$user->lastLogin = new DateTime();

		$session = $this->sessionService->create($user, $data['longLife']);

		$this->em->flush();

		return response::json([
			'token' => $session->token,
			'user'  => UsersResource::mapEntity($session->user)
		]);
	}

	/**
	 * Terminates active user session.
	 * @return Response
	 */
	public function deleteActive()
	{
		$token = $this->request->getHeader('x-session-token');

		$session = $this->em->getDao(Session::class)->findOneBy(['token' => $token]);

		if (!$session) {
			return response::json([
				'error'   => 'INVALID_SESSION',
				'message' => 'Unknown session or missing session token header.'
			])->setHttpStatus(Response::HTTP_BAD_REQUEST);
		}

		$this->em->remove($session);
		$this->em->flush();

		return response::blank()
			->setHttpStatus(Response::HTTP_OK);
	}

}
