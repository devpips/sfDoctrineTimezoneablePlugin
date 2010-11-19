<?php

abstract class sfDoctrineTimezoneRecord extends sfDoctrineRecord {
	
	static protected
	  $_initialized     = false,
	  $_defaultTimezone = 'UTC',
	  $_dbTimezone      = 'UTC';
	  
	public function setUp(){
		$timezonable = new Doctrine_Template_Timezoneable();
        $this->actAs($timezonable);
        
		parent::setUp();
	}
	
	public function construct(){
		parent::construct();
		self::initializeTimezone();
	}
	
	static public function initializeTimezone(){
		if(!self::$_initialized){
			if (sfContext::hasInstance() && $user = sfContext::getInstance()->getUser()){
				self::$_defaultTimezone = $user->getTimezone();
			}

			self::$_initialized = true;
		}
	}
	
	static public function listenToChangeTimezoneEvent(sfEvent $event){
		self::$_defaultTimezone = $event['timezone'];
	}
	
	static public function setDefaultTimezone($timezone){
		self::initializeTimezone();
    	self::$_defaultTimezone = $timezone;
	}
	
	static public function getDefaultTimezone(){
		self::initializeTimezone();
		
		if (!self::$_defaultTimezone){
      		throw new sfException('The default timezone has not been set');
    	}
    	
    	return self::$_defaultTimezone;
	}
	
	static public function getDoctrineTimestamp($date, $format = 'U'){
		$dt = new DateTime($date, new DateTimeZone(self::getDefaultTimezone()));
		$dt->setTimezone( new DateTimeZone(self::$_dbTimezone) );
		return $dt->format($format);
	}
	
	public function get($fieldName, $load = true){
		if($this->getTable()->hasField($fieldName)){
			$type = $this->getTable()->getTypeOf($fieldName);
			if($type == 'date' || $type == 'timestamp' || $type == 'datetime'){
				$date = parent::get($fieldName, $load);
				$dt   = new DateTime($date, new DateTimeZone(self::$_dbTimezone));
				$tz   = new DateTimeZone(self::getDefaultTimezone());
				
				$dt->setTimezone($tz);
				
				// for some reason, we have to do this or else it breaks in UTC when saving
				$parent = parent::get($fieldName, $load);
				if(is_null($parent))
					return $parent;
				
				return $dt->format('Y-m-d H:i:s');
			}
		}
		return parent::get($fieldName, $load);
	}
	
	public function set($fieldName, $value, $load = true){
		if($this->getTable()->hasField($fieldName)){
			$type = $this->getTable()->getTypeOf($fieldName);
			if($type == 'date' || $type == 'timestamp' || $type == 'datetime'){
				$dt = new DateTime($value, new DateTimeZone(self::getDefaultTimezone()));
				$tz = new DateTimeZone(self::$_dbTimezone);
				
				$dt->setTimezone($tz);
				
				return parent::set($fieldName, $dt->format('Y-m-d H:i:s'), $load);
			}
		}
		return parent::set($fieldName, $value, $load);
	}
	
	public function getDateTimeObject($dateFieldName, $in_timezone = null){
		$type = $this->getTable()->getTypeOf($dateFieldName);
    	if ($type == 'date' || $type == 'timestamp' || $type == 'datetime'){
    		$dt = new DateTime($this->get($dateFieldName), new DateTimeZone(self::getDefaultTimezone()));
    		if($in_timezone){
    			$dt->setTimezone(new DateTimeZone($in_timezone));
    		}
    		return $dt;
    	} else {
    		throw new sfException('Cannot call getDateTimeObject() on a field that is not of type date or timestamp.');
    	}
	}
	
}