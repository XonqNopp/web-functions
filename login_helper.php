<?php
require_once("helper.php");

require_once("body_helper.php");
require_once("cookie_helper.php");
require_once("crypto_helper.php");
require_once("html_helper.php");


$kGuest = 0;
$kUser = 1;
$kAdmin = 2;
$kSuper = 3;


/**
 * Login and logout for users.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LoginHelper extends MyHelper {
    private $sex = NULL;
    private $scriptName;
    private $cookie = "skipper";
    private $userLevel;
    private $levelDefault = 1;
    private $passwordName = "__p_a_s_s_o_i_r_e__";
    private $logoutKey = "lego";
    private $logoutValue = "aout";
    private $doAlwaysLoginLogout = true;
    private $loginLinkClass = NULL;

    /**
     * Setup
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function setup($sex, $scriptName, $filename) {
        $this->sex = $sex;
        $this->scriptName = $scriptName;

        if(property_exists($this->sex, "doAlwaysLoginLogout")) {
            $this->doAlwaysLoginLogout = $this->sex->doAlwaysLoginLogout;
        }

        if(property_exists($this->sex, "loginLinkClass")) {
            $this->loginLinkClass = $this->sex->loginLinkClass;
        }

        if(
            !isset($_SESSION[$this->sex->session])
            || $_SESSION[$this->sex->session] === NULL
            || $_SESSION[$this->sex->session] == ""
        ) {
            $_SESSION[$this->sex->session] = $this->sex->GuestValue;
        }

        $this->setUserLevel();

        $this->eatCookie($filename);

        if(!$this->doAlwaysLoginLogout) {
            return;
        }

        if($this->checkLogOut()) {
            return;
        }

        $this->checkPassword();
    }

    public function tearDown() {
        if(!$this->doAlwaysLoginLogout) {
            return;
        }

        echo $this->getLoginLink();
    }

        /**
         * Set user level from session.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        private function setUserLevel() {
            $this->logger->debug("setUserLevel()");

            switch($_SESSION[$this->sex->session]) {
                case $this->sex->LoggedValue:
                    global $kUser;
                    $this->userLevel = $kUser;
                    break;

                case $this->sex->AdminValue:
                    global $kAdmin;
                    $this->userLevel = $kAdmin;
                    break;

                case $this->sex->SuperValue:
                    global $kSuper;
                    $this->userLevel = $kSuper;
                    break;

                default:
                    global $kGuest;
                    $this->userLevel = $kGuest;
                    break;
            }

            // Update admin flag for logger
            $this->logger->userIsAdmin($this->userIsAdmin());
        }

        public function userIsGuest() {
            global $kGuest;
            return $this->userLevel == $kGuest;
        }
    //
        public function userIsLogged() {
            global $kUser;
            return $this->userLevel >= $kUser;
        }
    //
        public function userIsAdmin() {
            global $kAdmin;
            return $this->userLevel >= $kAdmin;
        }
    //
        public function userIsSuper() {
            global $kSuper;
            return $this->userLevel >= $kSuper;
        }
    //
        // if not allowed, make guest to index
        public function notAllowed($url="index.php", $admin=0) {
            $cond = $this->userIsGuest();
            if($admin == 1) {
                $cond = $this->userIsAdmin();
            } elseif($admin == 2) {
                $cond = $this->userIsSuper();
            }

            if(!$cond) {
                return;
            }

            global $theHtmlHelper;
            $theHtmlHelper->headerLocation($url);
        }
    //
        /**
         * Bake cookie (write)
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        private function bakeCookie() {
            $this->logger->trace("bakeCookie()");
            global $theOven;
            global $theBatman;
            $theOven->bake(
                $this->cookie,
                $theBatman->hache($_SESSION[$this->sex->session] . $this->sex->sugar)
            );
        }
    //
        /**
         * Eat login cookie (check)
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function eatCookie($redirect="index.php") {
            $this->logger->trace("eatCookie(redirect=$redirect)");

            global $theOven;
            $cookietxt = $theOven->retrieve($this->cookie);
            if(!$this->userIsGuest() || $cookietxt === NULL) {
                return;
            }

            $this->logger->debug("eatCookie user is guest and found cookie: $cookietxt");

            $sugar = $this->sex->sugar;

            global $theBatman;
            $value = NULL;
            if($theBatman->hache($this->sex->LoggedValue . $sugar, $cookietxt)) {
                $value = $this->sex->LoggedValue;

            } elseif($theBatman->hache($this->sex->AdminValue . $sugar, $cookietxt)) {
                $value = $this->sex->AdminValue;

            } elseif($theBatman->hache($this->sex->SuperValue . $sugar, $cookietxt)) {
                $value = $this->sex->SuperValue;

            }

            if($value === NULL) {
                $this->logger->debug("eatCookie not matching, invalidating...");
                $theOven->burn($this->cookie);
                return;
            }

            $_SESSION[$this->sex->session] = $value;
            $this->setUserLevel();

            $this->logger->debug("eatCookie match found");
            $this->bakeCookie();

            if($redirect == "") {
                return;
            }

            global $theHtmlHelper;
            $theHtmlHelper->headerLocation($redirect);
        }
    //
        /**
         * Login successful
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        private function loginSuccessful($level=0, $writeCookie=True) {
            $this->logger->trace("loginSuccessful(level=$level, bWriteCookie=$writeCookie)");

            $values = array(
                $this->sex->LoggedValue,
                $this->sex->AdminValue,
                $this->sex->SuperValue,
            );

            if($level > count($values)) {
                $this->logger->fatal("Level not approved");
            }

            $_SESSION[$this->sex->session] = $values[$level];
            $this->setUserLevel();

            if($writeCookie) {
                $this->bakeCookie();
            }
        }
    //
        /**
         * Log out
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function logOut($redirect=NULL) {
            if(isset($_GET["from"]) && $_GET["from"] !== NULL && $_GET["from"] != "") {
                $redirect = $_GET["from"];
            }

            $sessionName = session_name();
            $_SESSION = array();

            global $theOven;
            $theOven->burn($this->cookie);
            $theOven->burn($sessionName);

            session_unset();
            session_destroy();

            if($redirect === NULL) {
                $redirect = $this->scriptName;
            }

            global $theHtmlHelper;
            $theHtmlHelper->headerLocation($redirect);
        }
    //
        /**
         * Get the link to the login/logout page.
         */
        public function getLoginLink($class=NULL) {
            if($this->scriptName == "/login.php") {
                // No need to provide a link on this page.
                return "";
            }

            global $theBodyBuilder;

            $linkName = "login";
            $logPage = "/login.php?from={$this->scriptName}";
            if($this->userIsLogged()) {
                $linkName = "logout";
                $logPage = "{$this->scriptName}?{$this->logoutKey}={$this->logoutValue}";
            }

            if($class === NULL && $this->loginLinkClass !== NULL) {
                $class = $this->loginLinkClass;
            }
            if($class !== NULL) {
                $class = " class=\"$class\"";
            }

            $contents = "<div$class>";
            $contents .= $theBodyBuilder->anchor($logPage, $linkName);
            $contents .= "</div>\n";
            return $contents;
        }
    //
        private function getPasswordLevel($user) {
            if(is_string($user)) {
                // Just the password
                return array("password" => $user, "level" => $this->levelDefault);
            }

            $level = $this->levelDefault;
            $hash = $user->password;
            if(isset($user->level)) {
                $level = $user->level;

                if($level < 0 || $level > 2) {
                    $this->logger->error("ValueError(configured level $level)");
                }
            }

            return array("password" => $hash, "level" => $level);
        }
    //
        /**
         * Check the password.
         *
         * This uses initLocal->sex->users which can be either of:
         *
         * - a string: hash of the password
         * - a stdClass containing the fields password (hashed) and optionally level
         * - an associative array, keys being the username, values being one of the above
         *
         * Returns:
         *     bool: True if login successful
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.ElseExpression)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         */
        public function checkPassword() {
            if(!isset($_POST["loginPW"])) {
                // Not coming from login, nothing to do
                return false;
            }

            global $theHtmlHelper;

            $username = NULL;
            if(isset($_POST["loginUsername"]) && $_POST["loginUsername"] !== NULL && $_POST["loginUsername"] != "") {
                $username = $_POST["loginUsername"];
            }

            if(is_array($this->sex->users)) {
                // multiple users possible
                // check username
                foreach($this->sex->users as $sexUser => $sexArgs) {
                    if($username != $sexUser) {
                        continue;
                    }

                    $sexTract = $this->getPasswordLevel($sexArgs);
                    break;
                }

            } else {
                // only one user possible
                if($username !== NULL) {
                    $theHtmlHelper->headerLocation("login.php?wrong=$username&from={$this->scriptName}");
                    return false;
                }

                $sexTract = $this->getPasswordLevel($this->sex->users);
            }

            global $theBatman;
            if(!$theBatman->hache($_POST["loginPW"], $sexTract["password"])) {
                $theHtmlHelper->headerLocation("login.php?wrong={$this->passwordName}&from={$this->scriptName}");
                return false;
            }

            $this->loginSuccessful($sexTract["level"], $_POST["fire"] == "cold");
            return true;
        }
    //
        /**
         * Check if it is requested to log out.
         *
         * Returns:
         *     bool: True if we do a log out
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function checkLogOut() {
            if(!isset($_GET[$this->logoutKey]) || $_GET[$this->logoutKey] != $this->logoutValue) {
                return false;
            }

            $this->logOut();
            return true;
        }
    //
        /**
         * Check if we got a wrong username or password.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        public function checkWrongData() {
            if(!isset($_GET["wrong"]) || $_GET["wrong"] === NULL) {
                return;
            }

            if($_GET["wrong"] == $this->passwordName) {
                $this->logger->error("Wrong password!");
            } else {
                $this->logger->error("Invalid username: {$_GET['wrong']}");
            }
        }
}


// singleton
$theLoginHelper = new LoginHelper();
?>
