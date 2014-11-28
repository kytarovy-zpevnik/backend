<?php

namespace FrontendApi\Version1;

use App\Model\Entity\Song;
use App\Model\Entity\SongRating;
use App\Model\Entity\Songbook;
use App\Model\Entity\SongComment;
use App\Model\Entity\SongSharing;
use App\Model\Entity\User;
use App\Model\Entity\Wish;
use App\Model\Entity\Notification;
use App\Model\Entity\SongTag;
use App\Model\Query\SongAdvSearchQuery;
use App\Model\Query\SongSearchQuery;
use App\Model\Query\SongPublicSearchQuery;
use App\Model\Service\SessionService;
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
     * @return Response
     */
	public function create()
	{
		$this->assumeLoggedIn();

		$data = $this->request->getData();

		$song = new Song();

		$ids = array_map(function ($songbook) {
			return $songbook['id'];
		}, $data['songbooks']);

		$songbooks = $this->em->getDao(Songbook::getClassName())->findBy(['id' => $ids]);

		foreach ($songbooks as $songbook) {
			$song->addSongbook($songbook);
		}

        $tags = array_map(function ($tag) {
            return $tag['tag'];
        }, $data['tags']);

        foreach ($tags as $tag) {
            $_tag = new SongTag();
            $_tag->tag = $tag;
            $_tag->song = $song;
            $song->addTag($_tag);
            $this->em->persist($_tag);
        }

		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
		$song->lyrics         = $data['lyrics'];
		$song->chords         = $data['chords'];
        $song->note           = $data['note'];
		$song->owner          = $this->getActiveSession()->user;
		$song->public         = $data['public'];
        $song->created        = new DateTime();
        $song->modified       = $song->created;

		$this->em->persist($song);

        if ($songFromId = $this->request->getQuery('takenFrom')) {
            /** @var Song $songFrom */
            $songFrom = $this->em->getDao(Song::class)->find($songFromId);
            $userFrom = $this->em->getDao(User::class)->findOneBy(['username' => $songFrom->owner->username]);
            $takenSongNotification = new Notification();
            $takenSongNotification->user = $userFrom;
            $takenSongNotification->created = new DateTime();
            $takenSongNotification->read = false;
            $takenSongNotification->song = $songFrom;
            $takenSongNotification->text = 'Vaše píseň "'.$songFrom->title.'" byla převzata uživatelem "'.$this->getActiveSession()->user->username.'".';
            $this->em->persist($takenSongNotification);
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
                    $notification->text = "Píseň, která by se Vám mohla líbit.";
                    $this->em->persist($notification);
                }
            }
        }

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
		$song = $this->em->getDao(Song::getClassName())->find($id);

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

		$ids = array_map(function ($songbook) {
			return $songbook['id'];
		}, $data['songbooks']);

		$songbooks = $this->em->getDao(Songbook::getClassName())->findBy(['id' => $ids]);

		$song->clearSongbooks();
		foreach ($songbooks as $songbook) {
			$song->addSongbook($songbook);
		}

        $tags = array_map(function ($tag) {
            return $tag['tag'];
        }, $data['tags']);


        foreach ($song->tags as $tag) {
            $this->em->remove($tag);
        }
        $song->clearTags();
        foreach ($tags as $tag) {
            $_tag = new SongTag();
            $_tag->tag = $tag;
            $_tag->song = $song;
            $song->addTag($_tag);
            $this->em->persist($_tag);
        }

		$song->title          = $data['title'];
		$song->album          = $data['album'];
		$song->author         = $data['author'];
		$song->originalAuthor = $data['originalAuthor'];
		$song->year           = $data['year'];
		$song->lyrics         = $data['lyrics'];
		$song->chords         = $data['chords'];
        $song->note           = $data['note'];
		$song->owner          = $this->getActiveSession()->user;
		$song->public         = $data['public'];
        $song->modified       = new DateTime();

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
		$song = $this->em->getDao(Song::getClassName())->find($id);

		if (!$song) {
			return Response::json([
				'error' => 'UNKNOWN_SONG',
				'message' => 'Song with given id not found.'
			])->setHttpStatus(Response::HTTP_NOT_FOUND);
		}

		if (!$song->public) {
			$this->assumeLoggedIn();

			if ($this->getActiveSession()->user !== $song->owner
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $this->getActiveSession()->user, 'song' => $song])) {
				throw new AuthorizationException;
			}
		}

		$songbooks = array_map(function (Songbook $songbook) {
			return [
				'id'   => $songbook->id,
				'name' => $songbook->name,
                'note' => $songbook->note
			];
		}, $song->songbooks);

        $tags = array_map(function (SongTag $tag) {
            return [
                'tag' => $tag->tag
            ];
        }, $song->tags);

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
            'tags'           => $tags
		]);
	}

    /**
	 * Returns brief information about all user's songs.
     * @return Response
     */
    public function readAll()
    {
        $this->assumeLoggedIn(); // only logged can list his songs

		if ($search = $this->request->getQuery('search')) {
			$songs = $this->em->getDao(Song::getClassName())
				->fetch(new SongSearchQuery($this->getActiveSession()->user, $search))
				->getIterator()
				->getArrayCopy();

		}
        else if ($search = $this->request->getQuery('searchPublic')) {
            $songs = $this->em->getDao(Song::class)
                ->fetch(new SongPublicSearchQuery($search))
                ->getIterator()
                ->getArrayCopy();
        }
        else if ($this->request->getQuery('searchAllPublic', FALSE)) {
            $songs = $this->em->getDao(Song::class)->findBy(["public" => 1]);
        }
        else if (count($this->request->getQuery()) > 0) {
            $title  = $this->request->getQuery('title');
            $album  = $this->request->getQuery('album');
            $author = $this->request->getQuery('author');
            $tag    = $this->request->getQuery('tag');
            $songs  = $this->em->getDao(Song::getClassName())
                ->fetch(new SongAdvSearchQuery($this->getActiveSession()->user, $title, $album, $author, $tag))
                ->getIterator()
                ->getArrayCopy();

        } else {
			$songs = $this->em->getDao(Song::getClassName())
				->findBy(['owner' => $this->getActiveSession()->user], ['title' => 'ASC']);
		}

        $songs = array_map(function (Song $song){

            $tags = array_map(function(SongTag $tag){
                return ['tag' => $tag->tag];
            }, $song->tags);

            return [
                'id'              => $song->id,
                'title'           => $song->title,
                'album'           => $song->album,
                'author'          => $song->author,
                'originalAuthor'  => $song->originalAuthor,
                'year'            => $song->year,
                'note'            => $song->note,
                'public'          => $song->public,
                'username'        => $song->owner->username,
                'tags'            => $tags
            ];
        }, $songs);

        return response::json($songs);
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

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }


        if (($this->getActiveSession()->user == $song->owner) || (!$song->public
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $this->getActiveSession()->user, 'song' => $song]))){
            throw new AuthorizationException;
        }

        $data = $this->request->getData();

        $rating = new SongRating;

        $rating->user = $this->getActiveSession()->user;
        $rating->song = $song;
        $rating->created = new DateTime();
        $rating->modified = $rating->created;
        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];

        $this->em->persist($rating);
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

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }
        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;

        if(!$song->public) {

            if ($user !== $song->owner
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $user, 'song' => $song])){
                throw new AuthorizationException;
            }
        }

        if ($this->request->getQuery('checkRated', FALSE)) {
            $ratings = $this->em->getDao(SongRating::getClassName())->findBy(['user' => $user, 'song' => $song]);
        }
        else {
            $ratings = $this->em->getDao(SongRating::getClassName())
                ->findBy(['song' => $song]);
        }

        $ratings = array_map(function (SongRating $rating){
            return [
                'id'       => $rating->id,
                'comment'  => $rating->comment,
                'rating'   => $rating->rating,
                'created'  => self::formatDateTime($rating->created),
                'modified' => self::formatDateTime($rating->modified)
            ];
        }, $ratings);

        return response::json($ratings);
    }


    /**
     * Reads detailed information about rating.
     * @param int $relationId
     * @return Response
     */
    public function readRating($id, $relationId)
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
        if(!$rating->song->public) {


            if ($this->getActiveSession()->user !== $rating->song->owner
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $this->getActiveSession()->user, 'song' => $rating->song])){
                throw new AuthorizationException;
            }
        }

        return Response::json([
            'id'       => $rating->id,
            'comment'  => $rating->comment,
            'rating'   => $rating->rating,
            'created'  => self::formatDateTime($rating->created),
            'modified' => self::formatDateTime($rating->modified)
        ]);
    }


    /**
     * Updates existing song rating.
     * @param int $relationId
     * @return Response Response with SongRating object.
     */
    public function updateRating($id, $relationId)
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

        if ($this->getActiveSession()->user !== $rating->user){
            throw new AuthorizationException;
        }

        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];
        $rating->modified = new DateTime();

        $this->em->flush();

        return Response::json([
            'id' => $rating->id
        ]);
    }

    /**
     * Delete song rating.
     * @param int $ratingId
     * @return Response
     */
    public function deleteRating($ratingId)
    {
        /** @var SongRating $rating */
        $rating = $this->em->getDao(SongRating::getClassName())->find($ratingId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_RATING',
                'message' => 'Song rating with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        if ($this->getActiveSession()->user !== $rating->user) {
            throw new AuthorizationException;
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

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }


        if (($this->getActiveSession()->user !== $song->owner) && (!$song->public)
            && !$this->em->getDao(SongSharing::class)->findBy(['user' => $this->getActiveSession()->user, 'song' => $song])){
            throw new AuthorizationException;
        }

        $data = $this->request->getData();

        $comment = new SongComment;

        $comment->user = $this->getActiveSession()->user;
        $comment->song = $song;
        $comment->created = new DateTime();
        $comment->modified = $comment->created;
        $comment->comment = $data['comment'];

        $this->em->persist($comment);
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

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;

        if(!$song->public) {

            if ($user !== $song->owner
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $user, 'song' => $song])){
                throw new AuthorizationException;
            }
        }

        if ($this->request->getQuery('usersComment', FALSE)) {
            $comments = $this->em->getDao(SongComment::getClassName())->findBy(['user' => $user, 'song' => $song]);
        }
        else {
            $comments = $this->em->getDao(SongComment::getClassName())
                ->findBy(['song' => $song]);
        }


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

        $this->assumeLoggedIn();
        if(!$comment->song->public) {

            if ($this->getActiveSession()->user !== $comment->song->owner
                && !$this->em->getDao(SongSharing::class)->findBy(['user' => $this->getActiveSession()->user, 'song' => $comment->song])){
                throw new AuthorizationException;
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

        if ($this->getActiveSession()->user !== $comment->user){
            throw new AuthorizationException;
        }

        $comment->comment = $data['comment'];
        $comment->modified = new DateTime();

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
        $comment = $this->em->getDao(SongComment::class)->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONG_COMMENT',
                'message' => 'Song comment with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        $user = $this->getActiveSession()->user;
        if (($user !== $comment->user) && ($user->role->slug !== 'admin')){
            throw new AuthorizationException;
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

        $song = $this->em->getDao(Song::class)->find($id);

        if (!$song) {
            return Response::json([
                'error' => 'UNKNOWN_SONG',
                'message' => 'Song with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $data = $this->request->getData();

        $user = $this->em->getDao(User::class)->find($data['user']);

        if (!$user) {
            return Response::json([
                'error' => 'UNKNOWN_USER',
                'message' => 'User with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if ($this->getActiveSession()->user !== $song->owner){
            throw new AuthorizationException;
        }

        if ($this->getActiveSession()->user == $user || $this->em->getDao(SongSharing::class)->findBy(['user' => $user, 'song' => $song])){
            return Response::json([
                'error' => 'DUPLICATE_SHARING',
                'message' => 'Song already shared with this user.'
            ])->setHttpStatus(Response::HTTP_CONFLICT);
        }

        $sharing = new SongSharing();

        $sharing->song = $song;
        $sharing->user = $user;
        $sharing->editable = $data['editable'];

        $this->em->persist($sharing);

        $notification = new Notification();
        $notification->user = $user;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->song = $song;
        $notification->text = "Uživatel s vámi sdílel píseň.";
        $this->em->persist($notification);

        $this->em->flush();

        return Response::json([
            'id' => $sharing->id
        ]);
    }

}
