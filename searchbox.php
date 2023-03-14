<?php
function search_box($fieldname,$boxname,$the_which,$submit,$default,$css)
{
	/* This function displays in a table a checkbox, a legend and a text field. It is used to search according to several criteria, allowing the user to choose which criteria have to be considered.
	 *
	 * fieldname : name of the text field
	 * boxname   : name of the box field
	 * the_which : array containing booleans associate to the fieldname
	 * submit    : name of the submit button
	 * default   : default value for the text field
	 * css       : css style, root for all (_$fieldname,_check,_legend,_field)
	 */
	$back = "<!---------- " . strtoupper($fieldname) . " ---------->\n";
	$back .= "<tr class=\"$css $css" . "_$fieldname\">\n";
	$back .= "<td class=\"$css" . "_check\">\n";
	$back .= "<input id=\"$fieldname\" type=\"checkbox\" tabindex=\"-1\" name=\"$boxname" . "[]\" value=\"$fieldname\" onclick=\"focus_id('f_$fieldname'); enablesubmit(event,'$submit');\" ";
	if( $the_which[$fieldname] ) {
		$back .= "checked=\"checked\" ";
	}
	$back .= "/>\n";
	$back .= "</td>\n";
	$back .= "<td class=\"$css" . "_legend\" onclick=\"checkthebox(event,'$fieldname'); focus_id('f_$fieldname');\">\n";
	$back .= $fieldname;
	$back .= "</td>\n";
	$back .= "<td class=\"$css" . "_field\">\n";
	$back .= "<input id=\"f_$fieldname\" type=\"text\" size=\"80\" name=\"$fieldname\" onchange=\"checkthebox(event,'$fieldname'); enablesubmit(event,'$submit')\" onkeyup=\"checkthebox(event,'$fieldname'); enablesubmit(event,'$submit')\" onblur=\"uncheck_empty('f_$fieldname','$fieldname')\" value=\"$default\" />\n";
	$back .= "</td>\n";
	$back .= "</tr>\n";
	return $back;
}

function date_box($start,$boxname,$the_which,$submit,$default_year,$default_type,$css)
{
	/* This function displays in a table a checkbox, a legend and two date fields, an operator and a year. It is used to search according to several criteria, allowing the user to choose which criteria have to be considered.
	 *
	 */
	$back = "<!---------- DATE ---------->\n";
	$back .= "<tr class=\"$css $css" . "_Date\">\n";
	$back .= "<td class=\"$css" . "_check\">\n";
	$back .= "<input id=\"Date\" type=\"checkbox\" tabindex=\"-1\" name=\"$boxname" . "[]\" value=\"Date\" onclick=\"focus_id('f_Date'); enablesubmit(event,'$submit');\" ";
	if( $the_which["Date"] ) {
		$back .= "checked=\"checked\" ";
	}
	$back .= "/>\n";
	$back .= "</td>\n";
	$back .= "<td class=\"$css" . "_legend\" onclick=\"checkthebox(event,'Date'); focus_id('f_Date');\">\n";
	$back .= "Date";
	$back .= "</td>\n";
	$back .= "<td class=\"$css" . "_field\">\n";
	$back .= "<select id=\"f_Date\" name=\"date_type\" onchange=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\" onkeypress=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\" onclick=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\">\n";
	$back .= "<option value=\"gt\"";
	if( $default_type == "gt" ) {
		$back .= "selected=\"selected\" ";
	}
	$back .= ">more recent than</option>\n";
	$back .= "<option value=\"ge\"";
	if( $default_type == "ge" ) {
		$back .= "selected=\"selected\" ";
	}
	$back .= ">more recent than or equal to</option>\n";
	$back .= "<option value=\"lt\"";
	if( $default_type == "lt" ) {
		$back .= "selected=\"selected\" ";
	}
	$back .= ">older than</option>\n";
	$back .= "<option value=\"le\"";
	if( $default_type == "le" ) {
		$back .= "selected=\"selected\" ";
	}
	$back .= ">older than or equal to</option>\n";
	$back .= "<option value=\"x\" ";
	if( $default_type == "x" ) {
		$back .= " selected=\"selected\"";
	}
	$back .= ">from year</option>\n";
	$back .= "</select>\n";
	$back .= "<select name=\"Date\" onchange=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\" onkeypress=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\"  onclick=\"checkthebox_select(event,'Date'); enablesubmit_select(event,'search')\">\n";
	$this_year = date('Y');
	for( $i = $this_year; $i > $start; $i-- ) {
		$back .= "<option value=\"$i\"";
		if( $default_year == $i ) {
			$back .= " selected=\"selected\"";
		}
		$back .= ">$i</option>\n";
	}
	$back .= "</select>\n";
	$back .= "</td>\n";
	$back .= "</tr>\n";
	return $back;
}

function search_buttons($butname,$butval,$resetval,$cancelval,$disbut,$fields_array,$css)
{
	$back = "<!---------- BUTTONS ---------->\n";
	$back .= "<tr class=\"$css $css" . "_buttons\">\n";
	$back .= "<td></td>\n";
	$back .= "<td class=\"$css" . "_buts\" colspan=\"2\">\n";
	$fields_str = implode("','",$fields_array);
	$back .= "<input type=\"submit\" name=\"$butname\" value=\"$butval\"$disbut onfocus=\"disable_nobox('$butname',['$fields_str'])\" onmouseover=\"disable_nobox('$butname',['$fields_str'])\" />\n";
	$back .= "<input type=\"reset\" value=\"$resetval\" onclick=\"disablesubmit('$butname')\" />\n";
	$back .= "<input type=\"submit\" name=\"no\" value=\"$cancelval\" />\n";
	$back .= "</td>\n";
	$back .= "</tr>\n";
	return $back;
}
?>
