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
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Nette\Utils\DateTime;

/**
 * @author	peroupav
 */
class PasswordresetResource extends FrontendResource {

    /** @var EntityManager */
    private $em;
    const TOKEN_EXPIRATION = "-1day";

    public function __construct(SessionService $sessionService, EntityManager $em)
    {
        parent::__construct($sessionService);

        $this->em = $em;
    }

    public function create() {
        $identifier = $this->request->getPost()["user"]["identifier"];

        $result = $this->em->getDao(User::class)
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

        $this->em->persist($passwordReset);
        $this->em->flush();

        return Response::blank();

    }

    private function generateToken()
    {
        return str_replace(['+','/','='],['-','_',''], base64_encode(openssl_random_pseudo_bytes(32)));
    }
} 