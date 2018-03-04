#!/usr/bin/env php
<?php
require_once __DIR__.'/../vendor/autoload.php';

use appName\Command;
use Symfony\Component\Console\Application;

$app = new Application('appName', '0.0.0');

$app->addCommands([new Command\StartCommand()]);
//$app->addCommands([new Command\StopCommand()]);
$app->run();
?>
