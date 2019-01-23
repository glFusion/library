<?php
namespace Library;

$domain = $_CONF_LIB['pi_name'];
$results = setlocale(LC_MESSAGES, $LANG_LOCALE);
bind_textdomain_codeset($domain, 'UTF-8');
bindtextdomain($domain, __DIR__ . "/locale");

function _($txt)
{
    return \dgettext('library', $txt);
}
function _n($single, $plural, $number)
{
    return dngettext('library', $single, $plural, $number);
}

?>
