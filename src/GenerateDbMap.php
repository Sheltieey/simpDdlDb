<?php

namespace sheltie\SimpleManageDb;

use PDO;
use RuntimeException;
use sheltie\SimpleManageDb\lib\Common;

class GenerateDbMap extends Common
{
    private $__writeStrReplace = [
        'search' => [
            0 => "array (",
            1 => "array(",
            2 => "),",
            3 => ");"
        ],
        'replace' => [
            0 => "[",
            1 => "[",
            2 => "],",
            3 => "];",
        ]
    ];


    public function __construct($dirName="")
    {
        if (!empty($dirName)) $this->__dbMapDirectoryName = $dirName;
    }

    /**
     * 全部生成dbMap
     * @param $con
     * @param $force
     * @return void
     */
    public function generate($con,$force)
    {

        // 执行查询
        $stmt = $con->query('show tables');
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $tables = array_column($list, 'Tables_in_new_tv_data');
        if(empty($tables)) throw new RuntimeException("empty table");

        $this->makeDirectory($this->__local.$this->__dbMapDirectoryName);

        foreach ($tables as $tableName){

            $filePath = $this->analyzeTableName($tableName);

            $stmt = $con->query("SHOW FULL COLUMNS FROM `{$tableName}`;");
            $tableColumn = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $con->query("SHOW INDEX FROM `{$tableName}`;");
            $tableIndex = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $con->query("SHOW TABLE STATUS LIKE '{$tableName}';");
            $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->writeTableContent($tableColumn,$tableIndex,$tableInfo[0],$filePath);
        }

    }

    /**
     * 生成一个指定表的dbMap
     * @param $con
     * @param $tableName
     * @return void
     */
    public function generateOfTable($con,$tableName)
    {
        $filePath = $this->analyzeTableName($tableName);
        // var_dump($filePath);
        // touch($filePath,filemtime($filePath));

        $stmt = $con->query("SHOW FULL COLUMNS FROM `{$tableName}`;");
        $tableColumn = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SHOW INDEX FROM `{$tableName}`;");
        $tableIndex = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SHOW TABLE STATUS LIKE '{$tableName}';");
        $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->writeTableContent($tableColumn,$tableIndex,$tableInfo[0],$filePath);
    }

    /**
     * 写入生成文件
     * @param array $tableColumns
     * @param array $tableIndex
     * @param array $tableInfo
     * @param $filePath
     * @return void
     */
    private function writeTableContent(array $tableColumns,array $tableIndex,array $tableInfo,$filePath):void
    {
        // table info
        $writeContent['engine'] = $tableInfo['Engine'];
        $coArr = explode('_',$tableInfo['Collation']);
        $writeContent['charset'] = $coArr[0];
        $writeContent['collation'] = $tableInfo['Collation'];
        $writeContent['comment'] = $tableInfo['Comment'];

        // column
        $writeContent['column'] = $this->assemblyColumns($tableColumns);
        // index
        $writeContent['index'] = $this->assemblyIndex($tableIndex);

        // 开始写入文件
        // 使用 var_export 将数组转换为可读的 PHP 代码
        $arrayString = var_export($writeContent, true);
        // 将 array() 替换为 []
        $arrayString = str_replace($this->__writeStrReplace['search'], $this->__writeStrReplace['replace'], $arrayString);
        // 使用正则表达式去除索引
        $arrayString = preg_replace('/(\d+\s*=>\s*)/', '', $arrayString);
        // 创建包含数组代码的 PHP 文件内容
        $phpCode = "<?php\nreturn " . $arrayString . ";\n";
        $phpCode = str_replace($this->__writeStrReplace['search'][3], $this->__writeStrReplace['replace'][3], $phpCode);

        // 将生成的 PHP 代码写入文件
        $isFail = file_put_contents($filePath, $phpCode);
        if($isFail === FALSE) throw new RuntimeException('Error writing file');
    }

    /**
     * column分析
     * @param $tableColumns
     * @return array
     */
    private function assemblyColumns($tableColumns):array
    {
        $columnArray = [];
        foreach ($tableColumns as $k=>$column){
            unset($column['Privileges'],$column['Collation']);
            if(empty($column['Extra'])) unset($column['Extra']);
            if($column['Key'] != "PRI") unset($column['Key']);

            foreach ($column as $key=>$value){
                if(method_exists($this,'handleColumn'.$key)) $value = $this->{'handleColumn'.$key}($value);
                $columnArray[$k][strtolower($key)] = $value;
            }
        }
        return $columnArray;
    }

    /**
     * index分析
     * @param $tableIndex
     * @return array
     */
    private function assemblyIndex($tableIndex):array
    {
        $indexArray = [];
        foreach ($tableIndex as $indexItem){
            $indexArray[$indexItem['Key_name']]['field'][$indexItem['Seq_in_index']-1] = $indexItem['Column_name'];
            $indexArray[$indexItem['Key_name']]['unique'] = $indexItem['Non_unique'] === '0' ? true : false;
            $indexArray[$indexItem['Key_name']]['index_type'] = $indexItem['Index_type'];
        }

        $indexContent = [];
        foreach ($indexArray as $indexName=>$details){
            if($indexName == 'PRIMARY') continue;
            $indexContent[] = ['index_name'=>$indexName] + $details;
        }
        return $indexContent;
    }

    /**
     * 处理字段类型
     * @param $columnType
     * @return string
     */
    private function handleColumnType($columnType):string
    {
        $typeCheck = $this->onlyColumnName($columnType);

        switch ($typeCheck){
            default:
                return $columnType;
                break;
            case "enum":
                preg_match('#\((.*?)\)#', $columnType, $match);
                $str = '';
                foreach (explode(",",$match[1]) as $v) $str .= trim($v,"'").',';
                return "enum(".rtrim($str,',').")";
                break;
        }
    }


}