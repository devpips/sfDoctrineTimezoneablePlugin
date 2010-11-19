# sfDoctrineTimezoneablePlugin

The purpose of this plugin is to provide simple and easy timezone handling within Symfony and Doctrine. It attempts at being 100% transparent similar to the "rails way" by handling all time and date objects in the database.

## Quick Overview of How it Works

This plugin works by extending the sfDoctrineRecord class and automatically handling any calls to `get()` or `set()` methods that make a reference to a database column of the type date, datetime, or timestamp. The goal was to emulate the same way that user cultures are handled in Symfony.

When setting, the time is automatically converted to UTC. This is how it will be saved to the database.

When getting, the time is automatically converted to whatever timezone the user is in.

Querying the database is also automatically handled. When using a DQL query, you can query in the user's local timezone and it will automatically be converted to UTC before querying the database.

Everything time related is handled for you using PHP's built in DateTime objects. Daylight Savings Time is automatically accounted for and changing timezone is handled using Symfony's built in event system.

## Installing the Plugin

Installation is simple:

1. Put the `sfDoctrineTimezoneablePlugin` directory in your `plugins` directory.
2. Enable it in `ProjectConfiguration.class.php` file: `$this->enablePlugins('sfDoctrineTimezoneablePlugin');`
3. Make sure DQL callbacks for Doctrine are enabled:
		public function configureDoctrine(Doctrine_Manager $manager) 
		{
			$manager->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true); 
		}
4. Rebuild your Doctrine models: `php symfony doctrine:build --model`
5. Set the default user timezone in `settings.yml`:
		`default_timezone:       America/Chicago`
6. Enjoy :)

After installation, ALL of your models will be handled as timezoneable models. That is, all time and date columns in all your models will automatically be handled and converted to the user's timezone.

## Usage

### Changing the timezone

Need to change the user timezone or set it?

	$this->getUser()->setTimezone('Asia/Manila');
	$sf_user->setTimezone('UTC');

All times are then automatically updated to reflect the change.

### Taking a look at the timezone handling

If you want to just see how it works, maybe for testing purposes, you can check the tests in `/plugins/sfDoctrineTimezoneablePlugin/test/unit/sfDoctrineTimezoneablePluginTest.php`. If that doesn't suffice...

	$sf_user->setTimezone('America/New_York');
	
	$e = new Event();
	
	$e->setUpdatedAt('2010-06-22 11:54:00');
	
	echo $e->getUpdatedAt();	// 2010-06-22 11:54:00
	
	$e->save();	// if you were to look now, the time would be 2010-06-22 15:54:00, the UTC time
	
	$sf_user->setTimezone('America/Los_Angeles');
	
	echo $e->getUpdatedAt();	// 2010-06-22 08:54:00

### Setting the timezone affects my `date()`

Using `$sf_user->setTimezone()` will make a call to `date_default_timezone_set` so all your `date` based functions will also be affected. Whether this is a good or bad thing, it's up to you to decide.

### Listening to when the user changes the timezone

Want to listen to when the user changes the timezone? It's easy:

    $this->dispatcher->connect('user.change_timezone', array('myListeners','listenToUserChangeTimezone'));

### Getting the model's time in a different timezone

There may be cases where you want to get the model's column in a different timezone than what the user's timezone is currently set to. That's easy, too. :)

	$sf_user->setTimezone('America/New_York');
	
	$e = new Event();
	$e->setUpdatedAt('2010-06-22 12:00:00');
	
	echo $e->getDateTimeObject('updated_at', 'UST')->format('Y-m-d H:i:s');	// 2010-06-22 16:00:00
	echo $e->getDateTimeObject('updated_at', 'Asia/Manila')->format('U');	// 1277208000

Just specify the timezone name as the second parameter to `getDateTimeObject`. The conversion is quick and automatic.

### Querying the database with the automatic conversion

Any dates you put in your WHERE clause of your DQL queries will be automatically converted from the current user's timezone to UTC. Thus, this query:

	$sf_user->setTimezone('America/New_York');
	
	$q = Doctrine_Query::create()
       ->from('Events e')
       ->where('e.start_date = ?', '2010-06-15 12:00:00');

Will be tranlsated into this, prior to executing the query:

	SELECT * FROM events WHERE start_date = "2010-06-15 16:00:00";

Check the test file `/plugins/sfDoctrineTimezoneablePlugin/test/unit/sfDoctrineTimezoneablePluginTest.php` for some other examples of queries that will work / won't work.
	
Please note that only columns of the type date, datetime, or timestamp will be converted. If you reference a date in a varchar or text column, it won't be handled.

### Querying the database in UTC

There may be cases where you don't want to query the database in the user's timezone. Since any dates you put in your DQL query will be automatically converted, you have two options:

1. Set the user timezone temporarily to whatever you want to query in
2. Use raw SQL queries

Raw SQL queries won't be parsed and converted like DQL queries are. You can read about them here: http://www.doctrine-project.org/projects/orm/1.2/docs/manual/native-sql/en

## Possible Issues

+ If your models do not extend off of sfDoctrineRecord (check by looking at your *Base classes) then the automatic building won't work. You will have to manually set your models to extend off of sfDoctrineTimezoneRecord.
+ Directly calling `date_default_timezone_set` to change the timezone will not set the timezone in Symfony's models. You *must* use `$sf_user->setTimezone()` as it calls the event system.
+ If you use `fetchArray` your timezone will not be converted and you will get the UTC value.

## Needed Improvements

There are a number of ways that would make this plugin more ideal. Any developers who have the time and want to implement these, please help out:

+ Figure out a way to catch `get()` and `set()` without having to extend sfDoctrineRecord and set all models to extend off of sfDoctrineTimezoneRecord
+ Place user's timezone in an attribute tied to the user rather than hijacking `date_default_timezone_set`
+ Attempt to override the hydrator so that we can use `fetchArray` or other methods and still get the timezone conversion.