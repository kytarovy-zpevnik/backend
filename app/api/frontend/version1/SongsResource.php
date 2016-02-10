<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Song;
use App\Model\Entity\SongRating;
use App\Model\Entity\Songbook;
use App\Model\Entity\SongSongbook;
use App\Model\Entity\SongComment;
use App\Model\Entity\SongSharing;
use App\Model\Entity\SongTaking;
use App\Model\Entity\User;
use App\Model\Entity\Wish;
use App\Model\Entity\Notification;
use App\Model\Entity\SongTag;
use App\Model\Query\SongAdvSearchQuery;
use App\Model\Query\SongSearchQuery;
use App\Model\Query\SongPublicSearchQuery;
use App\Model\Service\SessionService;
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

    /** @var EntityManager */
    private $em;

	/** @var SongService */
	private $songService;

	/**
	 * @param SessionService $sessionService
	 * @param EntityManager $em
	 * @param SongService $songService
	 */
    public function __construct(SessionService $sessionService, EntityManager $em, SongService $songService)
    {
        parent::__construct($sessionService);

		$this->em          = $em;
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

        if ($songFromId = $this->request->getQuery('takenFrom')) {
            /** @var Song $songFrom */
            $songFrom = $this->em->getDao(Song::getClassName())->find($songFromId); // tohle bude chtít upravit při kopírování smazaného
            $userFrom = $this->em->getDao(User::getClassName())->findOneBy(['username' => $songFrom->owner->username]);
            $copyNotification = new Notification();
            $copyNotification->user = $userFrom;
            $copyNotification->created = new DateTime();
            $copyNotification->read = false;
            $copyNotification->song = $songFrom;
            $copyNotification->text = 'Vaše píseň "'.$songFrom->title.'" byla zkopírována uživatelem "'.$this->getActiveSession()->user->username.'".';
            $this->em->persist($copyNotification);

            $taking = $this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $this->getActiveSession()->user, 'song' => $songFrom]);
            if($taking){
                $this->em->remove($taking);
                foreach ($songFrom->tags as $tag) {
                    if($tag->user == $this->getActiveSession()->user){
                        $songFrom->removeTag($tag);
                        $this->em->remove($tag);
                    }
                }
            }
        }

        if ($song->public) {
            /** @var Wish[] $wishes */
            $wishes = $this->em->getDao(Wish::getClassName())->findBy(['name' => $song->title, 'interpret' => $song->author]);
            foreach ($wishes as $wish) {
                if ($wish->user != $song->owner) {
                    $notification = new Notification();
                    $notification->user = $wish->user;
                    $notification->created = new DateTime();
                    $notification->read = false;
                    $notification->song = $song;
                    $notification->text = '"'.$song->title.'" Píseň, která by se Vám mohla líbit.';
                    $this->em->persist($notification);
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
        else if ((!$public && count($this->request->getQuery()) > 0) || count($this->request->getQuery()) > 1) {
            $title  = $this->request->getQuery('title');
            $album  = $this->request->getQuery('album');
            $author = $this->request->getQuery('author');
            $tag    = $this->request->getQuery('tag');
            $songs  = $this->em->getDao(Song::getClassName())
                ->fetch(new SongAdvSearchQuery($user, $title, $album, $author, $tag, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else {
            $songs = $this->em->getDao(Song::getClassName())->findBy($findBy, ['title' => 'ASC']);
            if (!$public){
                $takenSongs = $this->em->getDao(SongTaking::getClassName())
                    ->findBy(['user' => $user]);
                $takenSongs = array_map(function(SongTaking $taking){
                    return $taking->song;
                }, $takenSongs);
                $songs = array_merge($songs, $takenSongs);
            }
        }

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

            $averageRating = $this->getAverageRating($song);

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
                'rating'          => $averageRating
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
            if($action == 'tags'){
                $this->updateTags($song, $data['tags']);
                $this->em->flush();
                return Response::blank();
            }
            if($action == 'songbooks'){
                $this->updateSongbooks($song, $data['songbooks']);
                $this->em->flush();
                return Response::blank();
            }
        }

		if ($user !== $song->owner) {
            $this->assumeAdmin();
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

        if($this->getActiveSession()->user !== $song->owner){
            $this->assumeAdmin();
        }

        if($song->archived == true)
            $song->archived = false;
        else{
            foreach ($song->songbooks as $songbook) { // tohle asi nebude úplně supr ;)
                $this->em->remove($songbook);
            }
            $song->archived = true;
        }
        $song->modified = new DateTime();

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

        $songbooks = $this->em->getDao(Songbook::getClassName())->findBy(['id' => $ids]);

        foreach ($song->songbooks as $songsongbook) {
            $keepit = false;
            foreach($songbooks as $songbook){
                if($songsongbook->songbook->id == $songbook->id){
                    $keepit = true;
                    break;
                }
            }
            if($keepit){
                continue;
            }
            $this->em->remove($songsongbook);
            $this->em->flush($songsongbook);
            $othersongs = $this->em->getDao(SongSongbook::getClassName())->findBy(['songbook' => $songsongbook->songbook], ['position' => 'ASC']);

            foreach ($othersongs as $other){
                if($other->position > $songsongbook->position){
                    $other->position -= 1;
                    $this->em->persist($other);
                }
            }
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
     * Counts average rating for given song
     * @param Song $song
     * @return int
     */
    private function getAverageRating(Song $song)
    {
        $ratings = $this->em->getDao(SongRating::getClassName())
            ->findBy(['song' => $song]);

        $average = 0;

        foreach ($ratings as & $rating) {
            $average += $rating->rating;
        }

        if(count($ratings) > 0)
            $average /= count($ratings);

        return [
            'rating'      => $average,
            'numOfRating' => count($ratings)
        ];
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

        if (!$song || $song->archived) {
            return FALSE;
        }

        if (!$song->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
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

        $averageRating = $this->getAverageRating($song);

        $taken = false;
        if($session && $this->em->getDao(SongTaking::getClassName())->findBy(['user' => $session->user, 'song' => $song])){
            $taken = true;
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
            'songbooks'      => $songbooks,
            'username'       => $song->owner->username,
            'tags'           => $tags,
            'rating'         => $averageRating,
            'taken'          => $taken
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

        $notification = new Notification();
        $notification->user = $song->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->song = $song;
        $notification->text = 'Uživatel "'.$user->username.'" ohodnotil Vaši píseň "'.$song->title.'".';
        $this->em->persist($notification);

        $this->em->flush();

        return Response::json([
            'id' => $rating->id
        ]);
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

        $notification = new Notification();
        $notification->user = $rating->song->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->song = $rating->song;
        $notification->text = 'Uživatel "'.$user->username.'" upravil hodnocení Vaší písně "'.$rating->song->title.'".';
        $this->em->persist($notification);

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

        if ($this->getActiveSession()->user !== $rating->user) {
            $this->assumeAdmin();
        }

        $this->em->remove($rating);

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
            $notification = new Notification();
            $notification->user = $song->owner;
            $notification->created = new DateTime();
            $notification->read = false;
            $notification->song = $song;
            $notification->text = 'Uživatel "'.$user->username.'" okomentoval Vaši píseň "'.$song->title.'".';
            $this->em->persist($notification);
        }

        $this->em->flush();

        return Response::json([
            'id' => $comment->id
        ]);
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
            $notification = new Notification();
            $notification->user = $comment->song->owner;
            $notification->created = new DateTime();
            $notification->read = false;
            $notification->song = $comment->song;
            $notification->text = 'Uživatel "' . $user->username . '" upravil komentář u Vaší písně "' . $comment->song->title . '".';
            $this->em->persist($notification);
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

        if ($this->getActiveSession()->user !== $comment->user){
            $this->assumeAdmin();
        }

        $this->em->remove($comment);

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

        $user = $this->em->getDao(User::getClassName())->find($data['user']);

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

        $notification = new Notification();
        $notification->user = $user;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->song = $song;
        $notification->text = 'Uživatel "'.$curUser->username.'" s vámi sdílel píseň "'.$song->title.'".';
        $this->em->persist($notification);

        $this->em->flush();

        return Response::json([
            'id' => $sharing->id
        ]);
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

        $notification = new Notification();
        $notification->user = $song->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->song = $song;
        $notification->text = 'Uživatel "'.$curUser->username.'" převzal vaši píseň "'.$song->title.'".';
        $this->em->persist($notification);

        $this->em->flush();

        return Response::json([
            'id' => $taking->id
        ]);
    }

}
