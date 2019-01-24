<?php
namespace Library;

//$LANG_LOCALE='de_DE';
$domain = $_CONF_LIB['pi_name'];
//$domain = 'default';
$results = setlocale(LC_MESSAGES, $LANG_LOCALE);
if ($results) {
    $dom = bind_textdomain_codeset($domain, 'UTF-8');
    $dom = bindtextdomain($domain, __DIR__ . "/locale");
}

function _($txt)
{
    return \dgettext('library', $txt);
}
function _n($single, $plural, $number)
{
    return \dngettext('library', $single, $plural, $number);
}

?>
