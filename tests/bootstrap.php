<?php

// See https://magento.stackexchange.com/a/143990

require_once __DIR__ . '/../../../app/Mage.php';

function fix_error_handler()
{
    $mageErrorHandler = set_error_handler(function () {
        return false;
    });
    set_error_handler(function ($errno, $errstr, $errfile) use ($mageErrorHandler) {
        if (substr($errfile, -19) === 'Varien/Autoload.php') {
            return null;
        }
        return is_callable($mageErrorHandler) ?
            call_user_func_array($mageErrorHandler, func_get_args()) :
            false;
    });
}

fix_error_handler();

Mage::app();
Mage::setIsDeveloperMode(true);

fix_error_handler();

$_SESSION = [];

if(!Mage::getConfig()->getModuleConfig('Wirecard_ElasticEngine')->is('active', true)) {
    throw new \RuntimeException('The Wirecard Elastic Engine extension is not enabled!');
}
