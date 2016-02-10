<?php

namespace App\Model\Query;

use App\Model\Entity\Song;
use App\Model\Entity\SongTag;
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

    /** @var string */
    private $tag;

    /** @var bool */
    private $public;

    /**
     * @param User $user
     * @param $title
     * @param $album
     * @param $author
     * @param $tag
     * @param bool $public
     */
	public function __construct($user, $title, $album, $author, $tag, $public)
	{
		$this->user   = $user;
	    $this->title  = $title;
        $this->album  = $album;
        $this->author = $author;
        $this->tag    = $tag;
        $this->public = $public;
	}

	/**
	 * @param Queryable $repository
	 * @return QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{
        $condition = $this->public ? 's.public = 1' : 's.owner = :owner';

        $query = $repository->createQueryBuilder()
			->select('s')
			->from(Song::getClassName(), 's')
            ->andWhere('s.archived = 0')
			->andWhere($condition)
            ->orderBy('s.title');

        if ($this->title != null)
            $query->andWhere('s.title LIKE :title')->setParameter('title', "%$this->title%");

		if ($this->album != null)
            $query->andWhere('s.album LIKE :album')->setParameter('album', "%$this->album%");

        if ($this->author != null)
            $query->andWhere('s.author LIKE :author')->setParameter('author', "%$this->author%");

        if ($this->tag != null) {
            $query->innerJoin('s.tags', 't')->andWhere('t.tag LIKE :tag')->setParameter('tag', "%$this->tag%");
        }

        if(!$this->public)
            $query->setParameter('owner', $this->user);

        return $query;
	}

}
