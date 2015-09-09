<?php

  use Illuminate\Database\Capsule\Manager as Capsule;

  $capsule = new Capsule();

  $capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => 'ts3api',
    'password' => 'FMrDpzyEAqyzPfqX',
    'database' => 'ts3api',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => 'ts3api_'
    ])

    $capsule->bootEloquent();
?>
