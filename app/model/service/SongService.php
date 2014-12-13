<?php

namespace App\Model\Service;

use App\Model\Entity\Song;
use App\Model\Entity\User;
use DateTime;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Song service
 * @author Tomáš Jirásek
 */
class SongService extends Object
{

	const NOTE_PATTERN = '~C#|D#|F#|G#|C|D|E|F|G|A|B|H~';

    /** @var EntityManager */
    private $em;

	/** @var array */
	private static $chromaticScale = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'B', 'H'];

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $title
     * @param $song
     * @param User $owner
     * @param string $originalAuthor
     * @param string $album
     * @param string $author
     * @param bool $public
     * @param int $year
     * @return Song
     */
    public function create($title, $song, User $owner = null, $originalAuthor = "undefined",
                           $album = "undefined", $author = "undefined", $public = false,
                           $year = null)
    {
        $song = new Song();

        $song->title = $title;
        $song->song  = $song;

        $song->owner          = $owner;
        $song->originalAuthor = $originalAuthor;
        $song->album          = $album;
        $song->author         = $author;
        $song->public         = $public;
        $song->year           = $year;

        $song->created  = new DateTime();
        $song->modified = $song->created;
        $song->archived = false;
        $song->viewers  = null;
        $song->editors  = null;
        $song->tags     = null;

        $this->em->persist($song);

        return $song;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $song = $this->em->find("Song",$id);
        if ($song) {
            $this->em->remove($song);
        }
    }

    /**
     * @return \App\Model\Entity\Song[]
     */
    public function getAllSongs()
    {
        return $this->em->getDao(Song::getClassName())->findAll();
    }

    /**
     * @return \App\Model\Entity\Song[]
     */
    public function getAllPublicSongs()
    {
        return $this->em->getDao(Song::getClassName())->findBy(["public" => true]);
    }

	/**
	 * Transposes all song chords.
	 * @param Song $song
	 * @param $offset
	 */
	public function transpose(Song $song, $offset)
	{
		$chords = Json::decode($song->chords, Json::FORCE_ARRAY);

		foreach ($chords as & $chord) {
			$chord = Strings::replace($chord, self::NOTE_PATTERN, function (array $matches) use ($offset) {
				$key = array_search($matches[0], self::$chromaticScale);
				$key += $offset;

				if ($key >= count(self::$chromaticScale)) {
					$key -= count(self::$chromaticScale);
				}

				return self::$chromaticScale[$key];
			});
		}

		$song->chords = Json::encode($chords);
	}

}
