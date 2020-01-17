<?php

spl_autoload_register(function ($name) {
    if ("xml_file" == $name) require_once("phar://" . __FILE__  . "/src/class-xml-file.php");
});
__HALT_COMPILER();
