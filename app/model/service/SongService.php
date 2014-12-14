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

	public function importAgama(Song $song, $agama)
	{
        $lyrics = [];
        $chords = [];

		$lines = explode("\n", $agama);

        while (TRUE) {
            $current = array_shift($lines);

            if ($current === NULL) {
                break;  // no more lines
            }

            // split to utf8 characters, because $s = 'čau' becomes $s[0] = '�', $s[1] = '�', $s[2] = 'a', $s[3] = 'u'
            $current = preg_split('~~u', $current, -1, PREG_SPLIT_NO_EMPTY);

            if ($current === []) {
                if (array_slice($lyrics, -2) !== ["\n", "\n"]) { // allow only one blank line
                    $lyrics[] = "\n";
                }
                continue;
            }

            $next = array_shift($lines);

            if ($next === NULL) {
                $lyrics = array_merge($lyrics, $current, ["\n"]);
                break; // no more lines
            }

            // split to utf8 characters, because $s = 'čau' becomes $s[0] = '�', $s[1] = '�', $s[2] = 'a', $s[3] = 'u'
            $next = preg_split('~~u', $next, -1, PREG_SPLIT_NO_EMPTY);

            if ($current[0] !== ' ' && $next[0] !== ' ') { // both lyrics
                $lyrics = array_merge($lyrics, $current, ["\n"]);
                $lyrics = array_merge($lyrics, $next, ["\n"]);
                continue;
            }

            $offset        = count($lyrics);
            $currentLength = count($current);
            $nextLength    = count($next);
            $maxLength     = max($currentLength, $nextLength);

            $chord = '';
            $ignoreSpace = TRUE;
            $chordOffset = 0;
            for ($i = 0; $i <= $maxLength; $i++) { // intentionally <= to get one more iteration
                if ($i < $currentLength && $current[$i] !== ' ') { // found chord's letter
                    if (!$chord) { // beginning of chord
                        $chordOffset = $offset; // capture offset
                        $ignoreSpace = TRUE; // ignore space in lyrics
                    }

                    $chord .= $current[$i]; // append chord's character

                } elseif ($chord) { // chord ended
                    if (isset($chords[$chordOffset])) { // multiple chords
                        $chords[$chordOffset] .= ', ' . $chord; // append chord
                    } else {
                        $chords[$chordOffset] = $chord; // store chord
                    }

                    $chord = ''; // reset chord
                }

                if ($i < $nextLength) {
                    if ($next[$i] !== ' ' // not space
                        || ( // or space
                            !$ignoreSpace // do not ignore spaces
                            && (
                                $offset === 0 // no previous character
                                || $lyrics[$offset - 1] !== ' ' // or previous character must not be space
                            )
                        )
                    ) {
                        $lyrics[] = $next[$i]; // append character to lyrics
                        $offset++; // advance offset
                        $ignoreSpace = FALSE; // do not ignore next space
                    }
                }
            }

            $lyrics[] = "\n"; // line break
        }

        $song->lyrics = implode('', $lyrics); // array of utf8 characters to string
        $song->chords = Json::encode($chords);
	}

}
