<?php
namespace sheltie\SimpleManageDb\lib;
class Common{

    protected $__local = "./";

    protected $__dbMapDirectoryName = 'dbMap';

    protected $__intTypes = [
        "tinyint","smallint","int","integer","bigint"
    ];
    protected $__decimalsTypes = [
        "float","double","decimal"
    ];
    protected $__stringsTypes = [
        "char","varchar","tinytext","text","mediumtext","longtext","enum","set"
    ];

    protected function onlyColumnName(string $columnType):string
    {
        $typeCheck = strstr($columnType,"(",true);
        if($typeCheck === false){
            return $columnType;
        }else{
            return $typeCheck;
        }
    }
    protected function checkIsNumberType($columnType):bool
    {
        $columnName = $this->onlyColumnName($columnType);
        return in_array(strtolower($columnName),array_merge($this->__intTypes,$this->__decimalsTypes));
    }

    protected function checkIsStringType($columnType):bool
    {
        $columnName = $this->onlyColumnName($columnType);
        return in_array(strtolower($columnName),$this->__stringsTypes);
    }

    protected function getTableNameByFilePath($filePath)
    {
        // $filePath = "./dbMap/pm/store/pm_store_cart.php";
        // return pm_store_cart
        return strstr(substr($filePath, strripos($filePath, "/") + 1), '.', true);
    }

    protected function parseFieldDataType($fieldConfigInfo):array
    {
        $cNull = strtolower($fieldConfigInfo['null']);
        if (isset($fieldConfigInfo['null']) && $cNull !== "yes") {
            $isNull = "NOT NULL";
        } else {
            $isNull = "";
        }
        if (!isset($fieldConfigInfo['default']) || strtolower($fieldConfigInfo['default']) === 'null') {
            $default = "DEFAULT NULL";
        } else {
            if ($this->checkIsStringType($fieldConfigInfo['type'])) {
                $default = "DEFAULT '" . $fieldConfigInfo['default'] . "'";
            } else {
                $default = "DEFAULT " . $fieldConfigInfo['default'];
            }
        }

        if (empty($fieldConfigInfo['comment'])) {
            $comment = "";
        } else {
            $comment = "COMMENT '{$fieldConfigInfo['comment']}'";
        }

        return [$isNull,$default,$comment];

    }

    protected function parseIndexDataType($indexStruct)
    {
        $fulltext = "";
        $unique = '';
        if(isset($indexStruct["unique"]) && $indexStruct["unique"]){
            $unique = "UNIQUE";
        }else{
            if(isset($indexStruct['fulltext']) && $indexStruct['fulltext']){
                $fulltext = "FULLTEXT";
            }
        }

        if(isset($indexStruct['index_type']) && in_array(strtoupper($indexStruct['index_type']),['BTREE','HASH'])){
            $indexType = " USING {$indexStruct['index_type']}";
        }else{
            $indexType = "";
        }

        return [$unique,$fulltext,$indexType];
    }


    protected function outputError($message)
    {
        echo "\n$message\n";
    }

}