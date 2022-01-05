<?php

spl_autoload_register(function ($name) {
    // $d = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src");
    $d = __DIR__;
    switch($name) {
        case "xml_file": require_once($d . "/class-xml-file.php"); break;
        case "xml_file_base": require_once($d . "/class-xml-file-base.php"); break;
        case "xml_file_interface": require_once($d . "/class-xml-file-interface.php"); break;
    }    
});
__HALT_COMPILER();
