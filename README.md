# SimpDdlDb
基于映射文件的数据库DDL操作脚本工具

## 工具介绍
我开发这个工具是为了解决PHP语言小团队开发过程中，对数据表DDL操作过多细碎又在发布的时候容易忘记。比如将半个月前测试完的分支发布到生产的时候会忘了具体是哪个字段的变更情况，这很不效率。在本工具的支持下可以如下简单操作：
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
将demo.php拷贝到项目根目录上，或者复制以下代码在根目录下创建一个php文件。内容都是一样的。

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

## 贡献和问题报告

如果您发现了 bug 或者有任何建议，请提交一个 issue 或者 fork 这个项目并提交一个 pull request。

