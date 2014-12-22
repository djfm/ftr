#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$class = $argv[1];

$server = new $class();

$address = $server->bind();

echo "$address\n";

$server->run();
