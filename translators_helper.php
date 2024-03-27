<?php
    class AbstractTranslator {
        private $words = array();
        private $wordsFemale = array();

        /**
         * Get translated word (if provided).
         *
         * Args:
         *
         * Returns:
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function get($wordIn, $bFemale=false) {
            if(!in_array($wordIn, $this->words)) {
                return $wordIn;
            }

            if($bFemale && in_array($wordIn, $this->wordsFemale)) {
                return $this->wordsFemale[$wordIn];
            }

            return $this->words[$wordIn];
        }
    }
//

    /**
     * French translator
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateField)
     */
    class FrenchTranslator extends AbstractTranslator {
        private $words = array(
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

        private $wordsFemale = array(
            "found" => "trouv&eacute;e",
            "last" => "derni&egrave;re",
            "next" => "prochaine",
        );
    }
//

    /**
     * Wolof translator
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateField)
     */
    class WolofTranslator extends AbstractTranslator {
        private $words = array(
            "add" => "dolli",
            "cancel" => "fomm",
            "delete" => "far",
            "erase" => "far",
        );
    }
//

    /**
     * Mandinka translator
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateField)
     */
    class MandinkaTranslator extends AbstractTranslator {
        private $words = array(
            "add" => "kafu",
            "cancel" => "bayoo",
            "delete" => "djan djan",
            "erase" => "djan djan",
        );
    }
//

class TranslatorCollection {
    // get(language) -> translator BUT check availa langs
    private $translators = array();

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
    public function getWord($language, $word, $bFemale=false) {
        $translator = $this->get($language);

        if($translator === NULL) {
            return $word;
        }

        return $translator->get($word, $bFemale);
    }
}


// singleton
$theTranslators = new TranslatorCollection();
?>
