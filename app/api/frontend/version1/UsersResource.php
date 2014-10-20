<?php

namespace FrontendApi\Version1;

use App\DuplicateEmailException;
use App\DuplicateUsernameException;
use App\Model\Entity\PasswordReset;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Service\SessionService;
use App\Model\Service\UserService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Nette\Utils\DateTime;

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
		$data = $this->request->getData();

		$role = $this->em->getDao(Role::class)->findOneBy(['slug' => 'registered']);

		try {
			$user = $this->userService->create($data['username'], $data['email'], $data['password'], $role);

		} catch (DuplicateUsernameException $e) {
			return response::json([
				'error' => 'DUPLICATE_USERNAME',
				'message' => 'User with given username already created.'
			])->setHttpStatus(Response::HTTP_CONFLICT);

		} catch (DuplicateEmailException $e) {
			return response::json([
				'error' => 'DUPLICATE_EMAIL',
				'message' => 'User with given email already created.'
			])->setHttpStatus(Response::HTTP_CONFLICT);
		}

		return response::json($this->mapEntity($user));
	}

	/**
	 * @return Response
	 */
	public function readAll()
	{
		$this->assumeAdmin(); // only admin can list all users

		$users = $this->em->getDao(User::class)->findAll();

		$data = array_map([$this, 'mapEntity'], $users);

		return response::json($data);
	}

	/**
	 * @param int $id
	 * @return Response
	 */
	public function update($id)
	{
        $this->assumeAdmin(); // only admin can change user

        $roleSlug = $this->request->getData('role')['slug'];
        $role = $this->em->getDao(Role::class)->findOneBy(['slug' => $roleSlug]);

        $user = $this->em->getDao(User::class)->find($id);
        $user->role = $role;

        $this->em->flush();

        $data = $this->mapEntity($user);

        return response::json($data);
	}

    /**
     * Method does not update all users, is used to update user's password by reset password token
     */
    public function updateAll() {
        $token = $this->request->getQuery('token');
        $passwordReset = $this->em->getDao(PasswordReset::class)->findOneBy(['token' => $token]);
        if($passwordReset == null || $passwordReset->createdOn < new DateTime(PasswordresetResource::TOKEN_EXPIRATION)) {
            return Response::json([
                "error" => "TOKEN_EXPIRATED",
                "message" => "Token is no longer valid"
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        $user = $passwordReset->user;
        $password = $this->request->getPost('password');
        $user->passwordHash =  $this->userService->getPasswordHash($password);
        $this->em->remove($passwordReset);
        $this->em->flush();
        //retun value is automatically set to 204
    }

	/**
	 * Maps entity to api object.
	 * @param User $user
	 * @return array
	 */
	public static function mapEntity(User $user)
	{
		return [
			'id'        => $user->id,
			'username'  => $user->username,
			'email'     => $user->email,
			'lastLogin' => self::formatDateTime($user->lastLogin),
			'role'      => [
				'id'   => $user->role->id,
				'name' => $user->role->name,
				'slug' => $user->role->slug
			]
		];
	}

}
