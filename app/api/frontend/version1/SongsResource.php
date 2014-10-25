<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Song;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Routing\AuthorizationException;

/**
 * Song resource class.
 * @author	Jiří Mantlík
 * @author  Tomáš Markacz
 */
class SongsResource extends FrontendResource {

    /** @var EntityManager */
    private $em;

	/**
	 * @param SessionService $sessionService
	 * @param EntityManager $em
	 */
    public function __construct(SessionService $sessionService, EntityManager $em)
    {
        parent::__construct($sessionService);

        $this->em = $em;
    }

	/**
	 * Creates new song.
	 */
	public function create()
	{
		$this->assumeLoggedIn();

		$data = $this->request->getData();

		$song = new Song;

		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
		$song->lyrics         = $data['lyrics'];
		$song->chords         = $data['chords'];
		$song->owner          = $this->getActiveSession()->user;
		$song->public         = FALSE;

		$this->em->persist($song);
		$this->em->flush();

		return Response::json([
			'id' => $song->id
		]);
	}

	/**
	 * Updates existing song.
	 * @param int $id
	 */
	public function update($id)
	{
		$data = $this->request->getData();

		/** @var Song $song */
		$song = $this->em->getDao(Song::class)->find($id);

		if (!$song) {
			return Response::json([
				'error' => 'UNKNOWN_SONG',
				'message' => 'Song with given id not found.'
			])->setHttpStatus(Response::HTTP_NOT_FOUND);
		}

		if (!$song->public) {
			$this->assumeLoggedIn();

			if ($this->getActiveSession()->user !== $song->owner) {
				throw new AuthorizationException;
			}
		}
		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
		$song->lyrics         = $data['lyrics'];
		$song->chords         = $data['chords'];
		$song->owner          = $this->getActiveSession()->user;
		$song->public         = FALSE;

		$this->em->flush();
	}

	/**
	 * Reads detailed information about song.
	 * @param int $id
	 * @return Response
	 */
	public function read($id)
	{
		/** @var Song $song */
		$song = $this->em->getDao(Song::class)->find($id);

		if (!$song) {
			return Response::json([
				'error' => 'UNKNOWN_SONG',
				'message' => 'Song with given id not found.'
			])->setHttpStatus(Response::HTTP_NOT_FOUND);
		}

		if (!$song->public) {
			$this->assumeLoggedIn();

			if ($this->getActiveSession()->user !== $song->owner) {
				throw new AuthorizationException;
			}
		}

		return Response::json([
			'id'             => $song->id,
			'title'          => $song->title,
			'album'          => $song->album,
			'author'         => $song->author,
			'originalAuthor' => $song->originalAuthor,
			'year'           => $song->year,
			'lyrics'         => $song->lyrics,
			'chords'         => $song->chords
		]);
	}

    /**
	 * Returns brief information about all user's songs.
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

        return response::json($songs);
    }

}
