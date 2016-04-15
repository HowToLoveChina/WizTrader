<?php

$phar = new Phar('WizTrader.phar', 0, 'WizTrader.phar');
$phar->buildFromDirectory(dirname(__FILE__) . '/WizTrader');
$phar->setStub($phar->createDefaultStub('index.php', 'index.php'));
$phar->compressFiles(Phar::GZ);
