
var __onGridCellEditMutex = {'cell': null, 'row': null};
var onGridCellEdit = function(stage,row,cell,grid){
	
	var broker = dhtmlxGridBroker.getInstance(grid);
	
	if ( stage == 2 ) {
		if ( __onGridCellEditMutex['cell'] == cell && __onGridCellEditMutex['row'] == row ){
			return;
		} else {
			__onGridCellEditMutex['cell'] = cell;
			__onGridCellEditMutex['row'] = row;
		}
		broker.registerChange(row,cell);
		//broker.updateRow(row);
		if ( row == grid.rowsAr.length-1 ){
			alert("here ("+row+','+cell+')');
			grid.addRow(grid.rowsAr.length);
		} else {
			
		}
		broker.setCurrentRow(null);
		__onGridCellEditMutex['cell'] = null;
		__onGridCellEditMutex['row'] = null;
	} else {
		broker.setCurrentRow(row);
	}
	return true;

};

/**
 * Data holder class to mark the change status of a row.
 */
function RowChangeInfo(row,cells){
	this.row = row;
	if ( cells.length ) this.cells = cells;
	else {
		this.cells = [];
		this.cells.push(cells);
	}
}

/**
 * Marks a cell as changed.
 */
RowChangeInfo.prototype.cellChanged = function(cell){
	if ( !this.isChanged(cell) ){
		this.cells[this.cells.length] = cell;
	}
}

/**
 * Checks to see if a cell has been changed.  If the cell param is omitted,
 * this checks to see if there are any changes in the row.
 *
 */
RowChangeInfo.prototype.isChanged = function(cell){
	if ( !cell ) return this.cells.length > 0;
	
	for ( var i=0; i<this.cells.length; i++){
		if ( this.cells[i] == cell ) return true;
	}
	return false;
}

function dhtmlxGridBroker_daemon(broker){
	this.broker = broker;
}	

dhtmlxGridBroker_daemon.prototype.run = function(){

	while ( this.broker.hasNextUpdate() ){
		var rowChangeInfo = this.broker.nextUpdate();
		if ( rowChangeInfo.row != this.broker.getCurrentRow() ){
			alert("We are now updating the row");
			this.broker.updateRow(rowChangeInfo.row);
		}
	}
	this.broker.stopDaemon();
}


/**
 * A data broker to update/retrieve information from the grid's server side.
 * @param grid A dhtmlGridObject which is being brokered.
 *
 */
function dhtmlxGridBroker(grid, serverurl){
	this.grid = grid;
	this.changes = [];
	this.daemon = null;
	this.currentRow = null;
	this.nextUpdateVal = null;
	this.serverurl = serverurl;
	
	

}

/**
 * Static variable containing all grids in the system.
 */
dhtmlxGridBroker.grids = {};

dhtmlxGridBroker.SERVER_URL = "";//"/~shannah/dataface/tests/dhtmlxGrid_testpage.php";

/**
 * Retrieves the broker associated with the specified grid.
 */
dhtmlxGridBroker.getInstance = function(grid){
	var grids = dhtmlxGridBroker.grids;
	if ( !grids[grid] ) grids[grid] = new dhtmlxGridBroker(grid, dhtmlxGridBroker.SERVER_URL);
	return grids[grid];
}

/**
 * Registers a change to the grid in the specified row and cell.
 */
dhtmlxGridBroker.prototype.registerChange = function(row,cell){
	var rowInfo = this.getRowInfo(row);
	if ( !rowInfo ){
		rowInfo = new RowChangeInfo(row,cell);
		this.setRowInfo(rowInfo);
	} else {
		rowInfo.cellChanged(cell);
	}
	
	// Start the daemon to do updates.  We use a daemon
	// rather than running the update right now because we need to know
	// if the user is still editing another cell in the same row.
	if ( !this.daemonRunning() ){
		
		this.startDaemon();
	}
		
}

dhtmlxGridBroker.prototype.setCurrentRow = function(row){
	this.currentRow = row;
}

dhtmlxGridBroker.prototype.getCurrentRow = function(){
	return this.currentRow;
}

dhtmlxGridBroker.prototype.getRowInfo = function(row){
	for ( var i=0; i<this.changes.length; i++){
		if ( !this.changes[i] ) continue;
		if ( this.changes[i].row == row ) return this.changes[i];
	}
	return null;
}

dhtmlxGridBroker.prototype.setRowInfo = function(rowInfo){
	for ( var i=0; i<this.changes.length; i++){
		if ( !this.changes[i] ) continue;
		if ( this.changes[i].row == rowInfo.row ) {
			this.changes[i] = rowInfo;
			return;
		}
	}
	this.changes.push(rowInfo);
}

dhtmlxGridBroker.prototype.hasNextUpdate = function(){
	for ( var i=0; i<this.changes.length; i++ ){
		if ( !this.changes[i] ) continue;
		if ( this.changes[i].row != this.currentRow ) {
			this.nextUpdateVal = this.changes[i];
			return true;
		}
	}
}

dhtmlxGridBroker.prototype.nextUpdate = function(){
	return this.nextUpdateVal;
}

dhtmlxGridBroker.prototype.updateRow = function(row){

	var http = getHTTPObject();
	http.open('POST', this.serverurl, true);

	//request.onreadystatechange = this.handleUpdateResponse;
	//Send the proper header information along with the request
	//alert("here");
	var params = "-action=update_grid&-gridid="+escape(this.grid.getUserData(null,'id'))+"&-rowid="+escape(row)+"&"+this.buildDataQuery(row);
    //alert(params);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	http.setRequestHeader("Content-length", params.length);
	http.setRequestHeader("Connection", "close");
	http.broker = this;

	// This should really go into an intermediate state "pending" before
	// actually clearing the changes - in case there is a connection issue.
	// But for now, we will just clear the changes as soon as we send the 
	// request.
	this.clearChanges(row);
	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 /*&& http.status == 200*/) {
			alert(http.responseText);
			//this.broker.clearChanges(row);
		}
	}
	http.send(params);
	
	
	
}

dhtmlxGridBroker.prototype.buildDataQuery = function(row){
	//alert(this.grid.obj._rows + " " + row);
	//var rowid = this.grid.obj._rows(row);
	//alert(rowid);

	var row2 = this.grid.obj._rows(parseInt(row));


	var arr = this.grid._getRowArray(row2);
				//alert("Building data query");
	var str = [];

	for ( var i=0; i<arr.length; i++){
		var cell = this.grid.cells(row, i);
		if ( cell.wasChanged() ) {
			
			str.push(escape('cells['+i+']')+'='+escape(arr[i]));
		}
	}

	return str.join('&');
}



dhtmlxGridBroker.prototype.clearChanges = function(row){
	for (var i=0; i<this.changes.length; i++ ){
		if ( !this.changes[i] ) continue;
		if ( this.changes[i].row == row ) this.changes[i] = null;
	}
}
dhtmlxGridBroker.prototype.daemonRunning = function(){
	return this.daemon != null;
}

dhtmlxGridBroker.prototype.startDaemon = function(){
	this.daemon = new dhtmlxGridBroker_daemon(this);
	if ( !window.__daemons ) window.__daemons = [];
	this.daemon.id = window.__daemons.length;
	window.__daemons[window.__daemons.length] = this.daemon;
	window.setTimeout('window.__daemons['+this.daemon.id+'].run();', 2000);
}

dhtmlxGridBroker.prototype.stopDaemon = function(){
	this.daemon = null;
}	