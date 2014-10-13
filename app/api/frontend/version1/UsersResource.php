<?php

namespace FrontendApi\Version1;

use App\DuplicateEmailException;
use App\DuplicateUsernameException;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Service\SessionService;
use App\Model\Service\UserService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class UsersResource extends FrontendResource
{

	/** @var EntityManager */
	private $em;

	/** @var UserService */
	private $userService;

	/**
	 * @param EntityManager $em
	 * @param UserService $userService
	 * @param SessionService $sessionService
	 */
	public function __construct(EntityManager $em, UserService $userService, SessionService $sessionService)
	{
		parent::__construct($sessionService);

		$this->em             = $em;
		$this->userService    = $userService;
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

		return Response::data($this->mapEntity($user));
	}

	/**
	 * @return Response
	 */
	public function readAll()
	{
		$this->assumeAdmin(); // only admin can list all users

		$users = $this->em->getDao(User::class)->findAll();

		$data = array_map([$this, 'mapEntity'], $users);

		return Response::data($data);
	}

	/**
	 * @param int $id
	 * @return Response
	 */
	public function update($id)
	{
		$this->assumeAdmin(); // only admin can change user

		$roleSlug = $this->request->getPost('role')['slug'];
		$role     = $this->em->getDao(Role::class)->findOneBy(['slug' => $roleSlug]);

		$user = $this->em->getDao(User::class)->find($id);
		$user->role = $role;

		$this->em->flush();

		$data = $this->mapEntity($user);

		return Response::data($data);
	}

	/**
	 * Maps entity to api object.
	 * @param User $user
	 * @return array
	 */
	public static function mapEntity(User $user)
	{
		return [
			'id'       => $user->id,
			'username' => $user->username,
			'email'    => $user->email,
			'role'     => [
				'name' => $user->role->name,
				'slug' => $user->role->slug
			]
		];
	}

}
