/*** investigate ***/

function CheckField(event, boxid, fieldid) {
	if(document.getElementById(textid).value == "") {
		document.getElementById(boxid).checked = false;
	} else {
		document.getElementById(boxid).checked = true;
	}
}



function boxcheck(event, fieldid, select = false)
{
	var keys;
	if(select) {
		keys = get_forbidden_keys_select();
	} else {
		keys = get_forbidden_keys();
	}
	if(!check_forbidden_keys(event, keys)) {
		document.getElementById(fieldid).checked = true;
	}
}

function checkthebox(event, fieldid)
{
	boxcheck(event, fieldid);
}

function checkthebox_select(event, fieldid)
{
	boxcheck(event, fieldid, true);
}

function uncheck_empty(textid, boxid)
{
	if(document.getElementById(textid).value == "") {
		document.getElementById(boxid).checked = false;
	}
}


/*** ??? ***/
function disable_nobox(fieldname, field_array)
{
	var disable = true;
	for(var i in field_array) {
		disable = disable && !document.getElementById(field_array[i]).checked;
	}
	eval("document.forms[0]." + fieldname + ".disabled = disable;");
}
