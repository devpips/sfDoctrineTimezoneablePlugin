<?php

if (sfConfig::get('app_sf_doctrine_timezoneable_builder_enabled', true))
	$this->dispatcher->connect('doctrine.filter_model_builder_options', array('sfEvents_Timezoneable_Listener','listenToModelBuilderOptions'));
	
$this->dispatcher->connect('user.method_not_found', array('sfEvents_Timezoneable_Listener','listenToUserMethodNotFound'));
$this->dispatcher->connect('user.change_timezone', array('sfDoctrineTimezoneRecord', 'listenToChangeTimezoneEvent'));