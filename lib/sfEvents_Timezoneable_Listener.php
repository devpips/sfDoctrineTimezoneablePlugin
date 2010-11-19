<?php

class sfEvents_Timezoneable_Listener {
	
	public static function listenToUserMethodNotFound(sfEvent $event){
  		switch($event['method']){
  			case 'setTimezone':
  				self::setTimezone($event, $event->getSubject(), $event['arguments']);
  				return true;
  			case 'getTimezone':
  				self::getTimezone($event, $event->getSubject(), $event['arguments']);
  				return true;
  			default:
  				return false;
  		}
  	}
  	
  	protected static function getTimezone(sfEvent $e, sfUser $subject, $args){
  		$e->setReturnValue(date_default_timezone_get());
  	}
  	
  	protected static function setTimezone(sfEvent $e, sfUser $subject, $args){
  		date_default_timezone_set($args[0]);
  		sfProjectConfiguration::getActive()->getEventDispatcher()->notify(new sfEvent($subject, 'user.change_timezone', array('timezone' => $args[0])));
  	}
  	
  	public static function listenToModelBuilderOptions(sfEvent $event, $result){
  		if($result['baseClassName'] == 'sfDoctrineRecord')
  			$result['baseClassName'] = 'sfDoctrineTimezoneRecord';
  		return $result;
  	}
  	
}