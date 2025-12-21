<?php

# Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

# Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.test');
$dotenv->load();
