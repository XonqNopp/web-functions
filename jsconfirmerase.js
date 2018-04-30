function ConfirmErase(language, type, id = "") {
	//console.log("confirmerase in da place");
	var question = "";
	if(type != "" && id != "") {
		type += " ";
	}
	if(id != "" && parseInt(id, 10) === id) {
		/*** Only if id is numeric ***/
		id = "#" + id;
	}
	if(language == "english") {
		question = "Are you sure you want to delete " + type + id + "?";
	} else {
		question = "Es-tu sur de vouloir effacer " + type + id + " ?";
	}
	return confirm(question);
}
