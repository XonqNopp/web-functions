<?php
    class AbstractTranslator {
        protected $words = array();
        protected $wordsFemale = array();

        /**
         * Get translated word (if provided).
         *
         * Args:
         *
         * Returns:
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function get($wordIn, $isFemale=false) {
            if(!in_array($wordIn, $this->words)) {
                return $wordIn;
            }

            if($isFemale && in_array($wordIn, $this->wordsFemale)) {
                return $this->wordsFemale[$wordIn];
            }

            return $this->words[$wordIn];
        }
    }
//

    class FrenchTranslator extends AbstractTranslator {
        protected $words = array(
            "add" => "ajouter",
            "April" => "avril",
            "August" => "ao&ucirc;t",
            "cancel" => "annuler",
            "December" => "d&eacute;cembre",
            "delete" => "effacer",
            "erase" => "effacer",
            "February" => "f&eacute;vrier",
            "found" => "trouv&eacute;",
            "January" => "janvier",
            "July" => "juillet",
            "June" => "juin",
            "last" => "dernier",
            "May" => "mai",
            "March" => "mars",
            "next" => "prochain",
            "nothing" => "rien",
            "November" => "novembre",
            "October" => "octobre",
            "September" => "septembre",
            "time" => "heure",  // special for time form
            "today" => "aujourd'hui",
            "tomorrow" => "demain",
            "update" => "editer",
            "welcome" => "bienvenue",
            "with" => "avec",
            "without" => "sans",
        );

        protected $wordsFemale = array(
            "found" => "trouv&eacute;e",
            "last" => "derni&egrave;re",
            "next" => "prochaine",
        );
    }
//

    class WolofTranslator extends AbstractTranslator {
        protected $words = array(
            "add" => "dolli",
            "cancel" => "fomm",
            "delete" => "far",
            "erase" => "far",
        );
    }
//

    class MandinkaTranslator extends AbstractTranslator {
        protected $words = array(
            "add" => "kafu",
            "cancel" => "bayoo",
            "delete" => "djan djan",
            "erase" => "djan djan",
        );
    }
//

class TranslatorCollection {
    // get(language) -> translator BUT check availa langs
    protected $translators = array();

    /**
     * constructor
     *
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    public function __construct() {
        $this->translators["french"] = new FrenchTranslator();
        $this->translators["wolof"] = new WolofTranslator();
        $this->translators["mandinka"] = new MandinkaTranslator();
    }

    public function get($language) {
        if(!array_key_exists($language, $this->translators)) {
            return NULL;
        }

        return $this->translators[$language];
    }

    /**
     * Get word from translator
     *
     * Args:
     *     language (string)
     *     word (string)
     *     bFemale (bool)
     *
     * Returns:
     *     (string) translated word, or NULL if language not available
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getWord($language, $word, $isFemale=false) {
        $translator = $this->get($language);

        if($translator === NULL) {
            return $word;
        }

        return $translator->get($word, $isFemale);
    }
}


// singleton
$theTranslators = new TranslatorCollection();
?>
