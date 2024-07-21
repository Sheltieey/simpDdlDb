<?php
require 'vendor/autoload.php';

// require_once "./src/SimpleManageDb.php";
// require 'vendor/autoload.php';

$mode = $argv[1]??'';
$specifyDir= $argv[2]??'';
$force = $argv[3]??'false';

if (!is_string($mode) || !in_array($mode,['generate','update'])){
    die("\n请输出正确指令\n");
}

use sheltie\SimpleManageDb\SimpleManageDb;

$dsn = 'mysql:host=127.0.0.1;dbname=new_tv_data;charset=utf8';
$username = 'root';
$password = '123456';

$pdoConnect = new PDO($dsn, $username, $password);
$a = SimpleManageDb::getInstance($pdoConnect);

switch ($mode){
    case 'generate':
        $a->generateDbMapping();
        break;
    case 'update':
        $a->updateTableStruct($specifyDir);
        break;
}