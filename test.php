<?php
require_once __DIR__.'/vendor/autoload.php';
$r = new Shela\RedfinParser\Client();
$request = new Shela\RedfinParser\Api\Request();
//$request->setProxy('');
$tags = $r->query($request)->search('90302');
var_dump($tags);


