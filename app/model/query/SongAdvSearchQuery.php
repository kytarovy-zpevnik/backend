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

    /**
     * @param User $user
     * @param $title
     * @param $album
     * @param $author
     * @param $tag
     */
	public function __construct(User $user, $title, $album, $author, $tag)
	{
		$this->user   = $user;
	    $this->title  = $title;
        $this->album  = $album;
        $this->author = $author;
        $this->tag    = $tag;
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
			->andWhere('s.owner = :owner')
			->setParameter('owner', $this->user);
        if ($this->title != null)
            $query->andWhere('s.title LIKE :title')->setParameter('title', "%$this->title%");
		if ($this->album != null)
            $query->andWhere('s.album LIKE :album')->setParameter('album', "%$this->album%");
        if ($this->author != null)
            $query->andWhere('s.author LIKE :author')->setParameter('author', "%$this->author%");
        if ($this->tag != null) {
            $query->innerJoin('s.tags', 't')->andWhere('t.tag LIKE :tag')->setParameter('tag', "%$this->tag%");
        }
        $query->orderBy('s.title');

        return $query;
	}

}
