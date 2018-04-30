/*** deprecated ***/

function check_forbidden_keys(event,keys)
{
	var back = false;
	if( event.keyCode != "undefined" ) {
		for( var i in keys ) {
			if( keys[i] == event.keyCode ) {
				back = true;
			}
		}
	}
	return back;
}

function get_forbidden_keys()        { return [9, 37, 38, 39, 40, 16, 17, 18, 20, 27, 224]; }
function get_forbidden_keys_select() { return [9, 37,     39,     16, 17, 18, 20, 27, 224]; }

