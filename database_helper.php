<?php
require_once("helper.php");
require_once("login_helper.php");
require_once("server_helper.php");
require_once("text_helper.php");
use mysqli;


class DatabaseText {
        // sql2url: from SQL to HTML readonly with conversion of links
        public function sql2url($text) {
            return preg_replace("/(https?:\/\/[^ \n<>]+)/", '<a href="\1" title="\1">\1</a>', $text);
        }
    //
        // sql2field: from SQL to HTML field
        public function sql2field($text) {
            return htmlspecialchars_decode($text, ENT_NOQUOTES);
        }
    //
        // field2SQL: from field to SQL
        public function field2SQL($text) {
            return stripslashes(htmlentities($text, ENT_QUOTES, "UTF-8"));
        }
    //
        // filename2SQL: from filename to SQL
        public function filename2SQL($text) {
            global $theTextHelper;
            $ext     = $theTextHelper->getExt($text);
            $without = $theTextHelper->woExt($text);

            $back = $without;
            $back = preg_replace("/\r?\n/", "", $back);
            $back = $this->field2SQL($back);
            $back = preg_replace("/&([a-zA-Z])[a-z]+;/", '\1', $back);
            $back = $this->sql2field($back);
            return "$back$ext";
        }
    //
        // sql2txtarea: from SQL to textarea
        public function sql2txtarea($text) {
            return $this->sql2field(preg_replace("/<br\s*\/?>/i", "", $text));
        }
    //
        // txtarea2SQL: from textarea to SQL
        public function txtarea2SQL($text) {
            return trim(nl2br(preg_replace("/(\r?\n)*$/", "", $this->field2SQL($text))));
        }
    //
        // sql2itemize: from SQL to textarea
        public function sql2itemize($text) {

            // WARNING sql2itemize not tested

            $back = str_replace("</li>\n<li>", "\r\n", $text);
            $back = preg_replace("/<\/li>$/", "", $back);
            $back = preg_replace("/^<li>/", "", $back);
            return $this->sql2field($back);
        }
    //
        // itemize2SQL: from textarea to SQL
        public function itemize2SQL($text) {

            // WARNING itemize2SQL not tested

            $back = str_replace(array("\r\n", "\n", "\r"), "</li>\n<li>", $this->field2SQL($text));
            $back = preg_replace("/^/", "<li>", $back);
            $back = preg_replace("/$/", "</li>", $back);
            return $back;
        }
    //
        // sql2paragraph: from SQL to textarea
        public function sql2paragraph($text) {
            $back = str_replace("</p>", PHP_EOL, $text);
            $back = str_replace("<p>", "", $back);
            $back = preg_replace("/<p [^>]*>/", "", $back);
            $back = str_replace("<br />", "", $back);
            $back = preg_replace("/\n$/", "", $back );
            return $this->sql2field($back);
        }
    //
        // paragraph2SQL: from textarea to SQL
        public function paragraph2SQL($text, $class="") {
            $back = $this->field2SQL($text);

            $tag = "";
            if($class != "") {
                $tag = " class=\"$class\"";
            }

            $br2 = array("\r\n\r\n\r\n\r\n", "\n\n\n\n", "\r\r\r\r", "\r\n\r\n\r\n", "\n\n\n", "\r\r\r", "\r\n\r\n", "\n\n", "\r\r");

            $back = preg_replace("/(\r?\n)*$/", "", $back);
            $back = str_replace($br2, "</p><p$tag>", $back);
            $back = nl2br($back);
            $back = preg_replace("/<\/p><p/", "</p>\n<p", $back);
            $back = "<p$tag>$back</p>";

            if($back == "<p$tag></p>") {
                return "";
            }

            return $back;
        }
}


$theDatabaseText = new DatabaseText();


    // DB data
        // Database data field
        class DbDataField {
            public $type;
            public $value;
            public $bEscaped;
            public $precision;

                /**
                 * Constructor
                 *
                 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
                 */
                public function __construct($type, $value, $bEscaped=true) {
                    $this->type = $type;
                    $this->value = $value;
                    $this->bEscaped = $bEscaped;

                    $this->precision = 3;  // default
                }
            //
                // sql2field: from SQL to HTML field
                public function sql2field($text) {
                    global $theDatabaseText;
                    return $theDatabaseText->sql2field($text);
                }
            //
                // valueSql2Field
                public function valueSql2Field() {
                    if($this->type != "s") {
                        return;
                    }

                    if(!$this->bEscaped) {
                        return;
                    }

                    $this->value = $this->sql2field($this->value);
                    $this->bEscaped = false;
                }
            //
                // field2SQL: from field to SQL
                public function field2SQL($text) {
                    global $theDatabaseText;
                    return $theDatabaseText->field2SQL($text);
                }
            //
                // valueField2Sql
                public function valueField2Sql() {
                    if($this->type != "s") {
                        return;
                    }

                    if($this->bEscaped) {
                        return;
                    }

                    $this->value = $this->field2SQL($this->value);
                    $this->bEscaped = true;
                }
            //
                // Value getter
                public function get() {
                    return $this->value;
                }
            //
                // Value setter
                public function set($newValue) {
                    if($this->bEscaped) {
                        $newValue = $this->field2SQL($newValue);
                    }

                    $this->value = $newValue;
                }
            //
                // Round a value
                //
                // Args:
                //     rounding (int): if NULL, use $this->precision
                public function round($rounding=NULL) {
                    if($this->type == "s") {
                        // Cannot round strings
                        return;
                    }

                    if($rounding === NULL) {
                        $rounding = $this->precision;
                    }

                    $this->set(round($this->get(), $rounding));
                }
        }
    //
        // Database data array
        class DbDataArray {
            public $fields = array();

                /**
                 * Add a field to the array.
                 *
                 * Args:
                 *     field (str)
                 *     type (str)
                 *     value
                 *
                 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
                 * @SuppressWarnings(PHPMD.MissingImport)
                 */
                public function addField($field, $type, $value, $bEscaped=true) {
                    $this->fields[$field] = new DbDataField($type, $value, $bEscaped);
                }
            //
                /**
                 * Getter for field.
                 *
                 * Args:
                 *     field (str)
                 *
                 * Returns:
                 *     Value of field
                 */
                public function get($field) {
                    return $this->fields[$field]->get();
                }
            //
                /**
                 * Setter for field
                 *
                 * Args:
                 *    field (str)
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
                public function setDataValuesFromPost($page, $escapeStrings=NULL) {
                    if($escapeStrings === NULL) {
                        $escapeStrings = isset($_POST["submit"]);
                    }

                    foreach ($this->fields as $field => $data) {
                        if($field == "id" && !isset($_POST[$field])) {
                            // When insert new entry, no id available
                            continue;
                        }

                        $value = $_POST[$field];

                        if($data->type == "s") {
                            // Convert string to be safe in SQL
                            $value = $page->field2SQL($value);
                        }

                        $this->set($field, $value);
                    }
                }
            //
                // Adding char escaping to DB data from fields
                public function field2SQL() {
                    foreach (array_keys($this->fields) as $field) {
                        $this->fields[$field]->valueField2Sql();
                    }
                }
            //
                // Removing char escaping from DB data to provide into fields
                public function sql2field() {
                    foreach (array_keys($this->fields) as $field) {
                        $this->fields[$field]->valueSql2Field();
                    }
                }
            //
                /**
                 * Round a value
                 *
                 * Args:
                 *     field (str)
                 *     rounding (int)
                 */
                public function round($field, $rounding=NULL) {
                    $this->fields[$field]->round($rounding);
                }
        }

// TODO backup/dump whole DB

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
         *     table (str): name of the table where we want to update an entry
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
         *     table (str): name of the table where we want to update an entry
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
         *     table (str): name of the table where we want to delete the entry
         *     dbId (int): ID of the entry to delete
         */
        public function queryDelete($table, $dbId) {
            $this->logger->trace("queryDelete($table, $dbId)");
            $this->idManage("DELETE FROM `{$this->dbName}`.`$table` WHERE `$table`.`id` = ? LIMIT 1;", $dbId);
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
        public function selectId($table, $dbId, $field="id") {
            return $this->idManage("SELECT * FROM `$table` WHERE `$field` = ?", $dbId);
        }
    // TODO one with bind_result???
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
         * get next or last entries
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.ElseExpression)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         * @SuppressWarnings(PHPMD.NPathComplexity)
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function nextLast($tables, $bNextNlast=true, $bIsFemale=false) {
            $this->logger->trace("nextLast(..., bNextNlast=" . (int) $bNextNlast . ", bIsFemale=" . (int) $bIsFemale . ")");

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

            if($bNextNlast) {
                $theNext = $this->getDateEvent("$query > 1");
                $theTomorrow = $this->getDateEvent("$query = 1");

                $back->what = $this->languageHelper->translate("next", $bIsFemale);

            } else {
                $theLast = $this->getDateEvent("$query < 0", "DESC");

                $back->what = $this->languageHelper->translate("last", $bIsFemale);
            }

            if($theNext === NULL && $theTomorrow === NULL && $theToday === NULL && $theLast === NULL) {
                $this->logger->trace("nextLast empty");
                $back->what = "";  // reset
                return $back;
            }

            if($theToday !== NULL) {
                $this->logger->trace("nextLast is today");
                $back->special = $this->languageHelper->translate("today");
                $back->when = $theToday;

            } elseif($theTomorrow !== NULL) {
                $this->logger->trace("nextLast is tomorrow");
                $back->special = $this->languageHelper->translate("tomorrow");
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

            if($dbId !== NULL) {
                $count = $this->idManage($query . " WHERE `id` = ?", $dbId);

            } else {
                $count = $this->queryManage($query);
            }

            $fetchCount = $count->fetch_object();
            $count->close();
            return $fetchCount->the_count;
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
}


// singleton
$theDbHelper = new DatabaseHelper();
?>
