<?php

namespace App\Console;

use App\Model\Entity\Role;
use App\Model\Service\UserService;
use InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds new user into database and calculates his password's hash.
 * @author Tomáš Markacz
 */
class AddUserCommand extends Command
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
		parent::__construct(); // must be there otherwise symfony console throws an exception

	    $this->em = $em;
		$this->userService = $userService;
	}

	/**
	 */
    protected function configure()
	{
		$this->setName('app:add-user')
			->setDescription('Adds new user into database and calculates his password\'s hash.')
			->addArgument('username', InputArgument::REQUIRED)
			->addArgument('email', InputArgument::REQUIRED)
			->addArgument('password', InputArgument::REQUIRED)
			->addArgument('role', InputArgument::REQUIRED, 'Role slug from database.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$args = $input->getArguments();

		$role = $this->em->getDao(Role::class)
			->findOneBy(['slug' => $args['role']]);

		if (!$role) {
			throw self::invalidRoleException($args['role']);
		}

		$this->userService->create($args['username'], $args['email'], $args['password'], $role);

		$this->em->flush();
	}

	/**
	 * @param string $role
	 * @return InvalidArgumentException
	 */
	private static function invalidRoleException($role)
	{
		return new InvalidArgumentException("Invalid role $role given.");
	}

}
