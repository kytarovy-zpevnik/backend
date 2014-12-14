<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\Song;
use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Utils\RequestBuilder;
use Nette\Utils\Json;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

/** @var EntityManager $em */
$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(3)); // user markatom

$agama = <<<END
Ami
  Ty nejsi krása prázdná
            G
nejsi pouhá   touha blázna
D
  vysněná, vysněná
Ami
  jsi tvrdší nežli skála
              G
přesto ses mi   tehdy zdála
D
  zraněná, zraněná

Emi                        D          A
  Když jsem tě na té párty   prvně uvi děl
Emi                           G              A
  chtěl jsem tě líbat na rty,   říct se nesty děl

Ami        G              D G
  Má lásko   divoká, pojď    spát


Už nevím cos mi řekla
jen to že jsi byla vzteklá
a šla spát sama spát
tvá slova byla hrubší
moje láska o to hlubší
takovou mám tě rád
END;

$data = [
	'songbooks' => [],
	'tags' => [],
	'public' => TRUE,
	'note' => NULL,
	'year' => 2014,
	'originalAuthor' => NULL,
	'author' => 'Foo bar',
	'title' => 'Test',
	'album' => NULL,
	'agama' => $agama,
];

$request = RequestBuilder::target('frontend', 1, 'songs', 'create', RequestBuilder::METHOD_POST) // specify target
	->setHeader('X-Session-Token', $sessionToken) // set session token
	->setQuery('import', 'agama')
	->setJsonPost($data)
	->create(); // create request

$response = handleRequest($request);

$data = Json::decode($response->getData(), Json::FORCE_ARRAY);

$song = $em->getDao(Song::getClassName())->find($data['id']);

$lyrics = <<<END
Ty nejsi krása prázdná
nejsi pouhá touha blázna
vysněná, vysněná
jsi tvrdší nežli skála
přesto ses mi tehdy zdála
zraněná, zraněná

Když jsem tě na té párty prvně uviděl
chtěl jsem tě líbat na rty, říct se nestyděl

Má lásko divoká, pojď spát

Už nevím cos mi řekla
jen to že jsi byla vzteklá
a šla spát sama spát
tvá slova byla hrubší
moje láska o to hlubší
takovou mám tě rád

END;

Assert::equal($lyrics, $song->lyrics);
Assert::equal('{"0":"Ami","35":"G","48":"D","65":"Ami","102":"G","114":"D","132":"Emi","157":"D","166":"A","170":"Emi",'
	. '"198":"G","211":"A","216":"Ami","225":"G","238":"D, G"}', $song->chords);
