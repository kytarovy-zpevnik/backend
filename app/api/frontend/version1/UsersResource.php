<?php

namespace FrontendApi\Version1;

use App\DuplicateEmailException;
use App\DuplicateUsernameException;
use App\Model\Entity\PasswordReset;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Service\SessionService;
use App\Model\Service\NotificationService;
use App\Model\Service\UserService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Nette\Utils\DateTime;

/**
 * Resource for User CRUD operations.
 *
 * @author	Tomáš Markacz
 */
class UsersResource extends FrontendResource
{

	/** @var UserService */
	private $userService;

	/**
	 * @param SessionService $sessionService
     * @param NotificationService $notificationService
     * @param EntityManager $em
     * @param UserService $userService
	 */
	public function __construct(SessionService $sessionService, NotificationService $notificationService, EntityManager $em, UserService $userService)
	{
        parent::__construct($sessionService, $notificationService, $em);

		$this->userService    = $userService;
	}

	/**
     * Creates user.
	 * @return Response Response with User object.
	 */
	public function create()
	{
		$data = $this->request->getData();

		$role = $this->em->getDao(Role::getClassName())->findOneBy(['slug' => 'registered']);

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
     * If username is set, read user by username. Else read all users.
	 * @return Response Response with User object or with array of User objects.
	 */
	public function readAll()
	{
        $this->assumeLoggedIn();

        $userName = $this->request->getQuery('username');

        if($userName){
            $user = $this->em->getDao(User::getClassName())->findOneBy(['username' => $userName]);

            if (!$user) {
                return response::json([
                    'error'   => 'UNKNOWN_IDENTIFIER',
                    'message' => 'No user account with given username found.'
                ])->setHttpStatus(Response::HTTP_NOT_FOUND);
            }

            return Response::json($this->mapEntity($user));
        }


		$this->assumeAdmin(); // only admin can list all users

		$users = $this->em->getDao(User::getClassName())->findAll();

		$data = array_map([$this, 'mapEntity'], $users);

		return response::json($data);
	}

	/**
     * Update user by id.
	 * @param int $id
	 * @return Response Response with User object.
	 */
	public function update($id)
	{
        $this->assumeAdmin(); // only admin can change user

        $roleSlug = $this->request->getData('role')['slug'];
        $role = $this->em->getDao(Role::getClassName())->findOneBy(['slug' => $roleSlug]);

        $user = $this->em->getDao(User::getClassName())->find($id);
        $user->role = $role;

        $this->em->flush();

        $data = $this->mapEntity($user);

        return response::json($data);
	}

    /**
     * Method does not update all users, is used to update user's password by reset password token.
     */
    public function updateAll() {
        $token = $this->request->getQuery('token');
        $passwordReset = $this->em->getDao(PasswordReset::getClassName())->findOneBy(['token' => $token]);
        if($passwordReset == null || $passwordReset->createdOn < new DateTime(PasswordresetResource::TOKEN_EXPIRATION)) {
            return Response::json([
                "error" => "TOKEN_EXPIRATED",
                "message" => "Token is no longer valid"
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        $user = $passwordReset->user;
        $password = $this->request->getData('password');
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
