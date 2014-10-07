<?php

namespace FrontendApi\Version1;

use App\DuplicateEmailException;
use App\DuplicateUsernameException;
use App\Model\Entity\Role;
use App\Model\Service\UserService;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Resource\Resource;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class UsersResource extends Resource
{

	/** @var EntityManager */
	private $em;

	/** @var UserService */
	private $userService;

	/**
	 * @param EntityManager $em
	 * @param UserService $userService
	 */
	public function __construct(EntityManager $em, UserService $userService)
	{
		$this->em          = $em;
		$this->userService = $userService;
	}

	/**
	 * @return Response
	 */
	public function create()
	{
		$data = $this->request->getPost();

		$role = $this->em->getDao(Role::class)->findOneBy(['slug' => 'registered']);

		try {
			$user = $this->userService->create($data['username'], $data['email'], $data['password'], $role);

		} catch (DuplicateUsernameException $e) {
			return Response::data([
				'error' => 'DUPLICATE_USERNAME',
				'message' => 'User with given username already created.'
			])->setHttpStatus(Response::HTTP_CONFLICT);

		} catch (DuplicateEmailException $e) {
			return Response::data([
				'error' => 'DUPLICATE_EMAIL',
				'message' => 'User with given email already created.'
			])->setHttpStatus(Response::HTTP_CONFLICT);
		}

		return Response::data([
			'id'       => $user->id,
			'username' => $user->username,
			'email'    => $user->email,
			'role'     => [
				'name' => $user->role->name,
				'slug' => $user->role->slug
			]
		]);
	}

}
