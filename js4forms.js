// js4forms.js
// Created on: Fri 2015-03-20 14:19:13 CET
// Version 0.1

var bu = false;
//console.log("put debug messages here");


// ConfirmCancel
// use: <body onbeforeunload="return ConfirmCancel()">
function ConfirmCancel() {
	if(bu) {
		return "Are you sure you want to leave this page?";
	}
	return null;
}

// FieldAction
// use: <input onwhatever="FieldAction()" />
function FieldAction() {
	document.forms[0].submit.disabled = false;
}

// FieldChanged
// use: <input onchange="FieldChanged()" />
function FieldChanged() {
	bu = true;
	document.forms[0].submit.disabled = false;
}

// ResetForm
// use: <input type="reset" onclick="ResetForm()" />
function ResetForm() {
	bu = false;
	document.forms[0].submit.disabled = true;
}

// SubmitForm
// use: <input type="submit" value="submit" onclick="SubmitForm()" />
function SubmitForm() {
	bu = false;
}

// ConfirmErase
// use: <input type="submit" value="erase" onclick="return ConfirmErase(...)" />
function ConfirmErase(content, english=true) {
	var q = "Are you sure you want to delete ";
	if(!english) {
		q = "Es-tu sur de vouloir effacer ";
	}
	q += content;
	q += "?";
	return confirm(q);
}

