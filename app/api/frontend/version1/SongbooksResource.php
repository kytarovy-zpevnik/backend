<?php

namespace FrontendApi\Version1;


use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Routing\AuthorizationException;

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
     * Reads detailed information about songbook.
     * @param int $id
     * @return Response
     */
    public function read($id)
    {
        $songbook = $this->em->getDao(Songbook::class)->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$songbook->public){
            $this->assumeLoggedIn();

            if($this->getActiveSession()->user !== $songbook->owner){
                throw new AuthorizationException;
            }
        }

        $songs = array_map(function (Song $song){
            return [
                'id'              => $song->id,
                'title'           => $song->title,
                'album'           => $song->album,
                'author'          => $song->author,
                'originalAuthor'  => $song->originalAuthor,
                'year'            => $song->year
            ];
        }, $songbook->songs);

        return Response::json([
            'id'             => $songbook->id,
            'name'          => $songbook->name,
            'songs'      => $songs
        ]);
    }

    /**
     * Returns brief information about all user's songbooks.
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