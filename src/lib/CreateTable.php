<?php
namespace sheltie\SimpleManageDb\lib;

class CreateTable extends Common
{

    private $__createTableSql = [];


    /**
     * 生成创建table的sql
     * @param array $readyCreateTable
     * @return array
     */
    public function generaCreateTableSql(array $readyCreateTable):array
    {
        foreach ($readyCreateTable as $createTableItem) {
            $fileTableStruct = include $createTableItem["filePath"];
            $tableName = $createTableItem["tableName"];
            $this->getCreateTableSql($tableName, $fileTableStruct);
        }

        return $this->__createTableSql;
    }

    /**
     * 获取sql
     * @param $tableName
     * @param $fileTableStruct
     * @return void
     */
    private function getCreateTableSql($tableName, $fileTableStruct)
    {
        if(empty($fileTableStruct['column'])) return;

        $mainPk = [];
        $sql = "CREATE TABLE `{$tableName}` (";
        foreach ($fileTableStruct['column'] as $fieldConfig) {
            if($fieldConfig['key'] === 'PRI'){
                $mainPk = $fieldConfig;
            }
            $sql .= $this->parseCreateSqlFieldLine($fieldConfig) . ', ';
        }

        $sql = rtrim($sql,', ');
        if(!empty($mainPk)) $sql .= ", PRIMARY KEY (`{$mainPk['field']}`) USING BTREE";

        if(!empty($fileTableStruct['index'])){
            $sql .= ', ';
            foreach ($fileTableStruct['index'] as $indexConfig){
                $sql .= $this->parseCreateSqlIndexLine($indexConfig) . ', ';
            }
            $sql = rtrim($sql,', ');
        }

        $sql .= ")";

        if(!empty($fileTableStruct['engine'])){
            $sql .= ' ENGINE='.$fileTableStruct['engine'];
        }
        if(!empty($fileTableStruct['charset'])){
            $sql .= ' DEFAULT CHARSET='.$fileTableStruct['charset'];
        }
        if(!empty($fileTableStruct['collation'])){
            $sql .= ' COLLATE='.$fileTableStruct['collation'];
        }
        if(!empty($fileTableStruct['comment'])){
            $sql .= ' COMMENT='.$fileTableStruct['comment'];
        }

        $sql .= ";";
        $this->__createTableSql[$tableName][] = $sql;

    }

    /**
     * 通过配置解析建表语句的字段行
     * @param $lineFieldConfInfo
     * @return string
     */
    private function parseCreateSqlFieldLine($lineFieldConfInfo):string
    {
        [$isNull, $default, $comment] = $this->parseFieldDataType($lineFieldConfInfo);
        if(!empty($lineFieldConfInfo['extra'])){
            $extra = $lineFieldConfInfo['extra'];
        }else{
            $extra = '';
        }
        return "`{$lineFieldConfInfo['field']}` {$lineFieldConfInfo['type']} {$isNull} {$extra} {$default} {$comment}";
    }

    private function parseCreateSqlIndexLine($lineIndexConfInfo):string
    {
        [$unique,$fulltext,$indexType] = $this->parseIndexDataType($lineIndexConfInfo);
        $indexFields = implode(',',$lineIndexConfInfo['field']);
        return "{$unique}{$fulltext} KEY {$lineIndexConfInfo['index_name']} ({$indexFields}) {$indexType}";
    }

}