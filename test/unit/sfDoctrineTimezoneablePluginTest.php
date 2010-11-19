<?php

// test variables definition
define('TEST_CLASS', 'EventOccurence');

// setup testing framework
$sf_root_dir = realpath(dirname(__FILE__)).'/../../../..';
$apps_dir    = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0],
	              strrpos($apps_dir[0], '/') + 1,
	              strlen($apps_dir[0]));

if(!$app)
	throw new Exception('No app has been detected in this project');

require_once($sf_root_dir.'/test/bootstrap/functional.php');

$configuration = ProjectConfiguration::getApplicationConfiguration($app, 'test', true);
$databaseManager = new sfDatabaseManager($configuration);

if(!defined('TEST_CLASS') || !class_exists(TEST_CLASS)) {
	// Don't run tests
	return;
}

Doctrine_Core::getTable(TEST_CLASS)->findAll()->delete();

// start tests
$t = new lime_test(23, new lime_output_color());

// let's play with the user object firstly

$sf_user = sfContext::getInstance()->getUser();

date_default_timezone_set('Asia/Manila');

$t->is($sf_user->getTimezone(), 'Asia/Manila', '->getTimezone() on sf_user gets me the current timezone');

// does setting the timezone work?

$sf_user->setTimezone('America/New_York');

$t->is(date_default_timezone_get(), 'America/New_York', '->setTimezone() on sf_user sets the current timezone');

// set the timezone we'll start with
$sf_user->setTimezone('Asia/Manila');

// let's create an event
$event  = _create_object();
$event2 = _create_object();

$event->setStartDate(date('Y-m-d H:i:s', mktime(0,0,0,6,15,2010)));
$event->save();

$event_id = $event->getId();

// we create this just to make sure we aren't selecting different events
$event2->setStartDate(date('Y-m-d H:i:s', mktime(12,0,0,6,15,2010)));
$event2->save();

$t->is($event->start_date, date('Y-m-d H:i:s', mktime(0,0,0,6,15,2010)), '->save() shouldn\'t visibly affect the date at all');

// doing a raw SQL query should not affect the timezone at all

$doctrine = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
$res = $doctrine->query('SELECT * FROM `event_occurence` LIMIT 1')->fetch();

$t->is($res['start_date'], '2010-06-14 16:00:00', '->fetch() should return the real UTC time when doing a raw SQL query');

// find an event for a given local time, but should really be in UTC time

$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.start_date = ?', '2010-06-15 00:00:00');
$event = $q->fetchOne();
$t->isa_ok($event, 'EventOccurence', '->fetchOne() will find the event correctly, passed the original time');
$t->is($event->getId(), $event_id, '->fetchOne() will return the correct event');

// find an event between two times, both local, but should really be UTC times

$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.start_date BETWEEN ? AND ?', array('2010-06-14 00:00:00', '2010-06-14 23:59:59'));
$t->ok(!$q->fetchOne(), '->fetchOne() will not find the event if it is outside the right local time');

$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.start_date BETWEEN ? AND ?', array('2010-06-14 23:30:00', '2010-06-15 10:59:00'));
$t->isa_ok($q->fetchOne(), 'EventOccurence', '->fetchOne() will find the event if it is in the right time');

$t->is($q->count(), 1, '->count() should only see one event');

// find an event but using multiple parameters in the SQL query

$sf_user->setTimezone('Asia/Manila');

$e = _create_object();
$e->setActual(5);
$e->setStartDate('2010-06-22 12:00:00');
$e->save();

$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.actual = ?', 5)
       ->andWhere('e.start_date = ?', array('2010-06-22 12:00:00'));
$t->isa_ok($q->fetchOne(), 'EventOccurence', '->fetchOne() will find the event with multiple parameters in the query');

// loading an event should load the event in the local timezone
$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.start_date = ?', '2010-06-15 00:00:00');
$t->is($q->fetchOne()->getStartDate(), '2010-06-15 00:00:00', '->fetchOne() loading will automatically convert stored UTC to local timezone');

// updating an event using DQL should affect the correct event
$q = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->update()
       ->set('e.impact', 1)
       ->where('e.start_date = ?', '2010-06-15 00:00:00')
       ->execute();
$e = Doctrine_Query::create()
       ->from('EventOccurence e')
       ->where('e.start_date = ?', '2010-06-15 00:00:00')
       ->fetchOne();
$t->is($e->getId(), $event_id, '->fetchOne() still returns the correct event');
$t->is($e->getImpact(), 1, '->getImpact() was correctly updated');

// apparently there's an issue with DST handling, let's see....
$sf_user->setTimezone('America/New_York');

$e = _create_object();
$e->setStartDate('2010-01-01 00:00:00');
$e->save();

$t->is($e->getStartDate(), '2010-01-01 00:00:00', '->save() when in DST will always equal the same after saving (New York, Jan 10)');

$e->delete();

$sf_user->setTimezone('America/New_York');

$e = _create_object();
$e->setStartDate('2010-06-18 00:00:00');
$e->save();

$t->is($e->getStartDate(), '2010-06-18 00:00:00', '->save() when in DST will always equal the same after saving (New York, June 10)');

$e->delete();

$sf_user->setTimezone('Australia/Sydney');

$e = _create_object();
$e->setStartDate('2010-01-01 00:00:00');
$e->save();

$t->is($e->getStartDate(), '2010-01-01 00:00:00', '->save() when in DST will always equal the same after saving (Sydeny, Jan 10)');

$e->delete();

// does setting the timezone update the model objects also?

$sf_user->setTimezone('UTC');

$e = _create_object();
$e->setStartDate('2010-06-18 15:11:11');

$sf_user->setTimezone('America/New_York');

$t->is($e->getStartDate(), '2010-06-18 11:11:11', '->getStartDate() is affected by the change of the timezone in sf_user');

$dt = $e->getDateTimeObject('start_date');

$t->is($dt->getTimezone()->getName(), 'America/New_York', '->getDateTimeObject() also has the timezone on it');
$t->is($dt->format('Y-m-d H:i:s'), '2010-06-18 11:11:11', '->getDateTimeObject() has the correct time in it');

// will switching timezones update the event itself also?
$sf_user->setTimezone('America/New_York');

$e = _create_object();
$e->setStartDate('2010-06-18 11:11:11');

$sf_user->setTimezone('America/Los_Angeles');

$t->is($e->getStartDate(), '2010-06-18 08:11:11', '->getStartDate() should return the time in LA');

// I should be able to get the DateTime object in any timezone I want
$sf_user->setTimezone('Asia/Manila');

$e = _create_object();
$e->setStartDate('2010-06-18 20:00:00');

$t->is($e->getDateTimeObject('start_date', 'UTC')->format('Y-m-d H:i:s'), '2010-06-18 12:00:00', '->getDateTimeObject() will return in any timezone I want');
$t->is($e->getDateTimeObject('start_date', 'America/New_York')->format('Y-m-d H:i:s'), '2010-06-18 08:00:00', '->getDateTimeObject() will return in any timezone I want');

// what if I don't specify the created_at or updated_at?
$sf_user->setTimezone('UTC');

$eo = new EventOccurence();
$eo->setEvent( EventTable::lookupEvent('Test Event', 'USD', 'US', '% m/m') );
$eo->setStartDate('2010-01-01 12:00:00');
$eo->save();

$t->ok($eo->getId() !== false, '->save() works ok even without specificying a created_at or updated_at');



// test object creation
function _create_object()
{
  $classname = TEST_CLASS;

  if (!class_exists($classname))
  {
    throw new Exception(sprintf('Unknow class "%s"', $classname));
  }

  $e = new $classname();
  $e->setEvent(EventTable::lookupEvent('Test Event', 'USD', 'US', '% m/m'));
  $e->setImpact(1);
  $e->setStartDate(date('Y-m-d H:i:s'));
  return $e;
}
