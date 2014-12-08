<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\Notification;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use App\Model\Service\NotificationService;
use Tester\Assert;

/** @var NotificationService $notificationService */
$notificationService = $dic->getByType('App\Model\Service\NotificationService');

$user     = new User;
$song     = new Song;
$songbook = new Songbook;

///////////////////////////////////////////////////////////////////////////////////////////

$notification = $notificationService->notify($user, 'Lorem ipsum.');

Assert::type(Notification::getClassName(), $notification);
Assert::type('DateTime', $notification->created);
Assert::equal(FALSE, $notification->read);
Assert::equal($user, $notification->user);
Assert::equal('Lorem ipsum.', $notification->text);
Assert::equal(NULL, $notification->song);
Assert::equal(NULL, $notification->songbook);

///////////////////////////////////////////////////////////////////////////////////////////

$notification = $notificationService->notify($user, 'Foo bar baz.', $song);

Assert::type(Notification::getClassName(), $notification);
Assert::type('DateTime', $notification->created);
Assert::equal(FALSE, $notification->read);
Assert::equal($user, $notification->user);
Assert::equal('Foo bar baz.', $notification->text);
Assert::equal($song, $notification->song);
Assert::equal(NULL, $notification->songbook);

///////////////////////////////////////////////////////////////////////////////////////////

$notification = $notificationService->notify($user, 'Dolor sit amet.', $songbook);

Assert::type(Notification::getClassName(), $notification);
Assert::type('DateTime', $notification->created);
Assert::equal(FALSE, $notification->read);
Assert::equal($user, $notification->user);
Assert::equal('Dolor sit amet.', $notification->text);
Assert::equal(NULL, $notification->song);
Assert::equal($songbook, $notification->songbook);

///////////////////////////////////////////////////////////////////////////////////////////

Assert::exception(function () use ($notificationService, $user) {
	$notificationService->notify($user, 'This will fail.', $user);
}, 'InvalidArgumentException', 'Invalid subject given. Song or Songbook entity expected.');
