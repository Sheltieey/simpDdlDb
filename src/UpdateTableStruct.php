<?php

namespace sheltie\SimpleManageDb;

use PDO;
use PDOException;
use sheltie\SimpleManageDb\lib\Common;
use sheltie\SimpleManageDb\lib\ComparisonColumn;
use sheltie\SimpleManageDb\lib\ComparisonIndex;
use sheltie\SimpleManageDb\lib\CreateTable;

class UpdateTableStruct extends Common
{
    /**
     * 指定检索文件范围
     * @var array
     */
    private $__files = [];

    /**
     * 比对文件标记后，需要修改表内容的
     * @var array
     */
    private $__readyUpdateTable = [];

    /**
     * 比对文件标记后，需要新增表的
     * @var array
     */
    private $__readyCreateTable = [];

    /**
     * 预备要执行的字段修改的sql
     * @var array
     */
    private $__readyExecuteColumnSql = [];

    /**
     * 预备要执行的索引修改的sql
     * @var array
     */
    private $__readyExecuteIndexSql = [];

    /**
     * 预备要执行的建表的sql
     * @var array
     */
    private $__readyCreateTableSql = [];

    private $__generateDbMap;

    private $__reGenerateTable = [];

    public function __construct($dirName = "")
    {
        if (!empty($dirName)) $this->__dbMapDirectoryName = $dirName;
        $this->__generateDbMap = new GenerateDbMap($this->__dbMapDirectoryName);;
    }

    /**
     * 执行入口方法
     * @param $con
     * @param $specifyDir
     * @param $force
     * @return void
     */
    public function update($con, $specifyDir, $force)
    {
        // 检查配置dbMap配置文件，且加入到待比对数组
        $this->check($specifyDir);

        // 预处理，分类好处理方式
        $this->preconditioning($con);

        if (empty($this->__readyUpdateTable) && empty($this->__readyCreateTable)) {
            echo "\nNo data tables available for update";
            return;
        }

        // 比对本地dbMap文件与数据表内的结构，且准备好处理sql
        if (!empty($this->__readyUpdateTable)) {
            $this->comparisonTablesFieldStruct();
        }

        if (!empty($this->__readyCreateTable)) {
            $createTableObj = new CreateTable($this->__readyCreateTable);
            $createTableObj->generaCreateTableSql();
        }

        // print_r($this->__readyCreateTableSql);
        // print_r($this->__readyExecuteColumnSql);
        // print_r($this->__readyExecuteIndexSql);
        // die;

        // 开始运行
        if(!empty($this->__readyCreateTableSql)){
            foreach ($this->__readyCreateTableSql as $tableName=>$sqlMore){
                foreach ($sqlMore as $sql){
                    try {
                        echo "\n$sql\n";
                        $con->exec($sql);
                        echo "exec complete\n";
                        $this->__reGenerateTable[] = $tableName;
                    }catch (\PDOException $exception){
                        echo "exec fail : ".$exception->getMessage()."\n";
                    }
                }
            }
        }
        if(!empty($this->__readyExecuteColumnSql)){
            foreach ($this->__readyExecuteColumnSql as $tableName=>$sqlMore){
                foreach ($sqlMore as $sql){
                    try {
                        echo "\n$sql\n";
                        $con->exec($sql);
                        echo "exec complete\n";
                        $this->__reGenerateTable[] = $tableName;
                    }catch (\PDOException $exception){
                        echo "exec fail : ".$exception->getMessage()."\n";
                    }
                }
            }
        }
        if(!empty($this->__readyExecuteIndexSql)){
            foreach ($this->__readyExecuteIndexSql as $tableName=>$sqlMore){
                foreach ($sqlMore as $sql){
                    try {
                        echo "\nexec sql : $sql\n";
                        $con->exec($sql);
                        echo "exec complete\n";
                        $this->__reGenerateTable[] = $tableName;
                    }catch (\PDOException $exception){
                        echo "exec fail : ".$exception->getMessage()."\n";
                    }
                }
            }
        }

        // 重新生成dbMap映射配置文件
        foreach ($this->__reGenerateTable as $tableName){
            $this->__generateDbMap->generateOfTable($con,$tableName);
        }

    }

    private function comparisonTablesFieldStruct()
    {
        $comparisonColumnObj = new ComparisonColumn();
        $comparisonIndexObj = new ComparisonIndex();
        
        foreach ($this->__readyUpdateTable as $i => $info) {
            $fileTableStruct = include $info["filePath"];
            if (empty($fileTableStruct)) continue;
            $fileTableColumnsStruct = $fileTableStruct["column"];
            $fileTableIndexesStruct = $fileTableStruct["index"];
            $dbTableStruct = $info["dbTableColumn"];
            $dbIndexesStruct = $info["dbTableIndex"];

            $this->__readyExecuteColumnSql = $comparisonColumnObj->execComparisonColumns($info["tableName"], $fileTableColumnsStruct, $dbTableStruct);
            $this->__readyExecuteIndexSql = $comparisonIndexObj->execComparisonIndexes($info["tableName"], $fileTableIndexesStruct, $dbIndexesStruct);
        }
    }

    private function check($specifyDir = "")
    {
        if (!empty($specifyDir)) {
            $dirPath = $this->__local . $this->__dbMapDirectoryName . '/' . $specifyDir;
        } else {
            $dirPath = $this->__local . $this->__dbMapDirectoryName;
        }

        if (!is_dir($dirPath)) throw new \RuntimeException($dirPath . " The DB mapping directory was not found locally");

        $files = scandir($dirPath);
        if (empty($files[2])) throw new \RuntimeException($dirPath . " This directory is empty");

        $this->getFiles($dirPath);
    }

    private function getFiles(string $dirName): void
    {
        $handle = opendir($dirName);
        while (($file = readdir($handle)) !== false) {
            //排除掉当前目录和上一个目录
            if ($file == "." || $file == "..") continue;
            $file = $dirName . DIRECTORY_SEPARATOR . $file;
            //如果是文件就打印出来，否则递归调用
            if (is_file($file)) {
                $this->__files[] = $file;
            } elseif (is_dir($file)) {
                $this->getFiles($file);
            }
        }
    }


    /**
     * 预处理
     * @param $con
     * @return void
     */
    private function preconditioning($con)
    {
        foreach ($this->__files as $filePath) {
            // $fileCreatedTimestamp = filectime($filePath);
            $fileUpdatedTimestamp = filemtime($filePath);
            $fileActiveTimestamp = fileatime($filePath);
            // echo $filePath ."\n";
            // // // echo "创建时间：".date("Y-m-d H:i:s",$fileCreatedTimestamp)."\n";
            // echo "修改时间：".date("Y-m-d H:i:s",$fileUpdatedTimestamp)."  --- {$fileUpdatedTimestamp} \n";
            // echo "访问时间：".date("Y-m-d H:i:s",$fileActiveTimestamp)."  --- {$fileActiveTimestamp} \n";

            if ($fileUpdatedTimestamp !== $fileActiveTimestamp) {

                $tableName = $this->getTableNameByFilePath($filePath);

                try {

                    $stmt = $con->query("SHOW FULL COLUMNS FROM `{$tableName}`;");
                    $tableColumn = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt = $con->query("SHOW INDEX FROM `{$tableName}`;");
                    $tableIndex = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $this->__readyUpdateTable[] = [
                        'tableName' => $tableName,
                        'filePath' => $filePath,
                        'dbTableColumn' => $tableColumn,
                        'dbTableIndex' => $tableIndex,
                    ];

                } catch (PDOException|\Error $PDOException) {

                    if (strpos("doesn't exist", $PDOException->getMessage()) !== false) {
                        // 新建表
                        $this->__readyCreateTable[] = [
                            'tableName' => $tableName,
                            'filePath' => $filePath,
                        ];
                    } else {
                        $this->outputError($tableName . "exception：" . $PDOException->getMessage());
                    }

                }

            }
        }
    }

}