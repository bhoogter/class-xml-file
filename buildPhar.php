<?php

$phar = new Phar('xml-file.phar');
$phar->buildFromDirectory('src/');
$phar->addFile("LICENSE");
$phar->addFile("VERSION");
$phar->addFile("README.md");
$phar->setDefaultStub('stub.php');
