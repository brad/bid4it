dataGridFieldFunctions = new Object()

dataGridFieldFunctions.removeFieldRow = function(node) {
    /* Remove the row in which the given node is found */
    
    var row = this.getParentElement(node, 'TR');
    var tbody = this.getParentElement(row, 'TBODY');
    var form = this.getParentElement(row, 'FORM');
    var inputs = row.getElementsByTagName("input");
    for ( var i=0, max=inputs.length; i<max; i++){
    	if ( inputs[i].getAttribute('name').match(/\[__id__\]/) ){
    		//var del_input = inputs[i].cloneNode(true);
    		var del_input = this.cloneNode(inputs[i]);
    		var new_name = del_input.getAttribute('name').replace(/^([^\[]+)\[/, '$1[__deleted__][]');
    		del_input.setAttribute('name', new_name);
    		form.appendChild(del_input);
    		break;
    	}
    }
    tbody.removeChild(row);
}

dataGridFieldFunctions.cloneNode = function(el){
	var copy = el.cloneNode(false);

	// This is not a text node, so we will loop through all of its child
	// nodes and recursively change the urls and email addresses in them.
	for ( var i=0, len=el.childNodes.length; i<len; i++ ){
		if ( !el.childNodes[i] ) continue;
		
		if ( el.childNodes[i].tagName == 'SCRIPT' ) continue;
			// We leave scripts alone because we don't want to screw up
			// any javascripts.
		
		copy.appendChild(this.cloneNode(el.childNodes[i]));
			// Now we recursively call ourselves.
	}
	return copy;
	

}

dataGridFieldFunctions.getInputOrSelect = function(node) {
    /* Get the (first) input or select form element under the given node */
    
    var inputs = node.getElementsByTagName("input");
    if(inputs.length > 0) {
        return inputs[0];
    }
    
    var selects = node.getElementsByTagName("select");
    if(selects.length > 0) {
        return selects[0];
    }
    
    var textareas = node.getElementsByTagName("textarea");
    if ( textareas.length > 0) {
    	return textareas[0];
    }

    return null;
}

dataGridFieldFunctions.setScriptText = function(script, text){
	try {
		script.innerHTML = text;
	} catch (e){
		script.text = text;
	}
}

dataGridFieldFunctions.getScriptText = function(script){
	if ( script.innerHTML ) return script.innerHTML;
	return script.text;
}


dataGridFieldFunctions.addRowOnChange = function(e) {
	/* Add a new row when changing the last row */

    // XXX: Generalize window.event for windows
    // Grab current node, replicate, remove listener, append
    var currnode = window.event ? window.event.srcElement : e.currentTarget;

    // XXX Should add/remove event listeners via JS, but IE has
    // non-standard methods.  Not hard, but for now, just check 
    // if we are the last row.  If not, bail.
    
    var tbody = this.getParentElement(currnode, "TBODY");
    var tr = this.getParentElement(currnode, "TR");
    var rows = tbody.getElementsByTagName("TR");
   
    if(rows.length ==(tr.rowIndex)) {
        // Create a new row
        //var newtr = tr.cloneNode(true);
        var newtr = this.cloneNode(tr);
        var row_id = tr.getAttribute('row_id');
        if ( !row_id ) row_id = tr.getAttribute('df:row_id');
        
        row_id = parseInt(row_id)+1;
        newtr.setAttribute('df:row_id', row_id);
        var scripts = tr.getElementsByTagName('SCRIPT');
        var scriptTexts = [];
        for ( var i=0,imax=scripts.length; i<imax; i++){
        	scriptTexts[scriptTexts.length] = this.getScriptText(scripts[i]);
        }
        scripts = scriptTexts;
        
            
        // Turn on hidden button images for current node
        var imgs = tr.getElementsByTagName("img");
        for(var i=0; i<imgs.length; i++)
            imgs[i].style.display = "block";
                                                       
        // Clear content of newly created cells that were duplicated from 
        // current cell
        cells = newtr.getElementsByTagName("td");
        for(var i=0; i<cells.length; i++) {
            
            td = cells[i];
            
            input = this.getInputOrSelect(td);
            if(input == null) 
                continue;

            if ( !input.getAttribute('name').match(/\[__id__\]/) ) input.value = ""
            var inputname = input.getAttribute('name');
            inputname = inputname.replace(/^([^\]]+)\[[0-9]+\]/, '$1['+row_id+']');
            input.setAttribute('name', inputname);

            
        }
        
        newtr = this.cloneTag(newtr, row_id, scripts);
        
        
        tr.parentNode.appendChild(newtr);
        this.updateOrderIndex(tbody);
        for (var i=0; i<newtr.copiedScripts.length; i++){
        	newtr.appendChild(newtr.copiedScripts[i]);
        }
     

    }
}


dataGridFieldFunctions.all = function(tag){
	var out = [];
	var stack = [tag];
	while ( stack.length > 0 ){
		var curr = stack.pop();
		out.push(curr);
		for ( var i=0; i<curr.childNodes.length; i++){
			stack.push(curr.childNodes[i]);
		}
	}
	return out;
}

dataGridFieldFunctions.cloneTag = function(tag, row_id, scripts){
	// Now we try to fix elements that have javascript modifications
	// e.g. js calendar, yahoo widgets etc...
	var subels = this.all(tag);
	var max;
	tag.copiedScripts = [];
	for (var i=0,imax=scripts.length; i<imax; i++){
		var script = document.createElement('script');
		script.type = 'text/javascript';
		this.setScriptText(script, scripts[i]);
		//script.innerHTML = scripts[i];
		tag.copiedScripts[tag.copiedScripts.length] = script;
	}
	for ( var i=0,imax=subels.length; i<imax; i++){
		if (subels[i].getAttribute && subels[i].getAttribute('df:cloneable')){
			
			// This element is cloneable
			var oldid = subels[i].getAttribute('id');
			var newid = oldid+'-'+row_id;
			subels[i].setAttribute('id', newid);
			for ( var j=0, jmax=tag.copiedScripts.length; j<jmax; j++){
				var script_contents = this.getScriptText(tag.copiedScripts[j]);
				
				var regex = new RegExp('[\'"]'+oldid+'[\'"]', "g");
				script_contents = script_contents.replace(regex, '\''+newid+'\'');
				this.setScriptText(tag.copiedScripts[j], script_contents);
				
				
				
			}
			
		}
		
		if ( subels[i].getAttribute && subels[i].getAttribute('df:noClone') ){
			subels[i].parentNode.removeChild(subels[i]);
		}
		
	}
	
	
	
	return tag;

}


dataGridFieldFunctions.getParentElement = function(currnode, tagname) {
    /* Find the first parent node with the given tag name */

    tagname = tagname.toUpperCase();
    var parent = currnode.parentNode;

    while(parent.tagName.toUpperCase() != tagname) {
        parent = parent.parentNode;
        // Next line is a safety belt
        if(parent.tagName.toUpperCase == "BODY") 
            return null;
    }

    return parent;
}


dataGridFieldFunctions.moveRowDown = function(node){
    /* Move the given row down one */
    
    var row = this.getParentElement(node, "TR")
    var tbody = this.getParentElement(row, "TBODY");
    var rows = tbody.getElementsByTagName('TR')
    var idx = null
    
    // We can't use nextSibling because of blank text nodes in some browsers
    // Need to find the index of the row
    for(var t = 0; t < rows.length; t++) {
        if(rows[t] == row) {
            idx = t;
            break;
        }
    }

    // Abort if the current row wasn't found
    if(idx == null)
        return;
        
    // If this was the last row (before the blank row at the end used to create
    // new rows), move to the top, else move down one.
    if(idx + 2 == rows.length) {
        var nextRow = rows.item(0)
        this.shiftRow(row, nextRow)
    } else {
        var nextRow = rows.item(idx+1)
        this.shiftRow(nextRow, row)
    }
    
    this.updateOrderIndex(tbody)

}

dataGridFieldFunctions.moveRowUp = function(node){
    /* Move the given row up one */
    
    var row = this.getParentElement(node, "TR")
    var tbody = this.getParentElement(row, "TBODY");
    var rows = tbody.getElementsByTagName('TR')
    var idx = null
    
    // We can't use nextSibling because of blank text nodes in some browsers
    // Need to find the index of the row
    for(var t = 0; t < rows.length; t++) {
        if(rows[t] == row) {
            idx = t;
            break;
        }
    }
    
    // Abort if the current row wasn't found
    if(idx == null)
        return;
        
    // If this was the first row, move to the end (i.e. before the blank row
    // at the end used to create new rows), else move up one
    if(idx == 0) {
        var previousRow = rows.item(rows.length - 1)
        this.shiftRow(row, previousRow)
    } else {
        var previousRow = rows.item(idx-1)
        this.shiftRow(row, previousRow)
    }
    
    this.updateOrderIndex(tbody)
}

dataGridFieldFunctions.shiftRow = function(bottom, top){
    /* Put node top before node bottom */
    
    bottom.parentNode.insertBefore(bottom, top)   
}

dataGridFieldFunctions.updateOrderIndex = function (tbody) {
    /* Update the hidden orderindex variables to be in the right order */
    
    var xre = new RegExp(/^orderindex__/)
    var idx = 0;
    for (var c = 0; (cell = tbody.getElementsByTagName('INPUT').item(c)); c++) {
        if (cell.getAttribute('id')) {
            if (xre.exec(cell.id)) {
                cell.value = idx++;
            }
        }
    }
}

