<?php

namespace FrontendApi\Version1;


use App\Model\Entity\Notification;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Entity\SongbookRating;
use App\Model\Entity\SongbookComment;
use App\Model\Entity\SongbookSharing;
use App\Model\Entity\SongbookTag;
use App\Model\Entity\User;
use App\Model\Query\SongbookSearchQuery;
use App\Model\Service\SessionService;
use FrontendApi\FrontendResource;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Routing\AuthorizationException;
use Nette\Utils\DateTime;

/**
 * Resource for Songbook CRUD operations.
 * @author	Jiří Mantlík
 * @author  Pavel Peroutka
 */
class SongbooksResource extends FrontendResource {
    /** @var EntityManager */
    private $em;

    public function __construct(SessionService $sessionService, EntityManager $em)
    {
        parent::__construct($sessionService);

        $this->em = $em;
    }


    public function create()
    {
        $this->assumeLoggedIn();

        $data = $this->request->getData();

        /** @var Songbook */
        $songbook = new Songbook();

        $tags = array_map(function ($tag) {
            return $tag['tag'];
        }, $data['tags']);

        foreach ($tags as $tag) {
            $_tag = new SongbookTag();
            $_tag->tag = $tag;
            $_tag->songbook = $songbook;
            $songbook->addTag($_tag);
            $this->em->persist($_tag);
        }

        $songbook->name = $data['name'];
        $songbook->created = new DateTime();
        $songbook->modified = new DateTime();
        $songbook->archived = false;
        $songbook->public = $data['public'];
        $songbook->owner = $this->getActiveSession()->user;
        $songbook->note = $data['note'];

        $this->em->persist($songbook);
        $this->em->flush();

        return Response::json([
            'id' => $songbook->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

    /**
     * Reads detailed information about songbook.
     * @param int $id
     * @return Response
     */
    public function read($id)
    {
        /** @var Songbook */
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$songbook->public){
            $this->assumeLoggedIn();

            if($this->getActiveSession()->user !== $songbook->owner
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $this->getActiveSession()->user, 'songbook' => $songbook])){
                throw new AuthorizationException;
            }
        }

        $tags = array_map(function (SongbookTag $tag) {
            return [
                'tag' => $tag->tag
            ];
        }, $songbook->tags);

        $songs = array_map(function (Song $song){
            return [
                'id'             => $song->id,
                'title'          => $song->title,
                'album'          => $song->album,
                'author'         => $song->author,
                'originalAuthor' => $song->originalAuthor,
                'year'           => $song->year,
                'public'         => $song->public,
                'username'       => $song->owner->username
            ];
        }, $songbook->songs);

        return Response::json([
            'id'       => $songbook->id,
            'name'     => $songbook->name,
            'note'     => $songbook->note,
            'songs'    => $songs,
            'public'   => $songbook->public,
            'username' => $songbook->owner->username,
            'tags'     => $tags
        ]);
    }

    /**
     * Returns brief information about all user's songbooks.
     * @return Response
     */
    public function readAll()
    {

		if ($search = $this->request->getQuery('search')) {
            $this->assumeLoggedIn(); // only logged can list his songbooks
			$songbooks = $this->em->getDao(Songbook::getClassName())
				->fetch(new SongbookSearchQuery($this->getActiveSession()->user, $search))
				->getIterator()
				->getArrayCopy();

		}
        else if ($search = $this->request->getQuery('searchPublic')) {
            if($search == ' '){
                $songbooks = $this->em->getDao(Songbook::getClassName())->findBy(["public" => 1], ['name' => 'ASC']);
            }
            /*else{
                $songbooks = $this->em->getDao(Songbook::getClassName())
                    ->fetch(new SongPublicSearchQuery($search))
                    ->getIterator()
                    ->getArrayCopy();
            }*/
        }
        else if ($this->request->getQuery('randomPublic')) {
            $songbooks = $this->em->getDao(Songbook::getClassName())->findBy(["public" => 1], ['name' => 'ASC']);
            if(sizeof($songbooks) > 1){
                $keys = array_rand ($songbooks, (8 < sizeof($songbooks) ? 8 : sizeof($songbooks)));

                while (list($k, $v) = each($keys))
                {
                    $randSongbooks[] = $songbooks[$v];
                }
                $songbooks = $randSongbooks;
            }
        }
        else if($this->request->getQuery('admin')){
            $this->assumeAdmin();
            $this->em->getFilters()->disable('DeletedFilter');
            $songbooks = $this->em->getDao(Songbook::getClassName())
                ->findBy(array(), ['id' => 'ASC']);
            $this->em->getFilters()->enable('DeletedFilter');
        }
        else {
            $this->assumeLoggedIn(); // only logged can list his songbooks
			$songbooks = $this->em->getDao(Songbook::getClassName())
				->findBy(['owner'=>$this->getActiveSession()->user], ['name' => 'ASC']);
		}

        $songbooks = array_map(function (Songbook $songbook){
            $tags = array_map(function (SongbookTag $tag) {
                return [
                    'tag' => $tag->tag
                ];
            }, $songbook->tags);

            return [
                'id'       => $songbook->id,
                'name'     => $songbook->name,
                'note'     => $songbook->note,
                'public'   => $songbook->public,
                'archived' => $songbook->archived,
                'username' => $songbook->owner->username,
                'tags'     => $tags
            ];
        }, $songbooks);

        return response::json($songbooks);
    }

    /**
     * Updates Songbook by id.
     * @param $id
     */
    public function update($id)
    {
        $data = $this->request->getData();

        $this->assumeLoggedIn();

        /** @var Songbook */
        $this->em->getFilters()->disable('DeletedFilter');
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);
        $this->em->getFilters()->enable('DeletedFilter');

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if ($this->getActiveSession()->user !== $songbook->owner) {
            $this->assumeAdmin();
        }

        $tags = array_map(function ($tag) {
            return $tag['tag'];
        }, $data['tags']);

        foreach ($songbook->tags as $tag) {
            $this->em->remove($tag);
        }
        $songbook->clearTags();
        foreach ($tags as $tag) {
            $_tag = new SongbookTag();
            $_tag->tag = $tag;
            $_tag->songbook = $songbook;
            $songbook->addTag($_tag);
            $this->em->persist($_tag);
        }

        $songbook->name = $data['name'];
        $songbook->note = $data['note'];
        $songbook->public = $data['public'];
        $songbook->archived = $data['archived'];
        $songbook->modified = new DateTime();

        $this->em->flush();
    }

    /**
     * Deletes Songbook by id.
     * @param $id
     */
    public function delete($id)
    {

        $this->assumeLoggedIn();

        /** @var Songbook */
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if($this->getActiveSession()->user !== $songbook->owner){
            $this->assumeAdmin();
        }

        $songbook->archived = true;
        $songbook->modified = new DateTime();

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Creates songbook rating by songbook id.
     * @param $id
     * @return Response Response with SongbookRating object.
     */
    public function createRating($id)
    {
        $this->assumeLoggedIn();

        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }


        if (($this->getActiveSession()->user == $songbook->owner) || (!$songbook->public
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $this->getActiveSession()->user, 'songbook' => $songbook]))){
            throw new AuthorizationException;
        }

        $data = $this->request->getData();

        $rating = new SongbookRating;

        $rating->user = $this->getActiveSession()->user;
        $rating->songbook = $songbook;
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


    /**songbook
     * Reads all songbook's ratings.
     * @param int $id
     * @return Response
     */
    public function readAllRating($id)
    {
        /** @var SongbookRating $rating */
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $user = null;

        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;

        if(!$songbook->public) {

            if ($user !== $songbook->owner
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
                throw new AuthorizationException;
            }
        }

        if ($this->request->getQuery('checkRated', FALSE)) {
            $ratings = $this->em->getDao(SongbookRating::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]);
        }
        else {
            $ratings = $this->em->getDao(SongbookRating::getClassName())
                ->findBy(['songbook'=> $songbook]);
        }


        $ratings = array_map(function (SongbookRating $rating){
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
        /** @var SongbookRating $rating */
        $rating = $this->em->getDao(SongbookRating::getClassName())->find($relationId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_RATING',
                'message' => 'Songbook rating with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();

        if(!$rating->songbook->public) {


            if ($this->getActiveSession()->user !== $rating->songbook->owner
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $this->getActiveSession()->user, 'songbook' => $rating->songbook])){
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
     * Updates existing songbook rating.
     * @param int $relationId
     * @return Response Response with SongbookRating object.
     */
    public function updateRating($id, $relationId)
    {
        $data = $this->request->getData();

        /** @var SongbookRating $rating */
        $rating = $this->em->getDao(SongbookRating::getClassName())->find($relationId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_RATING',
                'message' => 'Songbook rating with given id not found.'
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
     * Delete songbook rating.
     * @param int $ratingId
     * @return Response
     */
    public function deleteRating($ratingId)
    {
        /** @var SongbookRating $rating */
        $rating = $this->em->getDao(SongbookRating::getClassName())->find($ratingId);

        if (!$rating) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_RATING',
                'message' => 'Songbook rating with given id not found.'
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
     * Creates songbook comment by songbook id.
     * @param int $id
     * @return Response Response with SongbookComment object.
     */
    public function createComment($id) {

        $this->assumeLoggedIn();

        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }


        if (($this->getActiveSession()->user !== $songbook->owner) && (!$songbook->public)
            && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $this->getActiveSession()->user, 'songbook' => $songbook])){
            throw new AuthorizationException;
        }

        $data = $this->request->getData();

        $comment = new SongbookComment;

        $comment->user = $this->getActiveSession()->user;
        $comment->songbook = $songbook;
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
     * Reads all songbook's comment.
     * @param int $id
     * @return Response Response with SongbookComment[] object
     */
    public function readAllComment($id)
    {
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;

        if(!$songbook->public) {

            if ($user !== $songbook->owner
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
                throw new AuthorizationException;
            }
        }

        if ($this->request->getQuery('usersComment', FALSE)) {
            $comments = $this->em->getDao(SongbookComment::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]);
        }
        else {
            $comments = $this->em->getDao(SongbookComment::getClassName())
                ->findBy(['songbook' => $songbook]);
        }


        $comments = array_map(function (SongbookComment $comment){
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
     * @return Response Response with SongbookComment object
     */
    public function readComment($id, $relationId)
    {
        /** @var SongbookComment $comment */
        $comment = $this->em->getDao(SongbookComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_COMMENT',
                'message' => 'Songbook comment with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }
        $this->assumeLoggedIn();

        if(!$comment->songbook->public) {

            if ($this->getActiveSession()->user !== $comment->songbook->owner
                && !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $this->getActiveSession()->user, 'songbook' => $comment->songbook])){
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
     * Updates existing songbook comment.
     * @param int $relationId
     * @return Response Response with SongbookComment object.
     */
    public function updateComment($id, $relationId)
    {
        $data = $this->request->getData();

        /** @var SongbookComment $comment */
        $comment = $this->em->getDao(SongbookComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_COMMENT',
                'message' => 'Songbook comment with given id not found.'
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
     * Deletes existing songbook comment.
     * @param int $relationId
     * @return Response blank.
     */
    public function deleteComment($id, $relationId)
    {
        $data = $this->request->getData();

        /** @var SongbookComment $comment */
        $comment = $this->em->getDao(SongbookComment::getClassName())->find($relationId);

        if (!$comment) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_COMMENT',
                'message' => 'Songbook comment with given id not found.'
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
     * Creates songbook sharing by songbook id.
     * @param int $id
     * @return Response Response with SongbookSharing object.
     */
    public function createSharing($id)
    {
        $this->assumeLoggedIn();

        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
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

        if ($this->getActiveSession()->user !== $songbook->owner){
            throw new AuthorizationException;
        }

        if ($this->getActiveSession()->user == $user || $this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
            return Response::json([
                'error' => 'DUPLICATE_SHARING',
                'message' => 'Songbook already shared with this user.'
            ])->setHttpStatus(Response::HTTP_CONFLICT);
        }

        $sharing = new SongbookSharing();

        $sharing->songbook = $songbook;
        $sharing->user = $user;
        $sharing->editable = $data['editable'];

        $this->em->persist($sharing);

        $notification = new Notification();
        $notification->user = $user;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $songbook;
        $notification->text = "Uživatel s vámi sdílel zpěvník.";
        $this->em->persist($notification);

        $this->em->flush();

        return Response::json([
            'id' => $sharing->id
        ]);
    }



} 