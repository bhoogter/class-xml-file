<?php

spl_autoload_register(function ($name) {
    if ($name == "xml_file_test_utils") require_once(__DIR__ . "/class-xml_file_test_utils.php");
});
