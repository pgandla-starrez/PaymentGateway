<?php

declare(strict_types=1);

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

if (!$autoloader) {
    echo "Composer autoloader not found. Run 'composer install'.\n";
    exit(1);
}
