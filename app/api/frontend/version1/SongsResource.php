<?php

namespace FrontendApi\Version1;


use App\Model\Entity\Song;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;

/**
 * @todo	Fill desc.
 * @author	Jiří Mantlík
 */
class SongsResource extends FrontendResource {

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

        $songs = $this->em->getDao(Song::class)->findBy(['owner'=>$this->getActiveSession()->user]);

        $songs = array_map(function (Song $song){
            return [
                'id'              => $song->id,
                'title'           => $song->title,
                'album'           => $song->album,
                'author'          => $song->author,
                'originalAuthor'  => $song->originalAuthor,
                'year'            => $song->year
            ];
        }, $songs);

        return Response::data($songs);
    }


} 