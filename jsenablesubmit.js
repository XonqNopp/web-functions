function enablesubmit(event, fieldname)
{
	//var keys = get_forbidden_keys();
	//if(!check_forbidden_keys(event,keys)) {
		if(!fieldname) {
			fieldname = "submit";
		}
		eval("document.forms[0]." + fieldname + ".disabled = false;");
	//}
}

function enablesubmit_select(event, fieldname)
{
	//var keys = get_forbidden_keys_select();
	//if(!check_forbidden_keys(event,keys)) {
		if(!fieldname) {
			fieldname = "submit";
		}
		eval("document.forms[0]." + fieldname + ".disabled = false;");
	//}
}

function disablesubmit(fieldname)
{
	if(!fieldname) {
		fieldname = "submit";
	}
	eval("document.forms[0]." + fieldname + ".disabled = true;");
}

function doEnableSubmit(fieldname, fields_onchange = [], fields_onkeyup = [], fields_onclick = [])
{
	if(fields_onchange.length > 0) {
		for(var x in fields_onchange) {
			document.getElementById(fields_onchange[x]).onchange = eval("document.forms[0]." + fieldname + ".disabled = false;");
		}
	}
	if(fields_onkeyup.length > 0) {
		for(var x in fields_onkeyup) {
			document.getElementById(fields_onkeyup[x]).onkeyup = eval("document.forms[0]." + fieldname + ".disabled = false;");
		}
	}
	if(fields_onclick.length > 0) {
		for(var x in fields_onclick) {
			document.getElementById(fields_onclick[x]).onclick = eval("document.forms[0]." + fieldname + ".disabled = false;");
		}
	}
}
