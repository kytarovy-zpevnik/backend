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

        $ids = array_map(function ($song) {
            return $song['id'];
        }, $data['songs']);

        $songs = $this->em->getDao(Song::getClassName())->findBy(['id' => $ids]);

        foreach ($songs as $song) {
            $songsongbook = new SongSongbook();
            $songsongbook->song = $song;
            $songsongbook->songbook = $songbook;
            $songsongbook->position = count($songbook->songs) + 1;
            $this->em->persist($songsongbook);
            $songbook->addSong($songsongbook);
        }

        $tags = array_map(function ($tag) {
            $_tag = new SongbookTag();
            $_tag->tag = $tag['tag'];
            $_tag->public = $tag['public'];
            $_tag->user = $this->getActiveSession()->user;
            return $_tag;
        }, $data['tags']);

        foreach ($tags as $tag) {
            $tag->songbook = $songbook;
            $songbook->addTag($tag);
            $this->em->persist($tag);
        }

        $songbook->name = $data['name'];
        $songbook->created = new DateTime();
        $songbook->modified = new DateTime();
        $songbook->archived = false;
        $songbook->public = $data['public'];
        $songbook->owner = $this->getActiveSession()->user;
        $songbook->note = $data['note'];

        $this->em->persist($songbook);

        if ($songbookFromId = $this->request->getQuery('takenFrom')) {
            /** @var Songbook $songbookFrom */
            $songbookFrom = $this->em->getDao(Songbook::getClassName())->find($songbookFromId); // tohle bude chtít upravit při kopírování smazaného
            $userFrom = $this->em->getDao(User::getClassName())->findOneBy(['username' => $songbookFrom->owner->username]);
            $copyNotification = new Notification();
            $copyNotification->user = $userFrom;
            $copyNotification->created = new DateTime();
            $copyNotification->read = false;
            $copyNotification->song = $songbookFrom;
            $copyNotification->text = 'Váš zpěvník "'.$songbookFrom->name.'" byl zkopírován uživatelem "'.$this->getActiveSession()->user->username.'".';
            $this->em->persist($copyNotification);

            $taking = $this->em->getDao(SongbookTaking::getClassName())->findOneBy(['user' => $this->getActiveSession()->user, 'songbook' => $songbookFrom]);
            if($taking){
                $this->em->remove($taking);
                foreach ($songbookFrom->tags as $tag) {
                    if($tag->user == $this->getActiveSession()->user){
                        $songbookFrom->removeTag($tag);
                        $this->em->remove($tag);
                    }
                }
            }
        }

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
        else if ((!$public && count($this->request->getQuery()) > 0) || count($this->request->getQuery()) > 1) {
            $name  = $this->request->getQuery('name');
            $tag    = $this->request->getQuery('tag');
            $songbooks  = $this->em->getDao(Songbook::getClassName())
                ->fetch(new SongbookAdvSearchQuery($user, $name, $tag, $public))
                ->getIterator()
                ->getArrayCopy();
        }
        else {
            $songbooks = $this->em->getDao(Songbook::getClassName())->findBy($findBy, ['name' => 'ASC']);
            if (!$public){
                $takenSongbooks = $this->em->getDao(SongbookTaking::getClassName())
                    ->findBy(['user' => $user]);
                $takenSongbooks = array_map(function(SongbookTaking $taking){
                    return $taking->songbook;
                }, $takenSongbooks);
                $songbooks = array_merge($songbooks, $takenSongbooks);
            }
        }

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

            $averageRating = $this->getAverageRating($songbook);

            return [
                'id'       => $songbook->id,
                'name'     => $songbook->name,
                'public'   => $songbook->public,
                'archived' => $songbook->archived,
                'username' => $songbook->owner->username,
                'tags'     => $tags,
                'songs'    => count($songbook->songs),
                'rating'   => $averageRating
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

        if (!$songbook || $songbook->archived) {
            return Response::json([
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if(!$songbook->public){
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

        $averageRating = $this->getAverageRating($songbook);

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
            'rating'   => $averageRating,
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

        if($action = $this->request->getQuery('action')){
            if($action == 'tags'){
                $this->updateTags($songbook, $data['tags']);
                $this->em->flush();
                return Response::json([
                    'id' => $songbook->id
                ]);
            }
            if($action == 'songbooks'){
                $this->updateSongs($songbook, $data['songs']);
                $this->em->flush();
                return Response::json([
                    'id' => $songbook->id
                ]);
            }
        }

        if ($user !== $songbook->owner) {
            $this->assumeAdmin();
        }

        $this->updateTags($songbook, $data['tags']);
        $this->updateSongs($songbook, $data['songs']);

        $songbook->name = $data['name'];
        $songbook->note = $data['note'];
        $songbook->public = $data['public'];
        $songbook->modified = new DateTime();

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
                'error' => 'UNKNOWN_SONGBOOK',
                'message' => 'Songbook with given id not found.'
            ])->setHttpStatus(Response::HTTP_NOT_FOUND);
        }

        if($this->getActiveSession()->user !== $songbook->owner){
            $this->assumeAdmin();
        }

        if($songbook->archived == true)
            $songbook->archived = false;
        else
            $songbook->archived = true;
        $songbook->modified = new DateTime();

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
     * Updates songbooks for given song
     * @param Song $song
     * @param $songbooks
     */
    private function updateSongs(Songbook $songbook, $songs)
    {
        $ids = array_map(function ($song) {
            return $song['id'];
        }, $songs);

        $songs = $this->em->getDao(Song::getClassName())->findBy(['id' => $ids]);

        // Which songs to keep and which delete
        foreach ($songbook->songs as $songsongbook) {
            $keepit = false;
            foreach($songs as $song){
                if($songsongbook->song->id == $song->id){
                    $keepit = true;
                    break;
                }
            }
            if($keepit){
                continue;
            }
            $this->em->remove($songsongbook);
            $this->em->flush($songsongbook);
            $othersongs = $this->em->getDao(SongSongbook::getClassName())->findBy(['songbook' => $songbook], ['position' => 'ASC']);

            foreach ($othersongs as $other){
                if($other->position > $songsongbook->position){
                    $other->position -= 1;
                    $this->em->persist($other);
                    $this->em->flush($other);
                }
            }
            $songbook->removeSong($songsongbook);

        }

        foreach ($songs as $song) {
            $songsongbook = $this->em->getDao(SongSongbook::getClassName())->findOneBy(['songbook' => $songbook, 'song' => $song]);
            if(!$songsongbook){
                $songsongbook = new SongSongbook();
                $songsongbook->song = $song;
                $songsongbook->songbook = $songbook;
                $songsongbook->position = count($songbook->songs) + 1;
                $this->em->persist($songsongbook);
                $songbook->addSong($songsongbook);
            }
        }
    }

    /**
     * Counts average rating for given songbook
     * @param Songbook $songbook
     * @return int
     */
    private function getAverageRating(Songbook $songbook)
    {
        $ratings = $this->em->getDao(SongbookRating::getClassName())
            ->findBy(['songbook' => $songbook]);

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

        $notification = new Notification();
        $notification->user = $songbook->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $songbook;
        $notification->text = 'Uživatel "'.$user->username.'" ohodnotil Váš zpěvník "'.$songbook->name.'".';
        $this->em->persist($notification);

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

        $notification = new Notification();
        $notification->user = $rating->songbook->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $rating->songbook;
        $notification->text = 'Uživatel "'.$user->username.'" upravil hodnocení Vašeho zpěvníku "'.$rating->songbook->name.'".';
        $this->em->persist($notification);

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

        if ($this->getActiveSession()->user !== $rating->user) {
            $this->assumeAdmin();
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
            $notification = new Notification();
            $notification->user = $songbook->owner;
            $notification->created = new DateTime();
            $notification->read = false;
            $notification->songbook = $songbook;
            $notification->text = 'Uživatel "' . $user->username . '" okomentoval Váš zpěvník "' . $songbook->name . '".';
            $this->em->persist($notification);
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
            $notification = new Notification();
            $notification->user = $comment->songbook->owner;
            $notification->created = new DateTime();
            $notification->read = false;
            $notification->songbook = $comment->songbook;
            $notification->text = 'Uživatel "'.$user->username.'" upravil komentář u Vašeho zpěvníku "'.$comment->songbook->name.'".';
            $this->em->persist($notification);
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

        if ($this->getActiveSession()->user !== $comment->user){
            $this->assumeAdmin();
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

        if (!$songbook || $songbook->archived) {
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

        $notification = new Notification();
        $notification->user = $user;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $songbook;
        $notification->text = 'Uživatel "'.$curUser->username.'" s Vámi sdílel zpěvník "'.$songbook->name.'".';
        $this->em->persist($notification);

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

        $notification = new Notification();
        $notification->user = $songbook->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $songbook;
        $notification->text = 'Uživatel "'.$curUser->username.'" převzal váš zpěvník "'.$songbook->name.'".';
        $this->em->persist($notification);

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

        $notification = new Notification();
        $notification->user = $taking->songbook->owner;
        $notification->created = new DateTime();
        $notification->read = false;
        $notification->songbook = $taking->songbook;
        $notification->text = 'Uživatel "'.$taking->user->username.'" zrušil převzetí vašeho zpěvníku "'.$taking->songbook->name.'".';
        $this->em->persist($notification);

        $this->em->flush();

        return Response::blank();
    }



} 