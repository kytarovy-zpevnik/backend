<?php

namespace FrontendApi\Version1;


use App\Model\Entity\Songbook;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;

/**
 * @todo	Fill desc.
 * @author	Jiří Mantlík
 */
class SongbooksResource extends FrontendResource {
    /** @var EntityManager */
    private $em;

    public function __construct(SessionService $sessionService, EntityManager $em)
    {
        parent::__construct($sessionService);

        $this->em = $em;
    }

    /**
     * @return Response
     */
    public function readAll()
    {
        $this->assumeLoggedIn(); // only logged can list his songs

        $songbooks = $this->em->getDao(Songbook::class)->findBy(['owner'=>$this->getActiveSession()->user]);

        $songbooks = array_map(function (Songbook $songbook){
            return [
                'id'              => $songbook->id,
                'name'           => $songbook->name
            ];
        }, $songbooks);

        return response::json($songbooks);
    }
} 