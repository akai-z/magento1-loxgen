<?php

define('DS', DIRECTORY_SEPARATOR);
define('BP', getcwd());

$loxgenPharPath = BP . DS . 'build' . DS . 'loxgen.phar';

if (is_readable($loxgenPharPath)) {
    unlink($loxgenPharPath);
}

$phar = new Phar(
    $loxgenPharPath,
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    'loxgen.phar'
);

$phar->startBuffering();

$phar->setStub($phar->createDefaultStub('index.php'));

$phar->buildFromDirectory(BP . DS . 'loxgen');

$phar->stopBuffering();

echo "loxgen.phar has been created\n";
