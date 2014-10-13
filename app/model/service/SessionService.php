<?php

namespace App\Model\Service;

use App\Model\Entity\Session;
use App\Model\Entity\User;
use DateTime;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 * @todo Fill desc.
 * @author Tomáš Markacz
 */
class SessionService extends Object
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
	 * @param User $user
	 * @param bool $longLife
	 * @return Session
	 */
	public function create(User $user, $longLife = FALSE)
	{
		$expiration = $longLife
			? new DateTime('+ 14 days')
			: new DateTime('+ 20 minutes');

		$session = new Session();

		$session->token      = $this->generateToken();
		$session->user       = $user;
		$session->created    = new DateTime();
		$session->expiration = $expiration;
		$session->longLife   = $longLife;

		$this->em->persist($session);

		return $session;
	}

	/**
	 * Returns authenticated session.
	 * @param string $token
	 * @return Session|FALSE
	 */
	public function getActiveSession($token)
	{
		/** @var Session $session */
		$session = $this->em->getDao(Session::class)->findOneBy(['token' => $token]);

		if (!$session) {
			return FALSE;
		}

		if ($session->expiration < new DateTime) {
			$this->em->remove($session);
			$this->em->flush();

			return FALSE;
		}

		$session->expiration = $session->longLife
			? new DateTime('+ 14 days')
			: new DateTime('+ 20 minutes');

		$this->em->flush();

		return $session;
	}

	/**
	 * @return string
	 */
	private function generateToken()
	{
		return base64_encode(openssl_random_pseudo_bytes(32));
	}

}
