<?php
/**
 * All the input for forms are defined here in a common way.
 *
 * Note:
 * To make it the most simple to use, we make singletons.
 * As we cannot overload a method with different args, the method get shall only be defined in classes which are not
 * inherited from (or little), so generic classes cannot implement a get methd.
 *
 * TODO:
 * autocorrect=off
 * autocapitalize=words
 */
require_once("logging.php");
use DateTime;


/// Common attributes for a field: required, autofocus, readonly, disabled, also taking care of JS
class FieldAttributes {
	public $bRequired = false;
	public $bAutofocus = false;
	public $bReadonly = false;
	public $bDisabled = false;

	public $bWithJs = true;
	public $bJsChanged = true;

	public $size = NULL;

	public $min = NULL;
	public $max = NULL;
	public $step = NULL;

	/**
	 * Constructor with required and autofocus args.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function __construct($bRequired=false, $bAutofocus=false) {
		$this->bRequired = $bRequired;
		$this->bAutofocus = $bAutofocus;
	}

	private function getUintAttr($name, $value) {
		if($value === NULL) {
			return "";
		}

		if($value <= 0) {
			return "";
		}

		return " $name=\"$value\"";
	}

	private function getValAttr($name, $value) {
		if($value === NULL) {
			return "";
		}

		return " $name=\"$value\"";
	}

	private function getBoolAttr($name, $value) {
		if($value === NULL || !$value) {
			return "";
		}

		return " $name=\"$name\"";
	}

	private function getFlagAttr($name, $value) {
		if($value === NULL || !$value) {
			return "";
		}

		return " $name";
	}

	public function get() {
		$back = "";

		$back .= $this->getUintAttr("size", $this->size);

		$back .= $this->getValAttr("min", $this->min);
		$back .= $this->getValAttr("max", $this->max);
		$back .= $this->getValAttr("step", $this->step);

		$back .= $this->getFlagAttr("autofocus", $this->bAutofocus);
		$back .= $this->getFlagAttr("required", $this->bRequired);

		$back .= $this->getBoolAttr("readonly", $this->bReadonly);
		$back .= $this->getBoolAttr("disabled", $this->bDisabled);

		if(!$this->bWithJs) {
			// Nothing more to do
			return $back;
		}

		$jsfunc = "FieldAction()";

		$back .= " oninput=\"$jsfunc\"";
		$back .= " onpaste=\"$jsfunc\"";
		$back .= " oncut=\"$jsfunc\"";
		$back .= " onblur=\"$jsfunc\"";
		$back .= " onkeyup=\"$jsfunc\"";

		if($this->bJsChanged) {
			$jsfunc = "FieldChanged()";
		}

		$back .= " onchange=\"$jsfunc\"";

		return $back;
	}
}
use FieldAttributes;


/// Embedder for a field: div, paragraph, css, title/post-title
class FieldEmbedder {
	public $css = NULL;
	public $bDiv = true;
	public $bParagraph = false;

	public $title = "";
	public $posttitle = "";

	public function __construct($title="", $posttitle="") {
		$this->title = $title;
		$this->posttitle = $posttitle;
	}

	public function get($name, $string) {
		// CSS
		if($this->css === NULL) {
			$this->css = $name;
		}

		// title
		if($this->title === NULL) {
			$this->title = "";
		}
		if($this->title != "") {
			$this->title = "<label for=\"$name\">{$this->title}</label>&nbsp;: ";
		}

		// posttitle
		if($this->posttitle === NULL) {
			$this->posttitle = "";
		}
		if($this->posttitle != "") {
			$this->posttitle = "&nbsp;{$this->posttitle}";
		}

		$string = "{$this->title}{$string}{$this->posttitle}";

		// paragraph
		if($this->bParagraph) {
			$string = "<p class=\"{$this->css}\">\n$string\n</p>\n";
		}

		// div
		if($this->bDiv) {
			$string = "<div class=\"{$this->css}\">\n$string\n</div>\n";
		}

		return $string;
	}
}
use FieldEmbedder;



/**
 * Base input class defining backbone common to all fields
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class BaseInput {
	private $type = NULL;

	private $name;
	private $value;

	private $moreAttributes = "";
	private $attributes;
	private $embedder;

		private function setTitle($title) {
			if($title === NULL) {
				return;
			}

			if($title == "") {
				return;
			}

			$this->embedder->title = $title;
		}
	//
		private function attrValue($value) {
			if($value === NULL || $value == "") {
				return "";
			}

			return " value=\"" . stripslashes(trim($value)) . "\"";
		}
	//
		/**
		 * Get a tag attribute (if valid).
		 *
		 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
		 */
		private function tagAttribute($attr, $value) {
			if($value === NULL) {
				return "";
			}

			if($value == "") {
				return "";
			}

			return " $attr=\"$value\"";
		}
	//
		private function build() {
			$back = "<input id=\"{$this->name}\" type=\"{$this->type}\" name=\"{$this->name}\"";
			$back .= $this->attrValue($this->value);
			$back .= $this->moreAttributes;

			if($this->attributes !== NULL) {
				$back .= $this->attributes->get();
			}

			$back .= " />\n";

			return $back;
		}
	//
		private function string() {
			global $theLogger;
			$theLogger->trace("input(type={$this->type}, name={$this->name})");

			return $this->embedder->get($this->name, $this->build());
		}
	//
		private function setup($name, $value, $attributes, $embedder) {
			$this->name = $name;

			$this->value = $value;
			if($value === NULL) {
				$this->value = "";
			} elseif(is_object($value) && get_class($value) == "DbDataArray") {
				$this->value = $value->get($this->name);
			}

			$this->embedder = $embedder;

			if($attributes === NULL) {
				$attributes = new FieldAttributes();
			}
			$this->attributes = $attributes;
		}
	//
		// Example of get implementation:
		// public function get($name, $value, $attributes, $embedder) {
		//     $this->setup($name, $value, $attributes, $embedder);
		//     return $this->string();
		// }
}


/**
 * Hidden
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class HiddenInput extends BaseInput {
	private $type = "hidden";

	public function get($name, $value=NULL, $embedder=NULL) {
		// No title arg
		$this->setup($name, $value, NULL, $embedder);
		return $this->string();
	}
}
$theHiddenInput = new HiddenInput();


/**
 * File
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class FileInput extends BaseInput {
	private $type = "file";
	private $maxFileSize;

	private $kMaxFileSize = 5242880;

		private function setMaxFileSize($maxFileSize=NULL) {
			if($maxFileSize === NULL) {
				return "";
			}

			if($maxFileSize <= 0) {
				return "";
			}

			if($maxFileSize == 1) {
				$maxFileSize = $this->kMaxFileSize;
			}

			return $this->hidden("MAX_FILE_SIZE", $maxFileSize);
		}
	//
		/**
		 * Build
		 *
		 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
		 */
		private function build() {
			return $this->setMaxFileSize($this->maxFileSize) . parent::build();
		}
	//
		public function get($name, $maxFileSize=NULL, $title="", $attributes=NULL, $embedder=NULL) {
			$this->setup($name, NULL, $attributes, $embedder);

			$this->maxFileSize = $maxFileSize;

			$this->setTitle($title);

			return $this->string();
		}
}
$theFileInput = new FileInput();


/**
 * Textarea
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class Textarea extends BaseInput {
	private $type = "textarea";

	/**
	 * Build
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function build() {
		$back = "<textarea id=\"{$this->name}\" name=\"{$this->name}\"{$this->attributes->get()}{$this->moreAttributes}>";

		if($this->value !== NULL) {
			$back .= "\n{$this->value}\n";
		}

		$back .= "</textarea>\n";

		return $back;
	}

	public function get($name, $value=NULL, $rows=NULL, $cols=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		$this->setup($name, $value, $attributes, $embedder);

		$this->moreAttributes = "";
		$this->moreAttributes .= $this->tagAttribute("rows", $rows);
		$this->moreAttributes .= $this->tagAttribute("cols", $cols);

		$this->setTitle($title);
		if($this->embedder->title != "") {
			$this->embedder->title .= "<br />\n";
		}

		return $this->string();
	}
}
$theTextarea = new Textarea();


/**
 * Datalist (not really a field but used for fields).
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class Datalist extends BaseInput {
	private $type = "datalist";

	private $listId;

		private function setId() {
			if($this->value === NULL) {
				$this->listId = "";
				return;
			}

			if(gettype($this->value) == "string") {
				// Reuse an existing datalist
				$this->listId = $this->value;
				return;
			}

			$this->listId = "{$this->name}_datalist";
		}
	//
		public function getId($name, $values) {
			if($name != $this->name || $values != $this->value) {
				return NULL;
			}

			return $this->listId;
		}
	//
		/**
		 * Build
		 *
		 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
		 */
		private function build() {
			if($this->listId == "" || $this->value === NULL || $this->value === array()) {
				return "";
			}

			if($this->listId == $this->value) {
				// This means that we provided only the ID of an existing list
				return "";
			}

			$options = "";
			foreach($this->value as $value) {
				$options .= "<option value=\"$value\" />\n";
			}

			return "<datalist id=\"{$this->listId}\">$options</datalist>\n";
		}
	//
		public function get($name, $values, $embedder=NULL) {
			if($embedder === NULL) {
				// Empty embedder
				$embedder = new FieldEmbedder();
				$embedder->bDiv = false;
			}
			$this->setup($name, $values, NULL, $embedder);

			$this->setId();

			return $this->string();
		}
}
$theDatalist = new Datalist();


/**
 * Generic input, inherited for generic input box and generic number (also for date/time)
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class GenericInput extends BaseInput {
	private function genericGet($name, $value=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		$this->setup($name, $value, $attributes, $embedder);

		$this->setTitle($title);

		return $this->string();
	}
}


class GenericInputBox extends GenericInput {
	private $datalist;

	public function get($name, $value=NULL, $title="", $datalist=NULL, $attributes=NULL, $embedder=NULL) {
		global $theDatalist;
		$this->datalist = $theDatalist->get($name, $datalist);

		$this->moreAttributes = $this->tagAttribute("list", $theDatalist->getId($name, $datalist));

		return $this->genericGet($name, $value, $title, $attributes, $embedder);
	}

	/**
	 * Build the input tag.
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function build() {
		return $this->datalist . parent::build();
	}
}


/**
 * Text
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class TextInput extends GenericInputBox {
	private $type = "text";
}
$theTextInput = new TextInput();


/**
 * Search
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class SearchInput extends GenericInputBox {
	private $type = "search";
}
$theSearchInput = new SearchInput();


/**
 * Email (validated when pressing submit)
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class EmailInput extends GenericInputBox {
	private $type = "email";
}
$theEmailInput = new EmailInput();


/**
 * Url (validated when pressing submit)
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class UrlInput extends GenericInputBox {
	private $type = "url";
}
$theUrlInput = new UrlInput();


/**
 * Password
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class PasswordInput extends GenericInput {
	private $type = "password";

	public function get($name, $title="", $attributes=NULL, $embedder=NULL) {
		// Do not allow to provide value
		return $this->genericGet($name, NULL, $title, $attributes, $embedder);
	}
}
$thePasswordInput = new PasswordInput();


class GenericInputList extends BaseInput {
	private $list;

	private function getListFromDatabase($query, $key, $value) {
		global $theDbHelper;
		$items = $theDbHelper->queryManage($query);

		if($items->num_rows <= 0) {
			$items->close();
			return array();
		}

		$list = array();

		while($item = $items->fetch_object()) {
			$list[$item->$key] = $item->$value;
		}

		$items->close();

		return $list;
	}

	private function setList($list) {
		if(array_key_exists("query", $list)) {
			$this->list = $this->getListFromDatabase($list["query"], $list["key"], $list["value"]);
			return;
		}

		$this->list = $list;
	}

	/**
	 * Generic get for list input
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function genericGet($name, $list, $value=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		$this->setup($name, $value, $attributes, $embedder);

		$this->setTitle($title);
		$this->setList($list);

		return $this->string();
	}
}


class GenericInputChoice extends GenericInputList {
	private $kChecked = " checked=\"checked\"";

	private $separator;
	private $inputName;

	private function getValueArray() {
		if(is_array($this->value)) {
			return $this->value;
		}

		if($this->value === NULL) {
			return array();
		}

		return array($this->value);
	}

	/**
	 * Build input tag.
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function build() {
		$value = $this->getValueArray();

		$attributes = $this->moreAttributes . $this->attributes->get();

		$inputTypeName = "<input type=\"{$this->type}\" name=\"{$this->inputName}\"";

		$back = "";

		foreach($this->list as $key => $val) {
			$back .= "{$this->separator}{$inputTypeName} id=\"{$this->name}_{$key}\" value=\"{$key}\"";

			if(in_array($key, $value)) {
				$back .= $this->kChecked;
			}

			$back .= "$attributes />";

			$back .= "<label for=\"{$this->name}_{$key}\">&nbsp;{$val}</label>\n";
		}

		return $back;
	}

	/**
	 * Get
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function get($name, $list, $value=NULL, $title="", $bVerticalList=false, $bGetArray=true, $attributes=NULL, $embedder=NULL) {
		$this->inputName = $this->name;
		if($bGetArray && $this->type != "radio") {
			$this->inputName .= "[]";
		}

		$this->separator = "&nbsp;\n";
		if($bVerticalList) {
			$this->separator = "<br />\n";
		}

		return $this->genericGet($name, $list, $value, $title, $attributes, $embedder);
	}
}


/**
 * Radio
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class RadioInput extends GenericInputChoice {
	private $type = "radio";
}
$theRadioInput = new RadioInput();


/**
 * Checkbox
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class CheckboxInput extends GenericInputChoice {
	private $type = "checkbox";
}
$theCheckboxInput = new CheckboxInput();


class SelectInput extends GenericInputList {
	private $type = "select";

	private $kSelected = " selected=\"selected\"";

	/**
	 * Build input tag.
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function build() {
		$back = "<{$this->type} id=\"{$this->name}\" name=\"{$this->name}\" {$this->attributes->get()}{$this->moreAttributes}>\n";

		foreach($this->list as $key => $val) {
			$back .= "<option value=\"$key\" ";

			if($key == $this->value) {
				$back .= $this->kSelected;
			}

			$back .= ">$val</option>\n";
		}

		$back .= "</select>\n";

		return $back;
	}

	public function get($name, $list, $value=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		return $this->genericGet($name, $list, $value, $title, $attributes, $embedder);
	}
}
$theSelectInput = new SelectInput();


class GenericNumber extends GenericInput {
	public function get($name, $value=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		return $this->genericGet($name, $value, $title, $attributes, $embedder);
	}
}


/**
 * Number
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class NumberInput extends GenericNumber {
	private $type = "number";
}
$theNumberInput = new NumberInput();


/**
 * Range
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class RangeInput extends GenericNumber {
	private $type = "range";
}
$theRangeInput = new RangeInput();


class GenericInputDateTime extends GenericInput {
	private $datetimeFormat;

	private function validateValue($value) {
		if($value == "now") {
			$now = new DateTime();
			return $now->format($this->datetimeFormat);
		}

		return $value;
	}

	/**
	 * Get
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function get($name, $value=NULL, $title="", $attributes=NULL, $embedder=NULL) {
		if($value === NULL || $value == "") {
			$value = "now";
		}

		$this->setup($name, $value, $attributes, $embedder);

		$this->setTitle($title);

		$this->value = $this->validateValue($this->value);

		$this->attributes->min = $this->validateValue($this->attributes->min);
		$this->attributes->max = $this->validateValue($this->attributes->max);

		return $this->string();
	}
}


/**
 * Time
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class TimeInput extends GenericInputDateTime {
	private $type = "time";
	private $datetimeFormat = "H:i:s";
}
$theTimeInput = new TimeInput();


class GenericInputDate extends GenericInputDateTime {
	/**
	 * Validate min/max with special keywords.
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function validateValue($value) {
		if($value == "tomorrow" || $value == "yesterday") {
			$datetime = new DateTime($value);
			return $datetime->format($this->datetimeFormat);
		}

		return parent::validateValue($value);
	}
}


/**
 * Date
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class DateInput extends GenericInputDate {
	private $type = "date";
	private $datetimeFormat = "Y-m-d";
}
$theDateInput = new DateInput();


/**
 * Datetime
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class DatetimeInput extends GenericInputDate {
	private $type = "datetime-local";
	private $datetimeFormat = "Y-m-d H:i:s";
}
$theDatetimeInput = new DatetimeInput();
?>
