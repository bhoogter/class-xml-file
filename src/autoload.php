<?php

spl_autoload_register(function ($name) {
    if ($name == "xml_file") require_once(__DIR__ . "/class-xml-file.php");
});
