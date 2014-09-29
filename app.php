#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Commands\SyncCommand;

$application = new Application();
$application->add(new SyncCommand);
$application->run();
