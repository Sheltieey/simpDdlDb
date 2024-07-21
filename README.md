# SimpDdlDb
基于映射文件的数据库DDL操作脚本工具

## 工具介绍
我开发这个工具是为了解决PHP语言小团队开发过程中，对数据表DDL操作过多细碎又在发布的时候容易忘记的情况。比如将半个月前测试完的分支发布到生产的时候会忘了具体是哪个字段的变更、加了所有、建了新表等，这很不效率。在本工具的支持下可以如下简单操作：
#### 自定义的命令文件位置运行如下命令，即同步数据表字段的DDL操作到当前环境的数据库
```bash
php ddl.php update tie/user
```
- ddl.php 自定义的脚本文件名
- update 更新数据表指令的入参
- tie/user 只更新user部分的表

## 安装

通过 Composer 安装：

```bash
composer require sheltie/simp-ddl-db
```

## 使用示例
将demo.php拷贝到项目根目录上，或者复制以下代码在根目录下创建一个php文件，文件名自定义。内容都是一样的。

```php
<?php
require 'vendor/autoload.php';

$mode = $argv[1]??'';
$specifyDir= $argv[2]??'';
$force = $argv[3]??'false';

const PDO_DSN = "mysql:host=127.0.0.1;dbname=YOURNAME;charset=utf8";
const PDO_USER = "root";
const PDO_PASS = "123456";

use sheltie\SimpleManageDb\SimpleManageDb;
$SimpleDDL= SimpleManageDb::getInstance(new PDO(PDO_DSN, PDO_USER, PDO_PASS));
$SimpleDDL->run($mode,$specifyDir);
```
## 使用说明
- 脚本文件接收三个参数
    - mode：操作指令，目前支持 generate和update
        - generate: 生成配置数据库内的所有数据表的映射文件。默认生成位置在脚本文件同级的dbMap目录下。
        - update：更新数据表，或接收第二个参数更新制定范围数据表。
    - specifyDir：制定目录指令，只支持在update操作指令下生效。
    - force： 强制执行，赞不支持，后期开发
- 需要依赖PDO。
- 生成的dbMap目录内只支持两级目录，根据表名如tie_user_account，对应生成的文件路径 ./dbMap/tie/user/tie_user_account.php

## 初始化
第一次时候的时候必须要先将数据表生成映射文件
```bash
php demo.php generate
```
运行成功完成后，在demo.php同级目录下会生成dbMap目录，目录内就是当前数据库链接的所有数据表映射文件。

## 修改表字段
原来的映射文件内的表结构。表名：df_user_sales，映射文件路径： ./dbMap/df/user/df_user_sales.php
```php
<?php
return [
  'engine' => 'InnoDB',
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_unicode_ci',
  'comment' => '',
  'column' => 
  [
    [
      'field' => 'id',
      'type' => 'int(4) unsigned',
      'null' => 'NO',
      'key' => 'PRI',
      'default' => NULL,
      'extra' => 'auto_increment',
      'comment' => '',
    ],
    [
      'field' => 'uid',
      'type' => 'int(4) unsigned',
      'null' => 'NO',
      'default' => '0',
      'comment' => '',
    ],
```
修改后的映射文件表结构
```php
<?php
return [
  'engine' => 'InnoDB',
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_unicode_ci',
  'comment' => '',
  'column' => 
  [
    [
      'field' => 'id',
      'type' => 'int(4) unsigned',
      'null' => 'NO',
      'key' => 'PRI',
      'default' => NULL,
      'extra' => 'auto_increment',
      'comment' => '',
    ],
    [
      'field' => 'uid',
      'type' => 'varchar(255)', // 修改
      'null' => 'NO',
      'default' => '',          // 修改
      'comment' => '字符串',          // 修改
    ],
```
在cli命令行上运行修改命令
```bash
php ddl.php update df/user
```
或者
```bash
php ddl.php update
```
会看到命令窗口显示
```bash
ALTER TABLE `df_user_sales` MODIFY uid varchar(255) NOT NULL DEFAULT '' COMMENT '字符串';
exec complete.
```


## dbMap映射文件说明
- 文件内容都是数据内容，修改字段即修改数据内容，也可以模拟文件内容写法创建新表。
- update指令会比对数组内的 column 、 index 内容，而其他如 engine 、charset会在建表时起作用。
## 贡献和问题报告

如果您发现了 bug 或者有任何建议，请提交一个 issue 或者 fork 这个项目并提交一个 pull request。

