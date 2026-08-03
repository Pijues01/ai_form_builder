<?php

/*
 * PHP 8.5 deprecated the PDO::MYSQL_ATTR_SSL_CA class constant at compile time.
 * Laravel's framework config/database.php still references it, and Laravel 11 loads
 * the framework config as the base config on every boot. This script rewrites that
 * reference to the namespaced equivalent (available since PHP 8.0) so the deprecation
 * does not fire. It is idempotent and a no-op on PHP < 8.5.
 */

if (PHP_VERSION_ID < 80500) {
    exit(0);
}

$file = __DIR__.'/../vendor/laravel/framework/config/database.php';

if (! is_file($file)) {
    exit(0);
}

$content = file_get_contents($file);
$replaced = str_replace('PDO::MYSQL_ATTR_SSL_CA', 'Pdo\\Mysql::ATTR_SSL_CA', $content);

if ($replaced !== $content) {
    file_put_contents($file, $replaced);
}
