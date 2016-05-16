<?php
/**
 * Created by PhpStorm.
 * User: pero
 * Date: 10/18/14
 * Time: 3:25 AM
 */

namespace FrontendApi\Version1;


use App\Model\Entity\PasswordReset;
use App\Model\Entity\User;
use App\Model\Service\SessionService;
use App\Model\Service\NotificationService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;
use Nette\Utils\DateTime;

/**
 * Resource for resetting user password.
 * @author	Pavel Peroutka
 */
class PasswordresetResource extends FrontendResource {

    /** @var string */
    const TOKEN_EXPIRATION = "-1day";

    /**
     * @param SessionService $sessionService
     * @param NotificationService $notificationService
     * @param EntityManager $em
     */
    public function __construct(SessionService $sessionService, NotificationService $notificationService, EntityManager $em)
    {
        parent::__construct($sessionService, $notificationService, $em);

    }

    /**
     * Creates entry for reset password request and sends email with generated URL to reset password.
     * If reset password was already requested and is not expired, response is negative.
     * @return Response
     * @throws \Exception
     * @throws \Nette\Mail\SmtpException
     */
    public function create() {
        $identifier = $this->request->getData()["user"]["identifier"];

        $result = $this->em->getDao(User::getClassName())
            ->createQueryBuilder('u') // I need to search by username OR email
            ->where('u.username = :identifier OR u.email = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getResult();

        if (!$result) {
            return response::json([
                'error'   => 'UNKNOWN_IDENTIFIER',
                'message' => 'No user account with given identifier as username or email address found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = reset($result); // first item

        if($user->passwordReset) {
            if($user->passwordReset->createdOn < new DateTime(self::TOKEN_EXPIRATION)) {
                $this->em->remove($user->passwordReset);
            }
            else {
                return Response::json([
                "error"=> "ALREADY_SENT",
                "message"=>"Recent PasswordReset entity found"
                ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
            }
        }

        $passwordReset = new PasswordReset();
        $passwordReset->user = $user;
        $passwordReset->createdOn = new DateTime();
        $passwordReset->token = $this->generateToken();

		$smtp = new SmtpMailer([
			'host'     => 'smtp.gmail.com',
			'username' => 'kontakt.kytarovy.zpevnik@gmail.com',
			'password' => 'zdenekrybola',
			'secure'   => 'ssl'
		]);

		$message = new Message();
		$message->setSubject('Nastavení zapomenutého hesla | kz.markacz.com');
		$message->addTo($user->email);
		$message->setFrom('kontakt.kytarovy.zpevnik@gmail.com');
		$message->setBody("
Dobrý den,

přijali jsme požadavek na změnu hesla pro uživatelský účet $user->username.\n

Nastavení hesla provedete na tomto odkazu:\n
http://kz.markacz.com/#/reset-password/step2/$passwordReset->token\n

Pokud tento požadavek nebyl iniciován z Vaší strany, jednoduše tento email ignorujte.\n

Tým kytarového zpěvníku
		");

		$smtp->send($message);

        $this->em->persist($passwordReset);
        $this->em->flush();

        return Response::blank();
    }

    /**
     * Generates token and replace special characters, so it can be used in URL.
     * @return string
     */
    private function generateToken()
    {
        return str_replace(['+','/','='],['-','_',''], base64_encode(openssl_random_pseudo_bytes(32)));
    }
} 