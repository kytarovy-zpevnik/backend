<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Song;
use App\Model\Entity\SongRating;
use App\Model\Entity\Songbook;
use App\Model\Entity\SongSongbook;
use App\Model\Entity\SongComment;
use App\Model\Entity\SongSharing;
use App\Model\Entity\SongTaking;
use App\Model\Entity\SongCopy;
use App\Model\Entity\User;
use App\Model\Entity\Wish;
use App\Model\Entity\Notification;
use App\Model\Entity\SongTag;
use App\Model\Query\SongAdvSearchQuery;
use App\Model\Query\SongSearchQuery;
use App\Model\Service\SessionService;
use App\Model\Service\NotificationService;
use App\Model\Service\SongService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Routing\AuthorizationException;
use Nette\Utils\DateTime;

/**
 * Resource for Song CRUD operations.
 *
 * @author	Jiří Mantlík
 * @author  Tomáš Markacz
 */
class SongsResource extends FrontendResource {

	/** @var SongService */
	private $songService;

	/**
	 * @param SessionService $sessionService
     * @param NotificationService $notificationService
     * @param EntityManager $em
	 * @param SongService $songService
	 */
    public function __construct(SessionService $sessionService, NotificationService $notificationService, EntityManager $em, SongService $songService)
    {
        parent::__construct($sessionService, $notificationService, $em);

		$this->songService = $songService;
    }

    /**
     * Creates new song.
     * @return Response
     */
	public function create()
	{
		$this->assumeLoggedIn();

		$data = $this->request->getData();

		$song = new Song;

		$ids = array_map(function ($songbook) {
			return $songbook['id'];
		}, $data['songbooks']);

		$songbooks = $this->em->getDao(Songbook::getClassName())->findBy(['id' => $ids]);

        foreach ($songbooks as $songbook) {
            $songsongbook = new SongSongbook();
            $songsongbook->song = $song;
            $songsongbook->songbook = $songbook;
            $songsongbook->position = count($songbook->songs) + 1;
            $this->em->persist($songsongbook);
            $song->addSongbook($songsongbook);
        }

        $tags = array_map(function ($tag) {
            $_tag = new SongTag();
            $_tag->tag = $tag['tag'];
            $_tag->public = $tag['public'];
            $_tag->user = $this->getActiveSession()->user;
            return $_tag;
        }, $data['tags']);

        foreach ($tags as $tag) {
            $tag->song = $song;
            $song->addTag($tag);
            $this->em->persist($tag);
        }

        // XML IMPORT
        /*if ($this->request->getQuery('import') === 'agama') {
            $this->songService->importAgama($song, $data['agama']);

        } else {*/
            $song->lyrics = $data['lyrics'];
            $song->chords = $data['chords'];
        //}

		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
        $song->note           = $data['note'];
		$song->owner          = $this->getActiveSession()->user;
		$song->public         = $data['public'];
        $song->created        = new DateTime();
        $song->modified       = $song->created;

		$this->em->persist($song);

        if ($song->public) {
            /** @var Wish[] $wishes */
            $wishes = $this->em->getDao(Wish::getClassName())->findBy(['name' => $song->title, 'interpret' => $song->author]);
            foreach ($wishes as $wish) {
                if ($wish->user != $song->owner) {
                    $this->notificationService->notify($wish->user, 'wished', $song);
                }
            }
        }

		$this->em->flush();

		return Response::json([
			'id' => $song->id
		])->setHttpStatus(Response::HTTP_CREATED);
	}

    /**
     * Returns brief information about all user's songs.
     * @return Response
     */
    public function readAll()
    {
        $public = false;
        $user = null;
        $findBy = ['archived' => 0];
        if ($this->request->getQuery('public')) {
            $public = true;
            $findBy['public'] = 1;
        }
        else {
            $this->assumeLoggedIn(); // only logged can list his songs
            $user = $this->getActiveSession()->user;
            $findBy['owner'] = $user;
        }

        if ($this->request->getQuery('admin')) {
            $this->assumeAdmin();
            $songs = $this->em->getDao(Song::getClassName())
                ->findBy(array(), ['id' => 'ASC']);
        }
        else if ($this->request->getQuery('random')) {
            $songs = $this->em->getDao(Song::getClassName())->findBy($findBy, ['title' => 'ASC']);
            if(sizeof($songs) > 1){
                $keys = array_rand ($songs, (8 < sizeof($songs) ? 8 : sizeof($songs)));

                $randSongs = array();
                while (list($k, $v) = each($keys))
                {
                    $randSongs[] = $songs[$v];
                }
                $songs = $randSongs;
            }
        }
        else if ($search = $this->request->getQuery('search')) {
            $songs = $this->em->getDao(Song::getClassName())
                ->fetch(new SongSearchQuery($user, $search, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else if ($this->request->getQuery('title') || $this->request->getQuery('album') ||
                  $this->request->getQuery('author') || $this->request->getQuery('year') ||
                  $this->request->getQuery('owner') || $this->request->getQuery('tag')) {
            $title  = $this->request->getQuery('title');
            $album  = $this->request->getQuery('album');
            $author = $this->request->getQuery('author');
            $year  = $this->request->getQuery('year');
            $owner  = $this->request->getQuery('owner');
            $tag = $this->request->getQuery('tag');
            $songs  = $this->em->getDao(Song::getClassName())
                ->fetch(new SongAdvSearchQuery($user, $title, $album, $author, $year, $owner, $tag, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else {
            $songs = $this->em->getDao(Song::getClassName())->findBy($findBy);
            if (!$public){
                $takenSongs = $this->em->getDao(SongTaking::getClassName())
                    ->findBy(['user' => $user]);
                $takenSongs = array_map(function(SongTaking $taking){
                    return $taking->song;
                }, $takenSongs);
                $songs = array_merge($songs, $takenSongs);
            }
        }

        @usort($songs, function(Song $a, Song $b){
            $sort = $this->request->getQuery('sort');

            if($this->request->getQuery('order') == 'desc') {
                $c = $a;
                $a = $b;
                $b = $c;
            }

            switch($sort){
                case 'title':
                    return strcasecmp($a->title, $b->title);
                    break;
                case 'author':
                    return strcasecmp($a->author, $b->author);
                    break;
                case 'album':
                    return strcasecmp($a->album, $b->album);
                    break;
                case 'year':
                    return ($a->year < $b->year) ? -1 : (($a->year > $b->year) ? 1 : 0);
                    break;
                case 'owner':
                    return strcasecmp($a->owner->username, $b->owner->username);
                    break;
                case 'rating':
                    return ($a->getAverageRating()['rating'] < $b->getAverageRating()['rating']) ? -1 : (($a->getAverageRating()['rating'] > $b->getAverageRating()['rating']) ? 1 : 0);
                    break;
                default:
                    return ($a->id < $b->id) ? -1 : (($a->id > $b->id) ? 1 : 0);
                    break;
            }
        });

        $offset = $this->request->getQuery('offset');
        if(!$offset)
            $offset = 0;
        $length = $this->request->getQuery('length');
        $songs = array_slice($songs, $offset, $length);

        $songs = array_map(function (Song $song){

            $session = $this->getActiveSession();
            $tags = array();
            foreach($song->tags as $tag){
                if($tag->public == true || ($session && $tag->user == $session->user)){
                    $tags[] = $tag;
                }
            }

            $tags = array_map(function(SongTag $tag){
                return [
                    'tag'    => $tag->tag,
                    'public' => $tag->public
                ];
            }, $tags);

            return [
                'id'              => $song->id,
                'title'           => $song->title,
                'album'           => $song->album,
                'author'          => $song->author,
                'year'            => $song->year,
                'public'          => $song->public,
                'archived'        => $song->archived,
                'username'        => $song->owner->username,
                'tags'            => $tags,
                'rating'          => $song->getAverageRating()
            ];
        }, $songs);

        return response::json($songs);
    }

    /**
     * Reads detailed information about song.
     * @param int $id
     * @return Response
     */
    public function read($id)
    {
        $song = $this->getSong($id);

        if (!$song) {
            return Response::json([
                'error'   => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $session = $this->getActiveSession();
        if ($this->request->getQuery('old') && $session) {
            $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $session->user, 'song' => $song]);
            if ($taking && $copy = $taking->songCopy) {
                $song->title = $copy->title;
                $song->author = $copy->author;
                $song->originalAuthor = $copy->originalAuthor;
                $song->album = $copy->album;
                $song->year = $copy->year;
                $song->lyrics = $copy->lyrics;
                $song->chords = $copy->chords;
            }
            else {
                return Response::json([
                    'error' => 'NOT_FOUND',
                    'message' => 'No older version of this song found.'
                ])->setHttpStatus(Response::HTTP_NOT_FOUND);
            }
        }

        // XML EXPORT
        /*if ($this->request->getQuery('export') === 'agama') {
            return Response::json([
                'agama' => $this->songService->exportAgama($song)
            ]);

        } else {*/
        return $this->songToResponse($song);
        //}
    }

	/**
	 * Updates existing song.
	 * @param int $id
	 */
	public function update($id)
	{
		$data = $this->request->getData();

		/** @var Song $song */
		$song = $this->em->getDao(Song::getClassName())->find($id);

		if (!$song || $song->archived) {
			return Response::json([
				'error' => 'UNKNOWN_SONG',
				'message' => 'Song with given id not found.'
			])->setHttpStatus(Response::HTTP_NOT_FOUND);
		}

        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;
        if ($user !== $song->owner &&
            !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])) {
            $this->assumeAdmin();
        }

        if($action = $this->request->getQuery('action')){
            switch($action){
                case 'tags':
                    $this->updateTags($song, $data['tags']);
                    break;
                case 'songbooks':
                    $this->updateSongbooks($song, $data['songbooks']);
                    break;
            }
            $this->em->flush();
            return Response::blank();
        }

		if ($user !== $song->owner) {
            $this->assumeAdmin();
        }

        $takings = $this->em->getDao(SongTaking::getClassName())->findBy(['song' => $song]);
        $takings = array_filter($takings, function(SongTaking $taking){
            return $taking->songCopy ? FALSE : TRUE;
        });
        if ($takings) {
            $copy = new SongCopy();
            $copy->title          = $song->title;
            $copy->album          = $song->album;
            $copy->author         = $song->author;
            $copy->originalAuthor = $song->originalAuthor;
            $copy->year           = $song->year;
            $copy->lyrics         = $song->lyrics;
            $copy->chords         = $song->chords;
            $this->em->persist($copy);

            foreach ($takings as $taking) {
                $taking->songCopy = $copy;
                $this->notificationService->notify($taking->user, 'updated taken', $song, $user);
            }
        }

        $this->updateTags($song, $data['tags']);
        $this->updateSongbooks($song, $data['songbooks']);

		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
		$song->lyrics         = $data['lyrics'];
		$song->chords         = $data['chords'];
        $song->note           = $data['note'];
		$song->public         = $data['public'];
        $song->modified       = new DateTime();

        $this->em->flush();

        return Response::json([
            'id' => $song->id
        ]);
	}

    /**
     * Deletes Song by id.
     * @param $id
     */
    public function delete($id)
    {

        $this->assumeLoggedIn();

        /** @var Song */
        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if($curUser !== $song->owner){
            $this->assumeAdmin();
        }

        $text = '';
        if($song->archived == true) {
            $song->archived = false;
            $text = 'restored';
        }
        else{
            $song->archived = true;
            $text = 'deleted';
        }
        $song->modified = new DateTime();

        $takings = $this->em->getDao(SongTaking::getClassName())->findBy(['song' => $song]);
        foreach ($takings as $taking) {
            $this->notificationService->notify($taking->user, $text . ' taken', $song, $curUser);
        }
        if($curUser !== $song->owner)
            $this->notificationService->notify($song->owner, $text . ' by admin', $song, $curUser);

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Updates tags for given song
     * @param Song $song
     * @param $tags
     */
    private function updateTags(Song $song, $tags)
    {
        $tags = array_map(function ($tag) {
            $_tag = new SongTag();
            $_tag->tag = $tag['tag'];
            $_tag->public = $tag['public'];
            $_tag->user = $this->getActiveSession()->user;
            return $_tag;
        }, $tags);

        foreach ($song->tags as $tag) {
            if($tag->user != $this->getActiveSession()->user){
                $tags[] = $tag;
            }
            $this->em->remove($tag);
        }
        $song->clearTags();
        foreach ($tags as $tag) {
            if($tag->public && $tag->user != $song->owner){
                continue;
            }
            $tag->song = $song;
            $song->addTag($tag);
            $this->em->persist($tag);
        }
    }

    /**
     * Updates songbooks for given song
     * @param Song $song
     * @param $songbooks
     */
    private function updateSongbooks(Song $song, $songbooks)
    {
        $ids = array_map(function ($songbook) {
            return $songbook['id'];
        }, $songbooks);

        $songbooks = $this->em->getDao(Songbook::getClassName())->findBy(['id' => $ids]); // songbooky, kam chci song pridat

        foreach ($song->songbooks as $songsongbook) { // prochazim songbooky, kde tenhle song je
            $keepit = false;
            foreach($songbooks as $songbook){
                if($songsongbook->songbook->id == $songbook->id){ // pokud je songbook v obou mnozinach, tak ho tam necham
                    $keepit = true;
                    break;
                }
            }
            if($keepit || $songsongbook->songbook->owner != $this->getActiveSession()->user){
                continue;
            }
            $this->removeSongFromSongbook($songsongbook);
            $song->removeSongbook($songsongbook);

        }

        foreach ($songbooks as $songbook) {
            $songsongbook = $this->em->getDao(SongSongbook::getClassName())->findOneBy(['songbook' => $songbook, 'song' => $song]);
            if(!$songsongbook){
                $songsongbook = new SongSongbook();
                $songsongbook->song = $song;
                $songsongbook->songbook = $songbook;
                $songsongbook->position = count($songbook->songs) + 1;
                $this->em->persist($songsongbook);
                $song->addSongbook($songsongbook);
            }
        }
    }

    /**
     * Removes song from songbook and alters position of remaining songs
     * @param SongSongbook $songsongbook
     */
    private function removeSongFromSongbook(SongSongbook $songsongbook){
        $this->em->remove($songsongbook);
        $this->em->flush($songsongbook);
        $othersongs = $this->em->getDao(SongSongbook::getClassName())->findBy(['songbook' => $songsongbook->songbook], ['position' => 'ASC']);

        foreach ($othersongs as $other){
            if($other->position > $songsongbook->position){
                $other->position -= 1;
                $this->em->persist($other);
            }
        }
    }

    /**
     * Obtains song entity by given id.
     * @param int $id
     * @return Song|FALSE
     */
    private function getSong($id)
    {
        /** @var Song $song */
        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song) {
            return FALSE;
        }
        $session = $this->getActiveSession();
        if ($song->archived) {
            if (!($session && $this->em->getDao(SongTaking::getClassName())->findBy(['user' => $session->user, 'song' => $song]))) {
                $this->assumeAdmin();
            }
        }
        else if (!$song->public) {
            $this->assumeLoggedIn();

            $user = $session->user;
            if ($user !== $song->owner &&
                !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
                !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])) {
                    $this->assumeAdmin();
            }
        }

        return $song;
    }

    /**
     * Maps song data to api response.
     * @param Song $song
     * @return Response
     */
    private function SongToResponse(Song $song)
    {
        $session = $this->getActiveSession();

        $songbooks = array();
        foreach($song->songbooks as $songsongbook){
            $songbook = $songsongbook->songbook;
            if($session && $songbook->owner == $session->user){
                $songbooks[] = $songbook;
            }
        }
        $songbooks = array_map(function (Songbook $songbook) {
            return [
                'id'   => $songbook->id,
                'name' => $songbook->name,
                'note' => $songbook->note
            ];
        }, $songbooks);

        $tags = array();
        foreach($song->tags as $tag){
            if($tag->public == true || ($session && $tag->user == $session->user)){
                $tags[] = $tag;
            }
        }

        $tags = array_map(function(SongTag $tag){
            return [
                'tag'    => $tag->tag,
                'public' => $tag->public
            ];
        }, $tags);

        $taken = [
            'taken' => false,
            'copy'  => null
        ];
        if($session && $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $session->user, 'song' => $song])){
            $taken['taken'] = true;
            if($taking->songCopy)
                $taken['copy'] = $taking->songCopy->id;
        }

        return Response::json([
            'id'             => $song->id,
            'title'          => $song->title,
            'album'          => $song->album,
            'author'         => $song->author,
            'originalAuthor' => $song->originalAuthor,
            'year'           => $song->year,
            'lyrics'         => $song->lyrics,
            'chords'         => $song->chords,
            'note'           => $song->note,
            'public'         => $song->public,
            'archived'        => $song->archived,
            'songbooks'      => $songbooks,
            'username'       => $song->owner->username,
            'tags'           => $tags,
            'rating'         => $song->getAverageRating(),
            'taking'          => $taken
        ]);
    }

    /**
     * Creates song rating by song id.
     * @param int $id
     * @return Response Response with SongRating object.
     */
    public function createRating($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $user = $this->getActiveSession()->user;
        if ($user == $song->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot rate his own songs.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        if ($this->em->getDao(SongRating::getClassName())->findBy(['user' => $user, 'song' => $song])){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot rate song more than once.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }

        if (!$song->public &&
            !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
            !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])){
                $this->assumeAdmin();
        }

        $data = $this->request->getData();

        $rating = new SongRating;

        $rating->user = $user;
        $rating->song = $song;
        $rating->created = new DateTime();
        $rating->modified = $rating->created;
        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];

        $this->em->persist($rating);

        $this->notificationService->notify($song->owner, 'rated', $song, $user);

        $this->em->flush();

        return Response::json([
            'id' => $rating->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

    /**
     * Reads all song's ratings.
     * @param int $id
     * @return Response
     */
    public function readAllRating($id)
    {
        /** @var SongRating $rating */
        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$song->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $song->owner &&
                !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
                !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])){
                    $this->assumeAdmin();
            }
        }

        $ratings = $this->em->getDao(SongRating::getClassName())->findBy(['song' => $song]);

        $ratings = array_map(function (SongRating $rating){
            return [
                'id'       => $rating->id,
                'comment'  => $rating->comment,
                'rating'   => $rating->rating,
                'user'     => [
                                'id' => $rating->user->id,
                                'username' => $rating->user->username
                              ],
                'created'  => self::formatDateTime($rating->created),
                'modified' => self::formatDateTime($rating->modified)
            ];
        }, $ratings);

        return response::json($ratings);
    }

    /**
     * Updates existing song rating.
     * @param int $relationId
     * @return Response Response with SongRating object.
     */
    public function updateRating($relationId)
    {
        $data = $this->request->getData();

        /** @var SongRating $rating */
        $rating = $this->em->getDao(SongRating::getClassName())->find($relationId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_RATING',
                'message' => 'Song rating with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        $user = $this->getActiveSession()->user;
        if ($user !== $rating->user){
            $this->assumeAdmin();
        }

        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];
        $rating->modified = new DateTime();

        $this->notificationService->notify($rating->song->owner, 'updated rating', $rating->song, $user);

        $this->em->flush();

        return Response::json([
            'id' => $rating->id
        ]);
    }

    /**
     * Delete song rating.
     * @param int $relationId
     * @return Response
     */
    public function deleteRating($relationId)
    {
        /** @var SongRating $rating */
        $rating = $this->em->getDao(SongRating::getClassName())->find($relationId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_RATING',
                'message' => 'Song rating with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        $user = $this->getActiveSession()->user;
        if ($user !== $rating->user){
            $this->assumeAdmin();
        }

        $this->em->remove($rating);

        $this->notificationService->notify($rating->song->owner, 'deleted rating', $rating->song, $user);

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Creates song comment by song id.
     * @param int $id
     * @return Response Response with SongComment object.
     */
    public function createComment($id) {

        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $user = $this->getActiveSession()->user;
        if ($user !== $song->owner && !$song->public &&
            !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
            !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])){
                $this->assumeAdmin();
        }

        $data = $this->request->getData();

        $comment = new SongComment;

        $comment->user = $user;
        $comment->song = $song;
        $comment->created = new DateTime();
        $comment->modified = $comment->created;
        $comment->comment = $data['comment'];

        $this->em->persist($comment);

        if ($user !== $song->owner) {
            $this->notificationService->notify($song->owner, 'commented', $song, $user);
        }

        $this->em->flush();

        return Response::json([
            'id' => $comment->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }


    /**
     * Reads all song's comment.
     * @param int $id
     * @return Response Response with SongComment[] object
     */
    public function readAllComment($id)
    {
        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$song->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $song->owner &&
                !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
                !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])){
                    $this->assumeAdmin();
            }
        }

        $comments = $this->em->getDao(SongComment::getClassName())->findBy(['song' => $song]);

        $comments = array_map(function (SongComment $comment){
            return [
                'id'       => $comment->id,
                'comment'  => $comment->comment,
                'created'  => self::formatDateTime($comment->created),
                'modified' => self::formatDateTime($comment->modified),
                'username' => $comment->user->username
            ];
        }, $comments);

        return response::json($comments);
    }

    /**
     * Reads detailed information about comment.
     * @param int $id
     * @param int $relationId
     * @return Response Response with SongComment object
     */
    public function readComment($id, $relationId)
    {
        /** @var SongComment $comment */
        $comment = $this->em->getDao(SongComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_COMMENT',
                'message' => 'Song comment with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$comment->song->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $comment->song->owner &&
                !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $comment->song]) &&
                !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $comment->song])){
                    $this->assumeAdmin();
            }
        }

        return Response::json([
            'id'       => $comment->id,
            'comment'  => $comment->comment,
            'created'  => self::formatDateTime($comment->created),
            'modified' => self::formatDateTime($comment->modified),
            'username' => $comment->user->username
        ]);
    }

    /**
     * Updates existing song comment.
     * @param int $relationId
     * @return Response Response with SongComment object.
     */
    public function updateComment($id, $relationId)
    {
        $data = $this->request->getData();

        /** @var SongComment $comment */
        $comment = $this->em->getDao(SongComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_COMMENT',
                'message' => 'Song comment with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        $user = $this->getActiveSession()->user;
        if ($user !== $comment->user){
            $this->assumeAdmin();
        }

        $comment->comment = $data['comment'];
        $comment->modified = new DateTime();

        if ($user !== $comment->song->owner) {
            $this->notificationService->notify($comment->song->owner, 'updated comment', $comment->song, $user);
        }

        $this->em->flush();

        return Response::json([
            'id' => $comment->id
        ]);
    }

    /**
     * Deletes existing song comment.
     * @param int $relationId
     * @return Response blank.
     */
    public function deleteComment($id, $relationId)
    {

        /** @var SongComment $comment */
        $comment = $this->em->getDao(SongComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_COMMENT',
                'message' => 'Song comment with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        $user = $this->getActiveSession()->user;
        if ($user !== $comment->user){
            $this->assumeAdmin();
        }

        $this->em->remove($comment);

        if ($user !== $comment->song->owner) {
            $this->notificationService->notify($comment->song->owner, 'deleted comment', $comment->song, $user);
        }

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Creates song sharing by song id.
     * @param int $id
     * @return Response Response with SongSharing object.
     */
    public function createSharing($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $data = $this->request->getData();

        $user = $this->em->getDao(User::getClassName())->findOneBy(['username' => $data['user']]);

        if (!$user) {
            return Response::json([
                'error' => 'UNKNOWN_USER',
                'message' => 'User with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser !== $song->owner){
            throw new AuthorizationException;
        }

        if ($curUser == $user || $song->public ||
            $this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song])){
            return Response::json([
                'error' => 'DUPLICATE_SHARING',
                'message' => 'Song already shared with this user.'
            ])->setHttpStatus(Response::HTTP_CONFLICT);
        }

        $sharing = new SongSharing();

        $sharing->song = $song;
        $sharing->user = $user;

        $this->em->persist($sharing);

        $this->notificationService->notify($user, 'shared', $song, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $sharing->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

    /**
     * Creates song taking by song id.
     * @param int $id
     * @return Response Response with SongTaking object.
     */
    public function createTaking($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song || $song->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser == $song->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot take his own songs.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        else if(!($song->public ||
            $this->em->getDao(SongSharing::getClassName())->findBy(['user' => $curUser, 'song' => $song]))){
            throw new AuthorizationException;
        }

        $taking = new SongTaking();

        $taking->song = $song;
        $taking->user = $curUser;

        $this->em->persist($taking);

        $this->notificationService->notify($song->owner, 'taken', $song, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $taking->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

    /**
     * Sets song copy to null in song taking by song id and active user id.
     * @param int $id
     * @return Response
     */
    public function updateAllTaking($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;

        /** @var SongTaking $taking */
        $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $curUser, 'song' => $song]);

        if (!$taking) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_TAKING',
                'message' => 'Song taking with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if ($curUser !== $taking->user){
            $this->assumeAdmin();
        }

        $copy = $taking->songCopy;
        $taking->songCopy = null;
        $this->em->flush();

        if (!$this->em->getDao(SongTaking::getClassName())->findBy(['songCopy' => $copy])) { // zkontrolovat, jestli to neni posledni pouziti kopie
            $this->em->remove($copy);

            $this->em->flush();
        }

        return Response::json([
            'id' => $taking->id
        ]);
    }

    /**
     * Deletes song taking by song id and active user id.
     * @param int $id
     * @return Response
     */
    public function deleteAllTaking($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;

        /** @var SongTaking $taking */
        $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $curUser, 'song' => $song]);

        if (!$taking) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_TAKING',
                'message' => 'Active user does not have taken given song.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if ($curUser !== $taking->user){
            $this->assumeAdmin();
        }

        $this->em->remove($taking);

        if ($taking->songCopy != null) { // zkontrolovat, jestli to neni posledni pouziti kopie
            $copy = $taking->songCopy;
            $this->em->flush();

            if (!$this->em->getDao(SongTaking::getClassName())->findBy(['songCopy' => $copy])) {
                $this->em->remove($copy);
            }
        }

        foreach ($song->songbooks as $songsongbook) { // prochazim songbooky, kde tenhle song je
            if($songsongbook->songbook->owner != $taking->user){
                continue;
            }
            $this->removeSongFromSongbook($songsongbook);
            $song->removeSongbook($songsongbook);

        }

        $this->notificationService->notify($song->owner, 'canceled taking', $song, $curUser);

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Creates copy of song with given id.
     * @param int $id
     * @return Response Response with Song object.
     */
    public function createCopy($id)
    {
        $this->assumeLoggedIn();

        $song = $this->em->getDao(Song::getClassName())->find($id);

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser == $song->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot copy his own songs.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        if (!$song->public &&
            !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $curUser, 'song' => $song]) &&
            !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $curUser, 'song' => $song])){
            throw new AuthorizationException;
        }

        $newSong = new Song();
        $newSong->lyrics         = $song->lyrics;
        $newSong->chords         = $song->chords;
        $newSong->title          = $song->title;
        $newSong->album          = $song->album;
        $newSong->author         = $song->author;
        $newSong->originalAuthor = $song->originalAuthor;
        $newSong->year           = $song->year;
        $newSong->note           = $song->note;
        $newSong->owner          = $curUser;
        $newSong->public         = $song->public;
        $newSong->created        = new DateTime();
        $newSong->modified       = $song->created;
        $newSong->archived       = false;

        foreach ($song->tags as $tag) {
            if($tag->public){
                $_tag = new SongTag();
                $_tag->tag = $tag->tag;
                $_tag->public = $tag->public;
                $_tag->user = $curUser;
                $_tag->song = $newSong;
                $newSong->addTag($_tag);
                $this->em->persist($_tag);
            }
        }

        $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $curUser, 'song' => $song]);
        if($taking){
            $this->em->remove($taking); // odstranit prevzeti

            if ($taking->songCopy != null) { // zkontrolovat, jestli to neni posledni pouziti kopie
                $copy = $taking->songCopy;
                $this->em->flush();

                if (!$this->em->getDao(SongTaking::getClassName())->findBy(['songCopy' => $copy])) {
                    $this->em->remove($copy);
                }
            }

            foreach ($song->tags as $tag) {     // presunout soukr. tagy
                if($tag->user == $curUser){
                    $song->removeTag($tag);
                    $tag->song = $newSong;
                    $newSong->addTag($tag);
                }
            }
            foreach ($song->songbooks as $songsongbook) {     // presunout vlastní zpěvníky
                if($songsongbook->songbook->owner == $curUser){
                    $song->removeSongbook($songsongbook);
                    $songsongbook->song = $newSong;
                    $newSong->addSongbook($songsongbook);
                }
            }
        }

        $this->em->persist($newSong);

        $this->notificationService->notify($song->owner, 'copied', $song, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $newSong->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

}
