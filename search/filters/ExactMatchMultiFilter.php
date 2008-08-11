<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Checks if a value is in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * 
 * @todo Add negation (NOT IN)6
 */
class ExactMatchMultiFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		
		// hack
		// PREVIOUS $values = explode(',',$this->getValue());
		$values = array();
		if (is_string($this->getValue())) {
			$values = explode(',',$this->getValue());
		}
		else {
			foreach($this->getValue() as $v) {
				$values[] = $v;
			}
		}
		
		
		if(! $values) return false;
		for($i = 0; $i < count($values); $i++) {
			if(! is_numeric($values[$i])) {
				// @todo Fix string replacement to only replace leading and tailing quotes
				$values[$i] = str_replace("'", '', $values[$i]);
				$values[$i] = Convert::raw2sql($values[$i]);
			}
		}
		$SQL_valueStr = "'" . implode("','", $values) . "'";
		
		return $query->where("{$this->getDbName()} IN ({$SQL_valueStr})");
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
?>