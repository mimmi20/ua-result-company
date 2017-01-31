<?php
/**
 * Created by PhpStorm.
 * User: Thomas Müller
 * Date: 31.01.2017
 * Time: 07:49
 */

$types = json_decode(file_get_contents('data/companies.json'));
$x = [];
foreach ($types as $key => $data) {
    $x[$key] = ['type' => $key] + (array) $data;
}

file_put_contents(
    'data/companies.json',
    json_encode($x, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
);