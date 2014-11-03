<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Wish;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Nette\Utils\DateTime;
use Markatom\RestApp\Routing\AuthorizationException;

/**
 * Resource for Wish CRUD operations.
 *
 * @author	Tomáš Jirásek
 */
class WishesResource extends FrontendResource
{

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     * @param SessionService $sessionService
     */
    public function __construct(EntityManager $em, SessionService $sessionService)
    {
        parent::__construct($sessionService);

        $this->em = $em;
    }

    /**
     * Creates wish.
     * @return Response Response with Wish object.
     */
    public function create()
    {
        $this->assumeLoggedIn();

        $data = $this->request->getData();

        $wish = new Wish;

        $wish->name = $data['name'];
        $wish->note = $data['note'];
        $wish->user = $this->getActiveSession()->user;
        $wish->created = new DateTime();
        $wish->modified = new DateTime();
        $wish->meet = FALSE;

        $this->em->persist($wish);
        $this->em->flush();

        return Response::json([
            'id' => $wish->id
        ]);
    }

    /**
     * Read all user's wishes.
     * @return Response Response with array of Wish objects.
     */
    public function readAll()
    {
        $this->assumeLoggedIn(); // only logged can list his wishes


        $wishes = $this->em->getDao(Wish::class)
            ->findBy(['user' => $this->getActiveSession()->user]);

        $wishes = array_map(function (Wish $wish) {
            return [
                'id' => $wish->id,
                'name' => $wish->name,
                'note' => $wish->note,
                'meet' => $wish->meet,
                'created' => self::formatDateTime($wish->created),
                'modified' => self::formatDateTime($wish->modified),
            ];
        }, $wishes);

        return response::json($wishes);
    }

    /**
     * Reads detailed information about wish.
     * @param int $id
     * @return Response
     */
    public function read($id)
    {
        /** @var Wish $wish */
        $wish = $this->em->getDao(Wish::class)->find($id);

        if (!$wish) {
            return Response::json([
                'error' => 'UNKNOWN_WISH',
                'message' => 'Wish with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        if ($this->getActiveSession()->user !== $wish->user) {
            throw new AuthorizationException;
        }

        return Response::json([
            'id' => $wish->id,
            'name' => $wish->name,
            'note' => $wish->note,
            'meet' => $wish->meet,
            'created' => self::formatDateTime($wish->created),
            'modified' => self::formatDateTime($wish->modified),
        ]);
    }

    /**
     * Updates existing wish.
     * @param int $id
     */
    public function update($id)
    {
        $data = $this->request->getData();

        /** @var Wish $wish */
        $wish = $this->em->getDao(Wish::class)->find($id);

        if (!$wish) {
            return Response::json([
                'error' => 'UNKNOWN_WISH',
                'message' => 'Wish with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        if ($this->getActiveSession()->user !== $wish->user) {
            throw new AuthorizationException;
        }

        $wish->name = $data['name'];
        $wish->note = $data['note'];
        $wish->meet = $data['meet'];
        $wish->modified = new DateTime();

        $this->em->flush();

        return Response::json([
            'id' => $wish->id
        ]);
    }

    /**
     * Delete wish.
     * @param int $id
     * @return Response
     */
    public function delete($id)
    {
        /** @var Wish $wish */
        $wish = $this->em->getDao(Wish::class)->find($id);

        if (!$wish) {
            return Response::json([
                'error' => 'UNKNOWN_WISH',
                'message' => 'Wish with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        if ($this->getActiveSession()->user !== $wish->user) {
            throw new AuthorizationException;
        }

        $this->em->remove($wish);

        $this->em->flush();

        return Response::blank();
    }
}