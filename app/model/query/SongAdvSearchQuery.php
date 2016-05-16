<?php

namespace App\Model\Query;

use App\Model\Entity\Song;
use App\Model\Entity\User;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for songs which metadata contains search string.
 * @author Tomáš Markacz
 */
class SongAdvSearchQuery extends QueryObject
{

	/** @var User */
	private $user;

	/** @var string */
	private $title;

    /** @var string */
    private $album;

    /** @var string */
    private $author;

    /** @var int */
    private $year;

    /** @var string */
    private $owner;

    /** @var string */
    private $tag;

    /** @var bool */
    private $public;

    /**
     * @param $user
     * @param $title
     * @param $album
     * @param $author
     * @param $year
     * @param $owner
     * @param $tag
     * @param bool $public
     */
	public function __construct($user, $title, $album, $author, $year, $owner, $tag, $public)
	{
		$this->user   = $user;
	    $this->title  = $title;
        $this->album  = $album;
        $this->author = $author;
        $this->year   = $year;
        $this->owner  = $owner;
        $this->tag    = $tag;
        $this->public = $public;
	}

	/**
	 * @param Queryable $repository
	 * @return QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{

        $query = $repository->createQueryBuilder()
			->select('s')
			->from(Song::getClassName(), 's')
            ->andWhere('s.archived = 0')
            ->orderBy('s.title');

        if (!$this->public) {
            $or = new Orx([
                's.owner = :user',
                'st.user = :user'
            ]);
            $query->leftJoin('s.songTakes', 'st')
                ->andWhere($or)
                ->setParameter('user', $this->user);
        }
        else {
            $query->andWhere('s.public = 1');
        }

        if ($this->title != null)
            $query->andWhere('s.title LIKE :title')
                ->setParameter('title', "%$this->title%");

		if ($this->album != null)
            $query->andWhere('s.album LIKE :album')
                ->setParameter('album', "%$this->album%");

        if ($this->author != null)
            $query->andWhere('s.author LIKE :author')
                ->setParameter('author', "%$this->author%");

        if ($this->year != null)
            $query->andWhere('s.year LIKE :year')
                ->setParameter('year', "%$this->year%");

        if ($this->owner != null) {
            $query->innerJoin('s.owner', 'u')
                ->andWhere('u.username LIKE :owner')
                ->setParameter('owner', "%$this->owner%");
        }

        if ($this->tag != null) {
            $query->innerJoin('s.tags', 't')
                ->andWhere('t.tag LIKE :tag')
                ->setParameter('tag', "%$this->tag%");
        }

        return $query;
	}

}
