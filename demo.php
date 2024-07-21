<?php
require 'vendor/autoload.php';

$mode = $argv[1]??'';
$specifyDir= $argv[2]??'';
$force = $argv[3]??'false';

const PDO_DSN = "mysql:host=127.0.0.1;dbname=new_tv_data;charset=utf8";
const PDO_USER = "root";
const PDO_PASS = "123456";

use sheltie\SimpleManageDb\SimpleManageDb;
$SimpleDDL= SimpleManageDb::getInstance(new PDO(PDO_DSN, PDO_USER, PDO_PASS));
$SimpleDDL->run($mode,$specifyDir);
