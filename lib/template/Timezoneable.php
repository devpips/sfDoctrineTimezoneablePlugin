<?php

class Doctrine_Template_Timezoneable extends Doctrine_Template {
	
	public function setTableDefinition(){
		$this->addListener(new Doctrine_Template_Listener_Timezoneable($this->_options));
	}
	
}