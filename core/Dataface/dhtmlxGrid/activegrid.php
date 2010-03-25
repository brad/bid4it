<?php
import('Dataface/dhtmlxGrid/grid.php');

/**
 * This class is an Active grid, meaning that it is bound to a data-store and
 * exists across multiple requests.
 * <p>A grid is assigned an ID which is used to automatically reload it each time
 *    it is loaded.  The grid data is stored in session data, until a call is
 *    made to <code>commit()</code> when it is backed up to the database.
 * </p>
 */
class Dataface_dhtmlxGrid_activegrid extends Dataface_dhtmlxGrid_grid {

	var $id;
	var $query;
	
	
	function session_key($id=null){
		if ( !isset($id) ) $id = $this->id;
		return 'Dataface_dhtmlxGrid_activegrid_data?'.$id;
	}
    
    /**
     * Creates a grid with the given id or a query.
     * @param mixed $query Either the grid id, or a query array.
     *
     */
	function Dataface_dhtmlxGrid_activegrid($query, $name='mygrid'){
		// We are building a grid from a Dataface Query array.
		$q =& $query;
		
		
		
		if ( isset($q['-columns']) ){
			if ( is_array($q['-columns']) ) $columns = $q['-columns'];
			else $columns = explode(',',$q['-columns']);
		} else {
			$columns = null;
		}
		if ( isset($q['-records']) ){
			$this->Dataface_dhtmlxGrid_grid($columns, $q['-records'], $name, $q['-parent_id'], $q['-relationship']);
		}
		// At this point columns should be an associative array of column definitions.
		
		else if ( isset($q['-relationship']) ){
			// We are looking at the related records of a particular record.
			$record = df_get_record($q['-table'], $q);
			$records = $record->getRelatedRecordObjects($q['-relationship']);
			
			// We want the keys to be unique identifiers for the record that it
			// points to, so we will rekey the array using the 
			// Dataface_RelatedRecord::getId() method
			//$related_records = $this->keyById($related_records);
			
			if ( !$columns ){
				$table =& Dataface_Table::loadTable($q['-table']);
				$relationship =& $table->getRelationship($q['-relationship']);
				$columns = $relationship->getColumnNames();
			}
			$this->Dataface_dhtmlxGrid_grid($columns, $records, $name, $record->getId(), $q['-relationship']);
			// now that we have created the grid.. we need to generate
			// and id for it and save the data in session vars
			
		} else {
			// We are not looking for related records - we are looking for 
			// actual records.
			$records = df_get_records_array($q['-table'], $q);
			
			// We want the keys to be unique identifiers for the record that
			// it points to, so we will rekey the array using the
			// Dataface_Record::getId() method.
			//$records = $this->keyById($records);
			if ( !$columns ){
				$table =& Dataface_Table::loadTable($q['-table']);
				$columns = $table->fields();
				
			}

			$this->Dataface_dhtmlxGrid_grid($columns, $records, $name, $q['-table']);
			
		
		}

		$this->id = $this->update(null);

		
		
	}
	

	/**
	 * Takes an array of Dataface_Record or Dataface_RelatedRecord
	 * objects and outputs a new array where the keys are the result
	 * of the getId() method on each associated record object.
	 *
	 * @param array An array of Dataface_Record or Dataface_RelatedRecord
	 * 				objects.  May be mixed.
	 * @returns array An array of Dataface_Record objects identical to the 
	 *		input array except rekeyed on the result of getId() for each.
	 */
	function keyById(&$arr){
		$out = array();
		foreach ( array_keys($arr) as $key){
			$out[$arr[$key]->getId()] =& $arr[$key];
		}
		return $out;
	}
	
	
	/**
	 * Generates an new id for the session data of the grid.
	 * @returns integer
	 */
	function generateNewId(){
		$id = null;
		//while ( $this->getSessionData($id) !== null ){
			$id = rand(0,100000);
		//}
		return $id;
	}
	
	function getSessionData($id){
		if ( isset( $_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]) ) 
			return  $_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)];
			
		return null;
	}
	
	/**
	 * Returns the session data for a particular id.
	 * The data is stored as an array of associative arrays of values.
	 * @param integer $id The id of the grid that we are retrieving.
	 * @returns array
	 */
	function getGrid($id){
		if ( !isset($id) ) return null;

		if ( isset($_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]) ){
			//echo $_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)];
			return unserialize($_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]);
		}
		return null;
	}
	
	function removeGrid($id){
		if ( isset($_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]) ){
			unset($_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]);
		}
	}
	
	/**
	 * Sets the session data.
	 * @param integer $id The ID of the grid so that it can be recovered later.
	 * @param array $data The actual data for the grid.. should be an array of associative arrays of values.
	 * @returns integer The id of the resulting grid.
	 */
	function update($id=null, $grid=null){
		if ( !isset($grid) ) $grid =& $this;
		if ( !isset($id) ){
			
			if ( isset($grid->id) ) {
				$id = $grid->id;

			}
			else {
				$id = $grid->generateNewId();
				$grid->id = $id;
			}
		}
		unset($_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)]);
		$_SESSION[Dataface_dhtmlXGrid_activegrid::session_key($id)] = serialize($grid);
		return $id;
	}
	
	function clearGrids(){
		$els  = preg_grep('/^'.preg_quote(Dataface_dhtmlXGrid_activegrid::session_key('')).'/', array_keys($_SESSION));
		foreach ($els as $el){
			unset($_SESSION[$el]);
		}
	}


}
