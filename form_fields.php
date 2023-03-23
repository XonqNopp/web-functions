<?php
/**
 * All the input for forms are defined here in a common way.
 *
 * Note:
 * To make it the most simple to use, we make singletons.
 * As we cannot overload a method with different args, the method get shall only be defined in classes which are not
 * inherited from (or little), so generic classes cannot implement a get methd.
 */
require_once("logging.php");


/// Common attributes for a field: required, autofocus, readonly, disabled, also taking care of JS
class FieldAttributes {
    public $isRequired = false;
    public $hasAutofocus = false;
    public $isReadonly = false;
    public $isDisabled = false;

    public $withJs = true;
    public $hasJsChanged = true;

    public $autocorrect = true;
    public $autocapitalize = NULL;

    public $size = NULL;

    public $min = NULL;
    public $max = NULL;
    public $step = NULL;

    /**
     * Constructor with required and autofocus args.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($isRequired=false, $hasAutofocus=false) {
        $this->isRequired = $isRequired;
        $this->hasAutofocus = $hasAutofocus;
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

        $back .= $this->getFlagAttr("autofocus", $this->hasAutofocus);
        $back .= $this->getFlagAttr("required", $this->isRequired);

        $back .= $this->getBoolAttr("readonly", $this->isReadonly);
        $back .= $this->getBoolAttr("disabled", $this->isDisabled);

        if(!$this->autocorrect) {
            $back .= $this->getValAttr("autocorrect", "off");
        }
        if($this->autocapitalize !== NULL) {
            $back .= $this->getValAttr("autocapitalize", $this->autocapitalize);
        }

        if(!$this->withJs) {
            // Nothing more to do
            return $back;
        }

        $jsfunc = "FieldAction()";

        $back .= " oninput=\"$jsfunc\"";
        $back .= " onpaste=\"$jsfunc\"";
        $back .= " oncut=\"$jsfunc\"";
        $back .= " onblur=\"$jsfunc\"";
        $back .= " onkeyup=\"$jsfunc\"";

        if($this->hasJsChanged) {
            $jsfunc = "FieldChanged()";
        }

        $back .= " onchange=\"$jsfunc\"";

        return $back;
    }
}


/// Embedder for a field: div, paragraph, css, title/post-title
class FieldEmbedder {
    public $css = NULL;
    public $hasDiv = true;
    public $hasParagraph = false;
    public $hasBrAfterTitle = false;

    public $title = "";
    public $posttitle = "";
    private $myTitle = "";
    private $myPosttitle = "";

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
        $this->myTitle = "";
        if($this->title !== NULL && $this->title != "") {
            $this->myTitle = "<label for=\"$name\">{$this->title}</label>&nbsp;:";
            $afterTitle = " ";
            if($this->hasBrAfterTitle) {
                $afterTitle .= "<br />\n";
            }
            $this->myTitle .= $afterTitle;
        }

        // posttitle
        if($this->posttitle === NULL) {
            $this->myPosttitle = "";
        } elseif($this->posttitle != "") {
            $this->myPosttitle = "&nbsp;{$this->posttitle}";
        }

        $string = "{$this->myTitle}{$string}{$this->myPosttitle}";

        // paragraph
        if($this->hasParagraph) {
            $string = "<p class=\"{$this->css}\">\n$string\n</p>\n";
        }

        // div
        if($this->hasDiv) {
            $string = "<div class=\"{$this->css}\">\n$string\n</div>\n";
        }

        return $string;
    }
}



/**
 * Base input class defining backbone common to all fields
 */
class BaseInput {
    protected $type = NULL;

    protected $name;
    protected $value;

    protected $moreAttributes = "";
    protected $attributes;
    protected $embedder;

        protected function setTitle($title) {
            if($title === NULL) {
                return;
            }

            $this->embedder->title = $title;
        }
    //
        protected function attrValue($value) {
            if($value === NULL || $value == "") {
                return "";
            }

            return " value=\"" . stripslashes(trim($value)) . "\"";
        }
    //
        // Get a tag attribute (if valid).
        protected function tagAttribute($attr, $value) {
            if($value === NULL) {
                return "";
            }

            if($value == "") {
                return "";
            }

            return " $attr=\"$value\"";
        }
    //
        protected function build() {
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
        protected function string() {
            global $theLogger;
            $theLogger->trace("input(type={$this->type}, name={$this->name})");

            return $this->embedder->get($this->name, $this->build());
        }
    //
        /**
         * Setup.
         *
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        protected function setup($name, $value, $attributes, $embedder) {
            $this->name = $name;

            $this->value = $value === NULL ? "" : $value;
            if(is_object($value) && get_class($value) == "DbDataArray") {
                $this->value = $value->get($this->name);
            }

            $this->attributes = $attributes === NULL ? new FieldAttributes() : $attributes;
            $this->embedder = $embedder === NULL ? new FieldEmbedder() : $embedder;
        }
    //
        // Example of get implementation:
        // public function get($name, $value, $attributes, $embedder) {
        //     $this->setup($name, $value, $attributes, $embedder);
        //     return $this->string();
        // }
}


class HiddenInput extends BaseInput {
    protected $type = "hidden";

    public function get($name, $value=NULL, $embedder=NULL) {
        // No title arg
        $this->setup($name, $value, NULL, $embedder);
        return $this->string();
    }
}
$theHiddenInput = new HiddenInput();


class FileInput extends BaseInput {
    protected $type = "file";
    protected $maxFileSize;

    protected $kMaxFileSize = 5242880;

        protected function setMaxFileSize($maxFileSize=NULL) {
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
        protected function build() {
            return $this->setMaxFileSize($this->maxFileSize) . parent::build();
        }
    //
        public function get($name, $maxFileSize=NULL, $title=NULL, $attributes=NULL, $embedder=NULL) {
            $this->setup($name, NULL, $attributes, $embedder);

            $this->maxFileSize = $maxFileSize;

            $this->setTitle($title);

            return $this->string();
        }
}
$theFileInput = new FileInput();


class Textarea extends BaseInput {
    protected $type = "textarea";

    protected function build() {
        $back = "<textarea id=\"{$this->name}\" name=\"{$this->name}\"{$this->attributes->get()}{$this->moreAttributes}>";

        if($this->value !== NULL) {
            $back .= "\n{$this->value}";
            // no trailing newline as it will be accounted
        }

        $back .= "</textarea>\n";

        return $back;
    }

    public function get($name, $value=NULL, $rows=NULL, $cols=NULL, $title=NULL, $attributes=NULL, $embedder=NULL) {
        $this->setup($name, $value, $attributes, $embedder);

        $this->moreAttributes = "";
        $this->moreAttributes .= $this->tagAttribute("rows", $rows);
        $this->moreAttributes .= $this->tagAttribute("cols", $cols);

        $this->setTitle($title);
        $this->embedder->hasBrAfterTitle = true;

        return $this->string();
    }
}
$theTextarea = new Textarea();


/**
 * Datalist (not really a field but used for fields).
 */
class Datalist extends BaseInput {
    protected $type = "datalist";

    protected $listId;

        protected function setId() {
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
        protected function build() {
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
        /**
         * Getter.
         *
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function get($name, $values, $embedder=NULL) {
            if($embedder === NULL) {
                // Empty embedder
                $embedder = new FieldEmbedder();
                $embedder->hasDiv = false;
            }
            $this->setup($name, $values, NULL, $embedder);

            $this->setId();

            return $this->string();
        }
}
$theDatalist = new Datalist();


/**
 * Generic input, inherited for generic input box and generic number (also for date/time)
 */
class GenericInput extends BaseInput {
    protected function genericGet($name, $value=NULL, $title=NULL, $attributes=NULL, $embedder=NULL) {
        $this->setup($name, $value, $attributes, $embedder);

        $this->setTitle($title);

        return $this->string();
    }
}


class GenericInputBox extends GenericInput {
    protected $datalist;

    public function get($name, $value=NULL, $title="", $datalist=NULL, $attributes=NULL, $embedder=NULL) {
        global $theDatalist;
        $this->datalist = $theDatalist->get($name, $datalist);

        $this->moreAttributes = $this->tagAttribute("list", $theDatalist->getId($name, $datalist));

        return $this->genericGet($name, $value, $title, $attributes, $embedder);
    }

    protected function build() {
        return $this->datalist . parent::build();
    }
}


class TextInput extends GenericInputBox {
    protected $type = "text";
}
$theTextInput = new TextInput();


class SearchInput extends GenericInputBox {
    protected $type = "search";
}
$theSearchInput = new SearchInput();


class EmailInput extends GenericInputBox {
    protected $type = "email";
}
$theEmailInput = new EmailInput();


class UrlInput extends GenericInputBox {
    protected $type = "url";
}
$theUrlInput = new UrlInput();


class PasswordInput extends GenericInput {
    protected $type = "password";

    public function get($name, $title="", $attributes=NULL, $embedder=NULL) {
        // Do not allow to provide value
        return $this->genericGet($name, NULL, $title, $attributes, $embedder);
    }
}
$thePasswordInput = new PasswordInput();


class GenericInputList extends BaseInput {
    protected $list;

        /**
         * not used for lists
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        protected function attrValue($value) {
            return "";
        }
    //
        protected function getListFromDatabase($query, $key, $value) {
            global $theBobbyTable;
            $items = $theBobbyTable->queryManage($query);

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
    //
        protected function setList($list) {
            if(array_key_exists("query", $list)) {
                $this->list = $this->getListFromDatabase($list["query"], $list["key"], $list["value"]);
                return;
            }

            $this->list = $list;
        }
    //
        /**
         * Generic get for list input
         */
        protected function genericGet($name, $list, $value=NULL, $title=NULL, $attributes=NULL, $embedder=NULL) {
            $this->setup($name, $value, $attributes, $embedder);

            $this->setTitle($title);
            $this->setList($list);

            return $this->string();
        }
}


class GenericInputChoice extends GenericInputList {
    protected $kChecked = " checked=\"checked\"";

    protected $separator;
    protected $inputName;

    protected function getValueArray() {
        if(is_array($this->value)) {
            return $this->value;
        }

        if($this->value === NULL) {
            return array();
        }

        return array($this->value);
    }

    protected function build() {
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
    public function get($name, $list, $value=NULL, $title="", $isVerticalList=false, $returnsArray=true, $attributes=NULL, $embedder=NULL) {
        $this->inputName = $name;
        if($returnsArray && $this->type != "radio") {
            $this->inputName .= "[]";
        }

        $this->separator = "&nbsp;\n";
        if($isVerticalList) {
            $this->separator = "<br />\n";
        }

        return $this->genericGet($name, $list, $value, $title, $attributes, $embedder);
    }
}


class RadioInput extends GenericInputChoice {
    protected $type = "radio";
}
$theRadioInput = new RadioInput();


class CheckboxInput extends GenericInputChoice {
    protected $type = "checkbox";
}
$theCheckboxInput = new CheckboxInput();


class SelectInput extends GenericInputList {
    protected $type = "select";

    protected $kSelected = " selected=\"selected\"";

    protected function build() {
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


class NumberInput extends GenericNumber {
    protected $type = "number";
}
$theNumberInput = new NumberInput();


class RangeInput extends GenericNumber {
    protected $type = "range";
}
$theRangeInput = new RangeInput();


class GenericInputDateTime extends GenericInput {
    protected $datetimeFormat;

    /**
     * Validate the value.
     *
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    protected function validateValue($value) {
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
    public function get($name, $value=NULL, $title=NULL, $attributes=NULL, $embedder=NULL) {
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


class TimeInput extends GenericInputDateTime {
    protected $type = "time";
    protected $datetimeFormat = "H:i";  // no seconds :s

    public function validateValue($value) {
        if($value !== NULL && strlen($value) == 8) {
            // Remove seconds
            $value = substr($value, 0, 5);
        }
        return parent::validateValue($value);
    }
}
$theTimeInput = new TimeInput();


class GenericInputDate extends GenericInputDateTime {
    /**
     * Validate min/max with special keywords.
     *
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    protected function validateValue($value) {
        if($value == "tomorrow" || $value == "yesterday") {
            $datetime = new DateTime($value);
            return $datetime->format($this->datetimeFormat);
        }

        return parent::validateValue($value);
    }
}


class DateInput extends GenericInputDate {
    protected $type = "date";
    protected $datetimeFormat = "Y-m-d";
}
$theDateInput = new DateInput();


class DatetimeInput extends GenericInputDate {
    protected $type = "datetime-local";
    protected $datetimeFormat = "Y-m-d H:i";  // no seconds :s

    public function validateValue($value) {
        if($value !== NULL && strlen($value) == 19) {
            // Remove seconds
            $value = substr($value, 0, 16);
        }
        return parent::validateValue($value);
    }
}
$theDatetimeInput = new DatetimeInput();
?>
