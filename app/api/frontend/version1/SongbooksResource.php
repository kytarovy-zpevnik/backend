<?php

namespace FrontendApi\Version1;


use App\Model\Entity\Notification;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Entity\SongSongbook;
use App\Model\Entity\SongbookRating;
use App\Model\Entity\SongbookComment;
use App\Model\Entity\SongbookSharing;
use App\Model\Entity\SongbookTaking;
use App\Model\Entity\SongbookTag;
use App\Model\Entity\SongTag;
use App\Model\Entity\SongSharing;
use App\Model\Entity\SongTaking;
use App\Model\Entity\User;
use App\Model\Query\SongbookSearchQuery;
use App\Model\Query\SongbookAdvSearchQuery;
use App\Model\Service\SessionService;
use App\Model\Service\NotificationService;
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

    /**
     * @param SessionService $sessionService
     * @param NotificationService $notificationService
     * @param EntityManager $em
     */
    public function __construct(SessionService $sessionService, NotificationService $notificationService, EntityManager $em)
    {
        parent::__construct($sessionService, $notificationService, $em);
    }


    public function create()
    {
        $this->assumeLoggedIn();

        $data = $this->request->getData();

        /** @var Songbook */
        $songbook = new Songbook();

        $curUser = $this->getActiveSession()->user;

        $tags = array_map(function ($tag) {
            $_tag = new SongbookTag();
            $_tag->tag = $tag['tag'];
            $_tag->public = $tag['public'];
            return $_tag;
        }, $data['tags']);

        foreach ($tags as $tag) {
            $tag->songbook = $songbook;
            $tag->user = $curUser;
            $songbook->addTag($tag);
            $this->em->persist($tag);
        }

        $songbook->name = $data['name'];
        $songbook->created = new DateTime();
        $songbook->modified = new DateTime();
        $songbook->archived = false;
        $songbook->public = $data['public'];
        $songbook->owner = $curUser;
        $songbook->note = $data['note'];

        $this->em->persist($songbook);

        $this->em->flush();

        return Response::json([
            'id' => $songbook->id
        ])->setHttpStatus(Response::HTTP_CREATED);
    }

    /**
     * Returns brief information about all user's songbooks.
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

        if($this->request->getQuery('admin')){
            $this->assumeAdmin();
            $songbooks = $this->em->getDao(Songbook::getClassName())
                ->findBy(array(), ['id' => 'ASC']);
        }
        else if ($this->request->getQuery('random')) {
            $songbooks = $this->em->getDao(Songbook::getClassName())->findBy($findBy, ['name' => 'ASC']);
            if(sizeof($songbooks) > 1){
                $keys = array_rand ($songbooks, (8 < sizeof($songbooks) ? 8 : sizeof($songbooks)));

                $randSongbooks = array();
                while (list($k, $v) = each($keys))
                {
                    $randSongbooks[] = $songbooks[$v];
                }
                $songbooks = $randSongbooks;
            }
        }
        else if ($search = $this->request->getQuery('search')) {
            $songbooks = $this->em->getDao(Songbook::getClassName())
                ->fetch(new SongbookSearchQuery($user, $search, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else if ($this->request->getQuery('name') ||
                  $this->request->getQuery('owner') || $this->request->getQuery('tag')) {
            $name  = $this->request->getQuery('name');
            $owner  = $this->request->getQuery('owner');
            $tag    = $this->request->getQuery('tag');
            $songbooks  = $this->em->getDao(Songbook::getClassName())
                ->fetch(new SongbookAdvSearchQuery($user, $name, $owner, $tag, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else {
            $songbooks = $this->em->getDao(Songbook::getClassName())->findBy($findBy);
            if (!$public && !$this->request->getQuery('justOwned')){
                $takenSongbooks = $this->em->getDao(SongbookTaking::getClassName())
                    ->findBy(['user' => $user]);
                $takenSongbooks = array_map(function(SongbookTaking $taking){
                    return $taking->songbook;
                }, $takenSongbooks);
                $songbooks = array_merge($songbooks, $takenSongbooks);
            }
        }

        @usort($songbooks, function(Songbook $a, Songbook $b){
            $sort = $this->request->getQuery('sort');

            if($this->request->getQuery('order') == 'desc') {
                $c = $a;
                $a = $b;
                $b = $c;
            }

            switch($sort){
                case 'name':
                    return strcasecmp($a->name, $b->name);
                    break;
                case 'numOfSongs':
                    return ($a->getNumOfSongs() < $b->getNumOfSongs()) ? -1 : (($a->getNumOfSongs() > $b->getNumOfSongs()) ? 1 : 0);
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
        $songbooks = array_slice($songbooks, $offset, $length);

        $songbooks = array_map(function (Songbook $songbook){

            $session = $this->getActiveSession();
            $tags = array();
            foreach($songbook->tags as $tag){
                if($tag->public == true || ($session && $tag->user == $session->user)){
                    $tags[] = $tag;
                }
            }

            $tags = array_map(function(SongbookTag $tag){
                return [
                    'tag'    => $tag->tag,
                    'public' => $tag->public
                ];
            }, $tags);

            return [
                'id'       => $songbook->id,
                'name'     => $songbook->name,
                'public'   => $songbook->public,
                'archived' => $songbook->archived,
                'username' => $songbook->owner->username,
                'tags'     => $tags,
                'songs'    => $songbook->getNumOfSongs(),
                'rating'   => $songbook->getAverageRating()
            ];
        }, $songbooks);

        return response::json($songbooks);
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
        $session = $this->getActiveSession();
        if ($songbook->archived) {
            if (!($session && $this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $session->user, 'songbook' => $songbook]))) {
                $this->assumeAdmin();
            }
        }
        else if(!$songbook->public){
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if($user !== $songbook->owner &&
                !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]) &&
                !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
                $this->assumeAdmin();
            }
        }

        $session = $this->getActiveSession();
        $tags = array();
        foreach($songbook->tags as $tag){
            if($tag->public == true || ($session && $tag->user == $session->user)){
                $tags[] = $tag;
            }
        }

        $tags = array_map(function(SongbookTag $tag){
            return [
                'tag'    => $tag->tag,
                'public' => $tag->public
            ];
        }, $tags);

        $songs = array();
        foreach($songbook->songs as $songsongbook){
            $song = $songsongbook->song;
            if(!$song->public){
                if(!$session){
                    continue;
                }
                $user = $session->user;
                if($song->owner != $user &&
                    !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song]) &&
                    !$this->em->getDao(SongTaking::getClassName())->findBy(['user' => $user, 'song' => $song])) {
                    continue;
                }
            }
            $songs[] = $songsongbook;
        }

        $songs = array_map(function (SongSongbook $songsongbook){
            $song = $songsongbook->song;
            $session = $this->getActiveSession();
            $songTags = array();
            foreach($song->tags as $tag){
                if($tag->public == true || ($session && $tag->user == $session->user)){
                    $songTags[] = $tag;
                }
            }

            $songTags = array_map(function(SongTag $tag){
                return [
                    'tag'    => $tag->tag,
                    'public' => $tag->public
                ];
            }, $songTags);
            return [
                'id'             => $song->id,
                'title'          => $song->title,
                'album'          => $song->album,
                'author'         => $song->author,
                'year'           => $song->year,
                'public'         => $song->public,
                'username'       => $song->owner->username,
                'tags'           => $songTags,
                'position'       => $songsongbook->position
            ];
        }, $songs);

        $taken = false;
        if($session && $this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $session->user, 'songbook' => $songbook])){
            $taken = true;
        }

        return Response::json([
            'id'       => $songbook->id,
            'name'     => $songbook->name,
            'note'     => $songbook->note,
            'songs'    => $songs,
            'public'   => $songbook->public,
            'username' => $songbook->owner->username,
            'tags'     => $tags,
            'rating'   => $songbook->getAverageRating(),
            'taken'    => $taken
        ]);
    }

    /**
     * Updates Songbook by id.
     * @param $id
     */
    public function update($id)
    {
        $data = $this->request->getData();

        /** @var Songbook */
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $this->assumeLoggedIn();
        $user = $this->getActiveSession()->user;
        if ($user !== $songbook->owner &&
            !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])) {
            $this->assumeAdmin();
        }

        $action = $this->request->getQuery('action');

        if($action == 'tags'){
            $this->updateTags($songbook, $data['tags']);
            $this->em->flush();
            return Response::json([
                'id' => $songbook->id
            ]);
        }

        if ($user !== $songbook->owner) {
            $this->assumeAdmin();
        }

        $this->updateSongs($songbook, $data['songs']);
        if($action == 'songs'){
            $this->em->flush();
            return Response::json([
                'id' => $songbook->id
            ]);
        }
        else {
            $this->updateTags($songbook, $data['tags']);

            $songbook->name = $data['name'];
            $songbook->note = $data['note'];
            $songbook->public = $data['public'];
            $songbook->modified = new DateTime();
        }

        $takings = $this->em->getDao(SongbookTaking::getClassName())->findBy(['songbook' => $songbook]);
        foreach ($takings as $taking) {
            $this->notificationService->notify($taking->user, 'updated taken', $songbook, $user);
        }

        $this->em->flush();

        return Response::json([
            'id' => $songbook->id
        ]);
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
                'error'   => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser !== $songbook->owner) {
            $this->assumeAdmin();
        }

        $text = '';
        if ($songbook->archived == true) {
            $songbook->archived = false;
            $text = 'restored';
        } else {
            $songbook->archived = true;
            $text = 'deleted';
        }
        $songbook->modified = new DateTime();

        $takings = $this->em->getDao(SongbookTaking::getClassName())->findBy(['songbook' => $songbook]);
        foreach ($takings as $taking) {
            $this->notificationService->notify($taking->user, $text . ' taken', $songbook, $curUser);
        }
        if($curUser !== $songbook->owner)
            $this->notificationService->notify($songbook->owner, $text . ' by admin', $songbook, $curUser);

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Updates tags for given songbook
     * @param Songbook $songbook
     * @param $tags
     */
    private function updateTags(Songbook $songbook, $tags)
    {
        $tags = array_map(function ($tag) {
            $_tag = new SongbookTag();
            $_tag->tag = $tag['tag'];
            $_tag->public = $tag['public'];
            $_tag->user = $this->getActiveSession()->user;
            return $_tag;
        }, $tags);

        foreach ($songbook->tags as $tag) {
            if($tag->user != $this->getActiveSession()->user){
                $tags[] = $tag;
            }
            $this->em->remove($tag);
        }
        $songbook->clearTags();
        foreach ($tags as $tag) {
            if($tag->public && $tag->user != $songbook->owner){
                continue;
            }
            $tag->songbook = $songbook;
            $songbook->addTag($tag);
            $this->em->persist($tag);
        }
    }

    /**
     * Updates songs for given songbook
     * @param Songbook $songbook
     * @param $songs
     */
    private function updateSongs(Songbook $songbook, $songs)
    {
        $ids = array_map(function ($song) {
            return $song['id'];
        }, $songs);

        foreach ($songbook->songs as $songsongbook) {
            $this->em->remove($songsongbook);
        }
        $songbook->clearSongs();
        $this->em->flush();

        foreach ($ids as $id) {
            $song = $this->em->getDao(Song::getClassName())->findOneBy(['id' => $id]);

            $songsongbook = new SongSongbook();
            $songsongbook->song = $song;
            $songsongbook->songbook = $songbook;
            $songsongbook->position = count($songbook->songs) + 1;
            $this->em->persist($songsongbook);
            $songbook->addSong($songsongbook);
        }
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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }


        $user = $this->getActiveSession()->user;
        if ($user == $songbook->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot rate his own songbooks.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        if ($this->em->getDao(SongbookRating::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot rate songbook more than once.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }

        if (!$songbook->public &&
            !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]) &&
            !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
            $this->assumeAdmin();
        }

        $data = $this->request->getData();

        $rating = new SongbookRating;

        $rating->user = $user;
        $rating->songbook = $songbook;
        $rating->created = new DateTime();
        $rating->modified = $rating->created;
        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];

        $this->em->persist($rating);

        $this->notificationService->notify($songbook->owner, 'rated', $songbook, $user);

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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$songbook->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $songbook->owner &&
                !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]) &&
                !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
                $this->assumeAdmin();
            }
        }

        $ratings = $this->em->getDao(SongbookRating::getClassName())->findBy(['songbook'=> $songbook]);

        $ratings = array_map(function (SongbookRating $rating){
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
     * Updates existing songbook rating.
     * @param int $relationId
     * @return Response Response with SongbookRating object.
     */
    public function updateRating($relationId)
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

        $user = $this->getActiveSession()->user;
        if ($user !== $rating->user){
            $this->assumeAdmin();
        }

        $rating->comment = $data['comment'];
        $rating->rating = $data['rating'];
        $rating->modified = new DateTime();

        $this->notificationService->notify($rating->songbook->owner, 'updated rating', $rating->songbook, $user);

        $this->em->flush();

        return Response::json([
            'id' => $rating->id
        ]);
    }

    /**
     * Delete songbook rating.
     * @param int $relationId
     * @return Response
     */
    public function deleteRating($relationId)
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

        $user = $this->getActiveSession()->user;
        if ($user !== $rating->user){
            $this->assumeAdmin();
        }

        $this->em->remove($rating);

        $this->notificationService->notify($rating->songbook->owner, 'deleted rating', $rating->songbook, $user);

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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $user = $this->getActiveSession()->user;
        if ($user !== $songbook->owner && !$songbook->public &&
            !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]) &&
            !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
            $this->assumeAdmin();
        }

        $data = $this->request->getData();

        $comment = new SongbookComment;

        $comment->user = $user;
        $comment->songbook = $songbook;
        $comment->created = new DateTime();
        $comment->modified = $comment->created;
        $comment->comment = $data['comment'];

        $this->em->persist($comment);

        if ($user !== $songbook->owner) {
            $this->notificationService->notify($songbook->owner, 'commented', $songbook, $user);
        }

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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$songbook->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $songbook->owner &&
                !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook]) &&
                !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
                $this->assumeAdmin();
            }
        }

        $comments = $this->em->getDao(SongbookComment::getClassName())->findBy(['songbook' => $songbook]);

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

        if(!$comment->songbook->public) {
            $this->assumeLoggedIn();

            $user = $this->getActiveSession()->user;
            if ($user !== $comment->songbook->owner &&
                !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $comment->songbook]) &&
                !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $user, 'songbook' => $comment->songbook])){
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

        $user = $this->getActiveSession()->user;
        if ($user !== $comment->user){
            $this->assumeAdmin();
        }

        $comment->comment = $data['comment'];
        $comment->modified = new DateTime();

        if ($user !== $comment->songbook->owner) {
            $this->notificationService->notify($comment->songbook->owner, 'updated comment', $comment->songbook, $user);
        }

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
        if ($user !== $comment->user){
            $this->assumeAdmin();
        }

        $this->em->remove($comment);

        if ($user !== $comment->songbook->owner) {
            $this->notificationService->notify($comment->songbook->owner, 'deleted comment', $comment->songbook, $user);
        }

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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
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
        if ($curUser !== $songbook->owner){
            throw new AuthorizationException;
        }

        if ($curUser == $user || $songbook->public ||
            $this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $user, 'songbook' => $songbook])){
            return Response::json([
                'error' => 'DUPLICATE_SHARING',
                'message' => 'Songbook already shared with this user.'
            ])->setHttpStatus(Response::HTTP_CONFLICT);
        }

        $sharing = new SongbookSharing();

        $sharing->songbook = $songbook;
        $sharing->user = $user;

        $this->em->persist($sharing);

        foreach($songbook->songs as $song){
            if($song->song->owner == $curUser &&
                !$this->em->getDao(SongSharing::getClassName())->findBy(['user' => $user, 'song' => $song->song])){
                $songSharing = new SongSharing();

                $songSharing->song = $song->song;
                $songSharing->user = $user;

                $this->em->persist($songSharing);
            }
        }

        $this->notificationService->notify($user, 'shared', $songbook, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $sharing->id
        ]);
    }

    /**
     * Creates songbook taking by songbook id.
     * @param int $id
     * @return Response Response with SongbookTaking object.
     */
    public function createTaking($id)
    {
        $this->assumeLoggedIn();

        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser == $songbook->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot take his own songbooks.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        else if(!($songbook->public ||
            $this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $curUser, 'songbook' => $songbook]))){
            throw new AuthorizationException;
        }

        $taking = new SongbookTaking();

        $taking->songbook = $songbook;
        $taking->user = $curUser;

        $this->em->persist($taking);

        $this->notificationService->notify($songbook->owner, 'taken', $songbook, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $taking->id
        ]);
    }

    /**
     * Deletes songbook taking by songbook id and active user id.
     * @param int $id
     * @return Response
     */
    public function deleteAllTaking($id)
    {
        $this->assumeLoggedIn();

        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;

        /** @var SongbookTaking $taking */
        $taking = $this->em->getDao(SongbookTaking::getClassName())->findOneBy(['user' => $curUser, 'songbook' => $songbook]);

        if (!$taking) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK_TAKING',
                'message' => 'Songbook taking with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if ($curUser !== $taking->user){
            $this->assumeAdmin();
        }

        $this->em->remove($taking);

        $this->notificationService->notify($songbook->owner, 'canceled taking', $songbook, $curUser);

        $this->em->flush();

        return Response::blank();
    }

    /**
     * Creates copy of songbook with given id.
     * @param int $id
     * @return Response Response with Songbook object.
     */
    public function createCopy($id)
    {
        $this->assumeLoggedIn();

        /** @var Songbook $songbook*/
        $songbook = $this->em->getDao(Songbook::getClassName())->find($id);

        if (!$songbook) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        $curUser = $this->getActiveSession()->user;
        if ($curUser == $songbook->owner){
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => 'User cannot copy his own songbooks.'
            ])->setHttpStatus(Response::HTTP_BAD_REQUEST);
        }
        if (!$songbook->public &&
            !$this->em->getDao(SongbookSharing::getClassName())->findBy(['user' => $curUser, 'songbook' => $songbook]) &&
            !$this->em->getDao(SongbookTaking::getClassName())->findBy(['user' => $curUser, 'songbook' => $songbook])){
            throw new AuthorizationException;
        }

        /** @var Songbook $newSongbook*/
        $newSongbook = new Songbook();

        $newSongbook->name      = $songbook->name;
        $newSongbook->note      = $songbook->note;
        $newSongbook->owner     = $curUser;
        $newSongbook->public    = $songbook->public;
        $newSongbook->created   = new DateTime();
        $newSongbook->modified  = $songbook->created;
        $newSongbook->archived  = false;

        foreach ($songbook->tags as $tag) {     // kopírovat public tagy
            if($tag->public){
                $_tag = new SongbookTag();
                $_tag->tag = $tag->tag;
                $_tag->public = $tag->public;
                $_tag->user = $curUser;
                $_tag->songbook = $newSongbook;
                $newSongbook->addTag($_tag);
                $this->em->persist($_tag);
            }
        }

        foreach ($songbook->songs as $songsongbook) {     // kopírovat písně
            $song = $songsongbook->song;

            $newSongsongbook = new SongSongbook();
            $newSongsongbook->song = $song;
            $newSongsongbook->songbook = $newSongbook;
            $newSongsongbook->position = $songsongbook->position;
            $this->em->persist($newSongsongbook);
            $newSongbook->addSong($newSongsongbook);

            if($song->owner != $curUser &&
                !$this->em->getDao(SongTaking::getClassName())->findOneBy(['user' => $curUser, 'song' => $song])){

                $taking = new SongTaking();
                $taking->song = $song;
                $taking->user = $curUser;
                $this->em->persist($taking);
            }
        }

        $taking = $this->em->getDao(SongbookTaking::getClassName())->findOneBy(['user' => $curUser, 'songbook' => $songbook]);
        if($taking){
            $this->em->remove($taking); // odstranit prevzeti
            foreach ($songbook->tags as $tag) {     // presunout soukr. tagy
                if($tag->user == $curUser){
                    $songbook->removeTag($tag);
                    $tag->songbook = $newSongbook;
                    $newSongbook->addTag($tag);
                }
            }
        }

        $this->em->persist($newSongbook);

        $this->notificationService->notify($songbook->owner, 'copied', $songbook, $curUser);

        $this->em->flush();

        return Response::json([
            'id' => $newSongbook->id
        ])->setHttpStatus(Response::HTTP_CREATED);

    }



} 