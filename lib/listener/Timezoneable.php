<?php

class Doctrine_Template_Listener_Timezoneable extends Doctrine_Record_Listener {
    /**
     * We need to parse any select statement and convert the dates to something the DB can understand
     * @param Doctrine_Event $event
     */
  	public function preDqlSelect(Doctrine_Event $event) {
		$this->parseDqlQueryColumns($event);
  	}
  	
  	/**
  	 * We need to parse any delete statement and convert the dates to something the DB can understand
  	 * @param $event
  	 */
  	public function preDqlDelete(Doctrine_Event $event){
  		$this->parseDqlQueryColumns($event);
  	}
  	
  	/**
  	 * We need to parse any update statement and convert the dates to something the DB can understand
  	 * @param $event
  	 */
  	public function preDqlUpdate(Doctrine_Event $event){
  		if ( !$this->dqlUpdateCalled )
  			$this->parseDqlQueryColumns($event);
  			
  		$this->dqlUpdateCalled = true;
  	}
  	
  	/**
  	 * Parses the DQL query for the columns we are to act on and converts the dates to UTC
  	 * @param Doctrine_Event $event
  	 */
  	protected function parseDqlQueryColumns(Doctrine_Event $event){
  		$params  = $event->getParams();
		$query   = $event->getQuery();
		$qparams = $query->getParams();
		
		$where   = $query->getDqlPart('where');
		if(!is_array($where)) $where = array();
		$param_count = 0;
		foreach($where as $clause){
			list($col,)           = explode(' ', $clause);
			if(strpos($col, '.') !== false)
				list($alias, $column) = explode('.', $col);
			else
				$column = $col;
			
			$column = trim($column);
			
			if($event->getInvoker()->getTable()->hasColumn($column)){
				$type = $event->getInvoker()->getTable()->getTypeOf($column);
				if($type == 'date' || $type == 'datetime' || $type == 'timestamp'){
					// how many ?'s are in this caluse that we need to fix for?
					$params_in_clause = substr_count($clause, '?');
					
					for($i = 0; $i < $params_in_clause; $i++){
						$index = $i + $param_count;
						
						// get the corresponding value
						$value = $qparams['where'][$index];
							
						// convert this value to UTC
						$qparams['where'][$index] = sfDoctrineTimezoneRecord::getDoctrineTimestamp($value, 'Y-m-d H:i:s T');
					}
				}
			}
			// count the ? so we know which param we're going to need...
			$param_count += substr_count($clause, '?');
		}
		
		$query->setParams($qparams);
  	}
	
}