<?php

spl_autoload_register(function ($name) {
    if ("xml_file" == $name) {
        $f = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src") . "/class-xml-file.php";
        require_once($f);
    }
});
__HALT_COMPILER();
