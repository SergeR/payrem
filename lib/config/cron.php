<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * 
 */
return array(
    'payremSend' => '16 9,13,18 * * * /usr/bin/php -q '.wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR.'cli.php shop payremSend'
);