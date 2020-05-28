<?php
namespace Library;

class MO
{
    private static $domain = NULL;
    private static $lang2locale = array(
        'dutch' => 'nl',
        'finnish' => 'fi',
        'german' => 'de_DE',
        'polish' => 'pl_PL',
        'czech' => 'cs_CZ',
        'english' => 'en_US',
        'french_canada' => 'fr_CA',
        'spanish_colombia' => 'es_CO',
    );


    /**
     * Initialize a language.
     * Pass
     *
     * @access  public  so that notifications may set the language as needed.
     * @param   string  $locale     Locale to use, global config by default
     */
    public static function init($lang = NULL)
    {
        global $_CONF, $LANG_LOCALE;

        self::$domain = 'library';
        if ($lang === NULL) {
            $lang = $_CONF['language'];
        }
        $parts = explode('_', $lang);
        if (count($parts) > 2 && isset(self::$lang2local[$parts[0] . '_' . $parts[1]])) {
            // 2-part language, e.g. "french_canada"
            $locale = self::$lang2locale[$parts[0] . '_' . $parts[1]];
        } elseif (isset(self::$lang2locale[$parts[0]])) {
            // single-part language, e.g. "english"
            $locale = self::$lang2locale[$parts[0]];
        } elseif (!empty($LANG_LOCALE)) {
            // Not found, try the global variable
            $locale = $LANG_LOCALE;
        } else {
            // global not set, fall back to US english
            $locale = 'en_US';
        }
        $locale='de_DE';

        $results = setlocale(LC_MESSAGES, $locale);
        if ($results) {
            $dom = bind_textdomain_codeset(self::$domain, 'UTF-8');
            $dom = bindtextdomain(self::$domain, __DIR__ . "/locale");
        }
    }

    public static function dngettext($single, $plural, $number)
    {
        if (!self::$domain) self::init();
        return \dngettext(self::$domain, $single, $plural, $number);
    }
    public static function dgettext($txt)
    {
        if (!self::$domain) self::init();
        return \dgettext(self::$domain, $txt);
    }

}


/**
 * Get a single or plural text string as needed.
 *
 * @param   string  $single     Text when $number is singular
 * @param   string  $plural     Text when $number is plural
 * @param   float   $number     Number used in the string
 * @return  string      Appropriate text string
 */
function _n($single, $plural, $number)
{
    return MO::dngettext($single, $plural, $number);
}


/**
 * Get a single text string.
 *
 * @param   string  $txt    Text to be translated
 * @return  string      Translated string
 */
function _($txt)
{
    return MO::dgettext($txt);
}

?>
