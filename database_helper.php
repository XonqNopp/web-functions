<?php
require_once("helper.php");

require_once("file_helper.php");
require_once("language_helper.php");
require_once("login_helper.php");
require_once("server_helper.php");


// To handle DB fields from user input, we have to take care of 3 different types:
// * user input
// * DB storage
// * HTML display
// So we need methods to convert the following:
// * submit: user input to DB storage    -> input2sql
// * edit: DB storage to user input      -> sql2input
// * display: DB storage to HTML display -> sql2html
// BUT when we want to set a value to a user input field, we can keep it HTML renderable
// so actually we need only 2 methods:
// input2sql: user input submitted to DB storage
// sql2html: DB storage rendered in HTML


/**
 * DatabaseText
 *
 * Provides some functionalities to handle text to/from the DB.
 */
class DatabaseText {
          private function nullEmpty($text) {
              if($text === NULL) {
                  return "";
              }

              return $text;
          }
    //
        /**
         * input2sql: from user input to SQL
         *
         * Convert user input to SQL-safe string.
         * We replace any special character by its HTML encoding &xyz;.
         * This includes any accented characters, any special character as well as single and double quotes.
         * We also remove backslashes to prevent quote escaping.
         */
        public function input2sql($text) {
            return stripslashes(
                htmlentities(
                    trim(
                        $this->nullEmpty($text)
                    ),
                    ENT_QUOTES,
                    "UTF-8"
                )
            );
        }
    //
        /**
         * sql2html: from SQL to HTML
         *
         * Convert SQL-safe string back to HTML readable string.
         * It does not totally invert input2sql output but only what we need to render HTML correctly.
         * It only converts back the strings: &amp; &lt; &gt;
         */
        public function sql2html($text) {
            return htmlspecialchars_decode(
                trim(
                    $this->nullEmpty($text)
                ),
                ENT_NOQUOTES
            );
        }
    //
        // sql2htmlUrl: from SQL to HTML readonly with conversion of links
        public function sql2htmlUrl($text) {
            return preg_replace(
                "/(https?:\/\/[^ \n<>]+)/", '<a href="\1" title="\1">\1</a>',
                $this->nullEmpty($text)
            );
        }
    //
        // filename2sql: from filename to SQL
        public function filename2sql($text) {
            $text = $this->nullEmpty($text);

            global $theFileHelper;
            $ext     = $theFileHelper->getExt($text);
            $without = $theFileHelper->woExt($text);

            $back = $this->sql2html(  // 4. convert back to regular string
                preg_replace(  // 3. only keep allowed set of characters
                    "/&([a-zA-Z])[a-z]+;/",
                    '\1',
                    $this->input2sql(  // 2. convert to safe string
                        preg_replace("/\r?\n/", "", $without)  // 1. remove line breaks
                    )
                )
            );

            return "$back$ext";
        }
    //
        // inputTextarea2sql: from textarea to SQL
        public function inputTextarea2sql($text) {
            return trim(nl2br(preg_replace("/(\r?\n)*$/", "", $this->input2sql($text))));
        }
    //
        // sql2htmlTextarea: from SQL to textarea
        public function sql2htmlTextarea($text) {
            return $this->sql2html(preg_replace("/<br\s*\/?>/i", "", $this->nullEmpty($text)));
        }
    //
        // inputTextareaParagraph2sql: from textarea to SQL
        public function inputTextareaParagraph2sql($text, $class="") {
            $back = $this->input2sql($text);

            $tag = "";
            if($class != "") {
                $tag = " class=\"$class\"";
            }

            $back = preg_replace("/\r/", "", $back);  // no CR

            $back = preg_replace("/\n\n+/", "</p><p$tag>", $back);
            $back = nl2br($back);
            $back = preg_replace("/<\/p><p/", "</p>\n<p", $back);
            $back = "<p$tag>$back</p>";

            if($back == "<p$tag></p>") {
                return "";
            }

            return $back;
        }
    //
        // sql2htmlTextareaParagraph: from SQL to textarea
        public function sql2htmlTextareaParagraph($text) {
            $back = trim($this->nullEmpty($text));
            $back = preg_replace("/<\/p>\n*/", "\n\n", $back);
            $back = preg_replace("/<p( [^>]*)?>\n*/", "", $back);
            $back = preg_replace("/<br *\/?>\n*/", "\n", $back);
            return $this->sql2html($back);
        }
    //
        /**
         * inputTextareaItemize2sql: from textarea to SQL
         *
         * WARNING not tested
         */
        public function inputTextareaItemize2sql($text) {
            $back = str_replace(array("\r\n", "\n", "\r"), "</li>\n<li>", $this->input2sql($text));
            $back = preg_replace("/^/", "<li>", $back);
            $back = preg_replace("/$/", "</li>", $back);
            return $back;
        }
    //
        /**
         * sql2htmlTextareaItemize: from SQL to textarea
         *
         * WARNING not tested
         */
        public function sql2htmlTextareaItemize($text) {
            $back = str_replace("</li>\n<li>", "\r\n", $this->nullEmpty($text));
            $back = preg_replace("/<\/li>$/", "", $back);
            $back = preg_replace("/^<li>/", "", $back);
            return $this->sql2html($back);
        }
}


$theDatabaseText = new DatabaseText();


    // DB data
        /**
         * Database data field
         *
         * Contains information about a DB field as well as some methods to process the data.
         */
        class DbDataField {
            public $type;
            public $value;
            public $isSqlSafe;
            public $precision;

                /**
                 * Constructor
                 *
                 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
                 */
                public function __construct($type, $value, $isSqlSafe=true) {
                    $this->type = $type;
                    $this->value = $value;
                    $this->isSqlSafe = $isSqlSafe;

                    $this->precision = 3;  // default
                }
            //
                // sql2html: from SQL to HTML
                public function sql2html($text) {
                    global $theDatabaseText;
                    return $theDatabaseText->sql2html($text);
                }
            //
                // applySql2html
                public function applySql2html() {
                    if($this->type != "s") {
                        return;
                    }

                    if(!$this->isSqlSafe) {
                        return;
                    }

                    $this->value = $this->sql2html($this->value);
                    $this->isSqlSafe = false;
                }
            //
                // input2sql: from field to SQL
                public function input2sql($text) {
                    global $theDatabaseText;
                    return $theDatabaseText->input2sql($text);
                }
            //
                // applyInput2sql
                public function applyInput2sql() {
                    if($this->type != "s") {
                        return;
                    }

                    if($this->isSqlSafe) {
                        return;
                    }

                    $this->value = $this->input2sql($this->value);
                    $this->isSqlSafe = true;
                }
            //
                // Value getter
                public function get() {
                    return $this->value;
                }
            //
                // Value setter
                public function set($newValue) {
                    if($this->isSqlSafe) {
                        $newValue = $this->input2sql($newValue);
                    }

                    $this->value = $newValue;
                }
            //
                /**
                 * Round a value
                 *
                 * Args:
                 *     rounding (int): if NULL, use $this->precision
                 */
                public function applyRound($rounding=NULL) {
                    if($this->type == "s") {
                        // Cannot round strings
                        return;
                    }

                    if($rounding === NULL) {
                        $rounding = $this->precision;
                    }

                    $value = $this->get();

                    if($value == "") {
                        $value = 0;
                    }

                    $this->set(round($value, $rounding));
                }
        }
    //
        /*
         * Database data array
         *
         * Contains the DB data fields and some methods to process them.
         */
        class DbDataArray {
            public $fields = array();

                /**
                 * Add a field to the array.
                 *
                 * Args:
                 *     field (string)
                 *     type (string)
                 *     value
                 *
                 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
                 * @SuppressWarnings(PHPMD.MissingImport)
                 */
                public function addField($field, $type, $value, $isSqlSafe=true) {
                    $this->fields[$field] = new DbDataField($type, $value, $isSqlSafe);
                }
            //
                public function get($field) {
                    return $this->fields[$field]->get();
                }
            //
                /**
                 * Setter for field
                 *
                 * Args:
                 *    field (string)
                 *    value: if NULL, taking $_POST[$field]
                 *
                 * @SuppressWarnings(PHPMD.Superglobals)
                 */
                public function set($field, $value=NULL) {
                    if($value === NULL) {
                        $value = $_POST[$field];
                    }

                    $this->fields[$field]->set($value);
                }
            //
                /**
                 * Get the data we need to fill our SQL database from the POST data
                 *
                 * Args:
                 *     page (PhPage)
                 *     escapeStrings (bool): True to escape strings for DB. Default is isset($_POST["submit"])
                 *
                 * @SuppressWarnings(PHPMD.Superglobals)
                 */
                public function setDataValuesFromPost($escapeStrings=NULL) {
                    if($escapeStrings === NULL) {
                        $escapeStrings = isset($_POST["submit"]);
                    }

                    foreach(array_keys($this->fields) as $field) {
                        if($field == "id" && !isset($_POST[$field])) {
                            // When insert new entry, no id available
                            continue;
                        }

                        if(!isset($_POST[$field])) {
                            $this->logger->fatal("Missing input field: $field");
                        }

                        $this->set($field);  // handles input2sql
                    }
                }
            //
                // Adding char escaping to DB data from fields
                public function applyInput2sql() {
                    foreach(array_keys($this->fields) as $field) {
                        $this->fields[$field]->applyInput2sql();
                    }
                }
            //
                // Removing char escaping from DB data to provide into fields
                public function applySql2html() {
                    foreach(array_keys($this->fields) as $field) {
                        $this->fields[$field]->applySql2html();
                    }
                }
            //
                /**
                 * Round a value
                 *
                 * Args:
                 *     field (string)
                 *     rounding (int)
                 */
                public function applyRound($field, $rounding=NULL) {
                    $this->fields[$field]->applyRound($rounding);
                }
        }


/**
 * Database helper
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DatabaseHelper extends MyHelper {
    private $mysqli = NULL;

    private $server;
    private $username;
    private $password;
    private $filename;
    private $filePath;

    public $dbName;

    public function setup($server, $username, $password, $dbName, $filename, $filePath) {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->filename = $filename;
        $this->filePath = $filePath;
    }

        /**
         * Connection
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        private function connection() {
            $this->logger->trace("connection()");
            $mysqli = false;

            $server = $this->server;
            $username = $this->username;
            $password = $this->password;

            global $theServerHelper;
            if($theServerHelper->isLocalhost()) {
                $server = "127.0.0.1";
                $username = "localadmin";
                $password = "localpassword";
            }

            $mysqli = new mysqli($server, $username, $password, $this->dbName);

            $errorNum = mysqli_connect_errno();
            if($errorNum) {
                $this->logger->fatal("Connection error: " . mysqli_connect_error() . " ($errorNum)");
                exit();  // fatal dies, but just to be sure
            }

            return $mysqli;
        }
    //
        /**
         * Init DB
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function init($database="") {
            $this->logger->trace("init($database)");

            if($this->mysqli !== NULL) {
                $this->logger->debug("init: DB already defined");
                return;
            }

            $mysqli = $this->connection();
            if(!$mysqli) {
                $this->logger->fatal("init: Problem with database");
                exit();  // fatal dies, but just to be sure
            }

            $this->mysqli = $mysqli;
        }
    //
        // close
        public function close() {
            if($this->mysqli === NULL) {
                return;
            }

            $this->mysqli->close();
            $this->mysqli = NULL;
        }
    //
        // display error
        private function displayError($query) {
            $this->logger->trace("displayError(...)");

            $errorprint = "Database error";

            $adminerror = "In file {$this->filename} ({$this->filePath}) ";
            $adminerror .= ":\n";

            $queryError = "";
            if($query) {
                if($query->errno != "") {
                    $queryError = "<br />[query] Error #{$query->errno}: {$query->error}.\n";
                }
            }

            $mysqliError = "";
            if($this->mysqli->errno != "") {
                $mysqliError = "<br />[MySqli] Error #{$this->mysqli->errno} : {$this->mysqli->error}.\n";
            }
            $adminerror .= "$queryError$mysqliError";

            global $theLoginHelper;
            if($theLoginHelper->userIsAdmin()) {
                $errorprint .= "<br />\n$adminerror";
            }

            $this->logger->fatal($errorprint);
        }
    //
        // query prepare
        public function queryPrepare($query) {
            $this->logger->trace("queryPrepare(...)");

            $back = $this->mysqli->prepare($query);

            if($back) {
                return $back;
            }

            $this->displayError($back);
            $this->logger->fatal("Could not prepare mysqli");
            return false;
        }
    //
        // query manage
        public function queryManage($query) {
            $this->logger->trace("queryManage(...)");
            $back = $this->mysqli->query($query);

            if(!$back) {
                $this->displayError($back);
            }

            return $back;
        }
    //
        // query manage with external id provided
        public function idManage($query, $dbId, $filename="", $lineno=0) {
            $this->logger->trace("idManage(..., $dbId)");
            $sql = $this->queryPrepare($query, $filename, $lineno);
            $sql->bind_param("i", $dbId);
            $this->executeManage($sql, $filename, $lineno);
            return $sql;
        }
    //
        /**
         * Query to insert a new entry in the database
         *
         * Args:
         *     table (string): name of the table where we want to update an entry
         *     dbData (DbDataArray)
         *
         * Returns:
         *     (int) the new entry's ID
         */
        public function queryInsert($table, $dbData) {
            $this->logger->trace("queryInsert($table, ...)");

            $query = "INSERT INTO `{$this->dbName}`.`$table` (";

            $endquery = "";
            $params = "";
            $values = array();

            foreach($dbData->fields as $field => $data) {
                if($field == "id") {
                    // do not set it manually
                    continue;
                }

                if($params != "") {
                    $query .= ", ";
                    $endquery .= ", ";
                }

                $query   .= "`$field`";
                $endquery .= "?";
                $params .= $data->type;
                $values[] = $data->value;
            }

            $query .= ") VALUES($endquery)";

            $sql = $this->queryPrepare($query);
            $sql->bind_param($params, ...$values);
            $this->executeManage($sql);
            return $sql->insert_id;
        }
    //
        /**
         * Query to update a new entry in the database
         *
         * Args:
         *     table (string): name of the table where we want to update an entry
         *     dbData (DbDataArray)
         */
        public function queryUpdate($table, $dbData) {
            $this->logger->trace("queryUpdate($table, ...)");

            $query = "UPDATE `{$this->dbName}`.`$table` SET ";

            $params = "";
            $values = array();

            foreach($dbData->fields as $field => $data) {
                if($field == "id") {
                    // handled at the end
                    continue;
                }

                if($params != "") {
                    $query .= ", ";
                }
                $query   .= "`$field` = ?";
                $params .= $data->type;
                $values[] = $data->value;
            }

            $query .= " WHERE `$table`.`id` = ? LIMIT 1";
            $params .= $dbData->fields["id"]->type;
            $values[] = $dbData->fields["id"]->value;

            $sql = $this->queryPrepare($query);
            $sql->bind_param($params, ...$values);
            $this->executeManage($sql);
        }
    //
        /**
         * Query to delete an entry from the database
         *
         * Args:
         *     table (string): name of the table where we want to delete the entry
         *     matchValue (int): ID of the entry to delete
         *     matchField (string): field to match the value
         *     limit (int): use this value if you want to delete more than one value
         */
        public function queryDelete($table, $matchValue, $matchField="id", $limit=1) {
            $this->logger->trace("queryDelete($table, $matchValue)");
            $this->idManage(
                "DELETE FROM `{$this->dbName}`.`$table` WHERE `$table`.`$matchField` = ? LIMIT $limit;",
                $matchValue
            );
        }
    //
        // execute manage
        public function executeManage($query) {
            $this->logger->trace("executeManage(...)");
            $back = $query->execute();

            if(!$back) {
                $this->displayError($query);
            }

            return $back;
        }
    //
        /**
         * select all
         *
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        public function selectAll($table, $orders=array(), $more="") {
            $query = "SELECT * FROM `$table`";

            if($more != "") {
                $more = " $more";
            }

            $numOrders = count($orders);

            if($numOrders <= 0) {
                return $this->queryManage($query . $more);
            }

            if($numOrders == 1 && $orders[0] == "rand") {
                $query .= " ORDER BY RAND()";
                return $this->queryManage($query . $more);
            }

            for($i = 0; $i < $numOrders; $i++) {
                if($i == 0) {
                    $query .= " ORDER BY";
                } else {
                    $query .= ", ";
                }

                $field = $orders[$i];
                $way = "ASC";

                if(substr($field, 0, 4) == "DESC") {
                    $way = "DESC";
                    $field = substr($field, 4);
                }

                $query .= "`$field` $way";
            }

            return $this->queryManage($query . $more);
        }
    //
        // select ID
        public function selectId($table, $matchValue, $matchField="id") {
            return $this->idManage("SELECT * FROM `$table` WHERE `$matchField` = ?", $matchValue);
        }
    //
        // get a random entry from a given DB table
        public function randomEntry($table) {
            $this->logger->trace("randomEntry($table)");
            return $this->queryManage("SELECT * from `$table` ORDER BY RAND() LIMIT 1");
        }
    //
        private function getDateEvent($query, $order="ASC") {
            $query = "$query ORDER BY `date` $order LIMIT 1;";
            $this->logger->trace("getDateEvent($query)");

            $sql = $this->queryManage($query);

            if($sql->num_rows <= 0) {
                $sql->close();
                return NULL;
            }

            $object = $sql->fetch_object();
            $sql->close();
            return $object->date;
        }
    //
        /**
         * Get next or last entries.
         *
         * Args:
         *     tables
         *     isNext (bool): if false, gets last
         *     isFemale (bool)
         *
         * Returns:
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.ElseExpression)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         * @SuppressWarnings(PHPMD.NPathComplexity)
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function nextLast($tables, $isNext=true, $isFemale=false) {
            global $theLogopedist;

            $this->logger->trace("nextLast(..., isNext=" . (int) $isNext . ", isFemale=" . (int) $isFemale . ")");

            $basequery = "SELECT `date`, `date` FROM `";

            $query = "";
            $prefix = "UNION ";
            if(!is_array($tables)) {
                $tables = array($tables);
            }

            foreach($tables as $table) {
                $query .= "$prefix$basequery$table` ";
            }

            // Remove initial prefix
            $query = substr($query, strlen($prefix));

            $query .= "HAVING DATEDIFF(`date`, CURDATE())";

            $theNext = NULL;
            $theTomorrow = NULL;
            $theLast = NULL;

            $theToday = $this->getDateEvent("$query = 0");

            $back = new stdClass();
            $back->special = "";// this is for 'today' or 'tomorrow'
            $back->when = NULL;
            $back->what = "";

            if($isNext) {
                $theNext = $this->getDateEvent("$query > 1");
                $theTomorrow = $this->getDateEvent("$query = 1");

                $back->what = $theLogopedist->translate("next", $isFemale);

            } else {
                $theLast = $this->getDateEvent("$query < 0", "DESC");

                $back->what = $theLogopedist->translate("last", $isFemale);
            }

            if($theNext === NULL && $theTomorrow === NULL && $theToday === NULL && $theLast === NULL) {
                $this->logger->trace("nextLast empty");
                $back->what = "";  // reset
                return $back;
            }

            if($theToday !== NULL) {
                $this->logger->trace("nextLast is today");
                $back->special = $theLogopedist->translate("today");
                $back->when = $theToday;

            } elseif($theTomorrow !== NULL) {
                $this->logger->trace("nextLast is tomorrow");
                $back->special = $theLogopedist->translate("tomorrow");
                $back->when = $theTomorrow;

            } elseif($theNext !== NULL) {
                $this->logger->trace("nextLast is next");
                $back->when = $theNext;

            } elseif($theLast !== NULL) {
                $this->logger->trace("nextLast is last");
                $back->when = $theLast;
            }

            $back->when = $this->timeHelper->str2date($back->when);
            $back->when->month = $this->timeHelper->months($back->when->month);

            $this->logger->debug("nextLast what={$back->what}");
            $this->logger->debug("nextLast special={$back->special}");
            $this->logger->debug("nextLast when year ={$back->when->year}");
            $this->logger->debug("nextLast when month={$back->when->month}");
            $this->logger->debug("nextLast when day  ={$back->when->day}");

            return $back;
        }
    //
        /**
         * Get total count of entries.
         *
         * Args:
         *     tables (string or array)
         *     dbId (int): if provided, query is appended with 'WHERE id=$dbId'
         *
         * Returns:
         *     (int) total number of entries
         *
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        public function getCount($tables, $dbId=NULL) {
            $this->logger->trace("getCount($tables)");
            $query = "";

            if(!is_array($tables)) {
                $tables = array($tables);
            }

            $query = "SELECT COUNT(*) AS `the_count` FROM `$tables[0]`";

            if(count($tables) > 1) {
                $basequery = "SELECT COUNT(*) AS `count` FROM `";

                foreach($tables as $table) {
                    if($query != "") {
                        $query .= "UNION ALL ";
                    }

                    $query .= "$basequery$table` ";
                }

                $query = "SELECT sum(a.count) AS the_count FROM ($query) a";
            }

            $theCount = NULL;
            if($dbId !== NULL) {
                $count = $this->idManage($query . " WHERE `id` = ?", $dbId);
                $count->bind_result($theCount);
                $count->fetch();

            } else {
                $count = $this->queryManage($query);
                $fetchCount = $count->fetch_object();
                $theCount = $fetchCount->the_count;
            }

            $count->close();
            return $theCount;
        }
    //
        // optimize tables
        public function optimize($tables) {
            if(!is_array($tables)) {
                $tables = array($tables);
            }

            foreach($tables as $table) {
                $this->queryManage("OPTIMIZE TABLE `$table`");
            }
        }
    //
        // SQL sort alpha
        public function sortAlpha($field, $language="") {
            $this->logger->trace("sortAlpha($field)");
            $nodetfield = "{$field}_nodet";
            $back = "";

            // Note: Vim fold titles here are misleading, as indentation is proprotional to SQL indent
                // 3-letter words
                $back .= "IF(";
                $back .= "LEFT(`$field`, 4) = 'The ' ";
                $back .= "OR ";
                $back .= "LEFT(`$field`, 4) = 'Les ' ";
                $back .= ", MID(`$field`, 5),\n";
                    // 2-letter words
                    $back .= "  IF( ";
                    $back .= "LEFT(`$field`, 3) = 'Le ' ";
                    $back .= "OR ";
                    $back .= "LEFT(`$field`, 3) = 'La ' ";
                    $back .= "OR ";
                    $back .= "LEFT(`$field`, 3) = 'An ' ";
                    $back .= ", MID(`$field`, 4),\n";
                        // single-letter character
                        if($language == "english") {
                            $back .= "        IF(";
                            $back .= "LEFT(`$field`,2) = 'A '";
                            $back .= ", MID(`$field`,3),\n";
                        }
                            // Coded characters
                            $back .= "            IF(";
                            $back .= "LEFT(`$field`, 8) = 'L\\\\&#039;'";
                            $back .= ", MID(`$field`, 9),\n";
                                $back .= "                IF(";
                                $back .= "LEFT(`$field`, 7) = 'L&#039;'";
                                $back .= ", MID(`$field`, 8), `$field` )\n";
                            $back .= "            )\n";
                        if($language == "english") {
                            $back .= "        )\n";
                        }
                    $back .= "    )\n";

                $back .= ") AS `$nodetfield`\n";
            return $back;
        }
    //
        // SQL sort num
        public function orderAlpha($field, $way="ASC") {
            $this->logger->trace("orderAlpha($field, $way)");

            $field = "{$field}_nodet";

            $noway = "ASC";
            if($way == "ASC") {
                $noway = "DESC";
            }

            $back = "`$field` IS NULL $noway, ";
            $back .= "`$field` = '' $noway, ";
            $back .= "SUBSTRING_INDEX(`$field`,' ',1) + 0 > 0 $noway, ";
            $back .= "SUBSTRING_INDEX(`$field`,' ',1) + 0 $way, ";
            $back .= "`$field` $way";
            return $back;
        }
    //
        // SQL query with Sort+Order alpha
        public function queryAlpha($table, $field, $way="ASC", $language="") {
            $this->logger->trace("queryAlpha($table, $field, $way)");
            $query = "";
            $query .= "SELECT *, ";
            $query .= $this->sortAlpha($field, $language);
            $query .= "FROM `$table` ";
            $query .= "ORDER BY ";
            $query .= $this->orderAlpha($field, $way);
            return $this->queryManage($query);
        }
    //
        /**
         * SQL query with sort and order improved
         *
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        public function queryXi($table, $fields, $language="") {
            $this->logger->trace("queryXi($table)");

            $query = "";
            $queryselect  = "";
            $queryselect .= "SELECT *";
            $queryorder  = "";

            $coma = ", ";
            foreach($fields as $field => $way) {
                $queryorder .= $coma;

                if(substr($way, 0, 1) == "a") {
                    $queryselect .= ", " . $this->sortAlpha($field, $language);
                    $way = substr($way, 1);

                    if($way == "") {
                        $way = "ASC";
                    }

                    $queryorder .= $this->orderAlpha($field, $way);

                } else {
                    if($way == "") {
                        $way = "ASC";
                    }

                    $queryorder .= " `$field` $way";
                }
            }

            // Remove initial coma
            $queryorder = substr($queryorder, strlen($coma));

            $queryselect .= "FROM `$table`";
            $query = "$queryselect ORDER BY $queryorder";
            return $this->queryManage($query);
        }
    //
        /**
         * Load a file.
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function loadFile($fieldname, $filename, $path, $maxfilesize, $maximgsize, $querybound, $reduce=true) {
            global $theFileHelper;
            $fullname = $theFileHelper->loadFile($fieldname, $filename, $path, $maxfilesize, $maximgsize, $reduce);

            if($fullname === NULL) {
                // Something failed upstream, abort
                return;
            }

            $this->logger->trace("loadFile to SQL");
            if(!$querybound->execute()) {
                $this->logger->error("loadFile ERROR SQL failed, deleting file");
                unlink($fullname);
                $this->displayError($querybound);
            }
        }
}


// singleton
// http://xkcd.com/327/
$theBobbyTable = new DatabaseHelper();
?>
