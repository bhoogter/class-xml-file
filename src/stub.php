<?php

spl_autoload_register(function ($name) {
    $d = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src");
    if ($name == "xml_file") require_once($d . "/class-xml-file.php");
    if ($name == "xml_file_base") require_once($d . "/class-xml-file-base.php");
    if ($name == "xml_file_interface") require_once($d . "/class-xml-file-interface.php");
});
__HALT_COMPILER();
