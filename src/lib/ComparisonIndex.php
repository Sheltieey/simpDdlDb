<?php
namespace sheltie\SimpleManageDb\lib;

class ComparisonIndex extends Common
{
    private $__modifyIndexSql = [];

    /**
     * dbMap配置文件与db上的 索引比对
     * @param $tableName
     * @param $mainIndex
     * @param $dbIndex
     * @return array
     */
    public function execComparisonIndexes($tableName, $mainIndexArray, $dbIndexArray):array
    {
        $dbIndex = [];
        foreach ($dbIndexArray as $item){
            if($item['Key_name'] == 'PRIMARY') continue;
            $dbIndex[$item['Key_name']][] = $item;
        }
        if(empty($dbIndex)) return [];
        foreach ($mainIndexArray as $k => $indexStruct) {
            if (empty($indexStruct["index_name"])) {
                $this->outputError("The 'index_name' configured for 'index' in the '{$tableName}.php' file is illegal, specifically at the {$k}th element of the array.");
                continue;
            }
            if (empty($indexStruct["field"])) {
                $this->outputError("The 'field' configured for 'index' in the '{$tableName}.php' file is illegal, specifically at the {$k}th element of the array.");
                continue;
            }

            // 检查是否需要修改索引名称index_name
            if (isset($indexStruct['new_index_name'])) {
                // 修改索引名
                $this->changeIndexNameSql($tableName,$indexStruct);
            } else {
                // 检查local索引配置内容是否存在DB库里的索引
                $indexName = $indexStruct["index_name"];
                if (isset($dbIndex[$indexName])) {
                    // 存在，分析修改与否
                    $this->checkIndexIsDiff($tableName, $indexStruct, $dbIndex[$indexName]);
                } else {
                    // 不存在，新增该索引到db上
                    $this->generaAddIndexSql($tableName, $indexStruct);

                }
            }
        }
        return $this->__modifyIndexSql;
    }

    private function checkIndexIsDiff(string $tableName, array $mainIndexStruct, array $dbIndexStruct)
    {

        $mainIndexStruct2 = $dbIndexStruct2 = [];
        foreach ($mainIndexStruct as $key => $val) $mainIndexStruct2[strtolower($key)] = $val;
        foreach ($dbIndexStruct as $k=>$item){
            foreach ($item as $key=>$val){
                $dbIndexStruct2[$k][strtolower($key)] = $val;
            }
        }

        // print_r($mainIndexStruct2);
        // print_r($dbIndexStruct2);

        if(count($mainIndexStruct2['field']) != count($dbIndexStruct2)){
            // echo "\n索引值数不一样\n";
            $this->generaModifyIndexSql($tableName, $mainIndexStruct2);
        }else{
            for ($i=0; $i<count($mainIndexStruct2['field']); $i++){
                if($mainIndexStruct2['field'][$i] !== $dbIndexStruct2[$i]['column_name']){
                    // echo "\n索引值内容不一样\n";
                    $this->generaModifyIndexSql($tableName, $mainIndexStruct2);
                    break;
                }
            }
        }

        if ( $dbIndexStruct2[0]['non_unique'] === '0'){
            $dbIndexUnique = true;
        }else{
            $dbIndexUnique = false;
        }

        if($mainIndexStruct2['unique'] !== $dbIndexUnique){
            // echo "\n索引unique不一样\n";
            $this->generaModifyIndexSql($tableName, $mainIndexStruct2);
        }
        if( strtolower($mainIndexStruct2['index_type']) != strtolower($mainIndexStruct2['index_type'])){
            // echo "\n索引index_type不一样\n";
            $this->generaModifyIndexSql($tableName, $mainIndexStruct2);
        }
    }

    /**
     * 修改索引名称
     * @param $tableName
     * @param $indexStruct
     * @return void
     */
    private function changeIndexNameSql($tableName,$indexStruct)
    {
        $this->__modifyIndexSql[$tableName][] = "ALTER TABLE {$tableName} RENAME INDEX {$indexStruct['index_name']} TO {$indexStruct['new_index_name']}";
    }

    private function generaModifyIndexSql($tableName, $mainIndexStruct)
    {
        $this->__modifyIndexSql[$tableName][] = "ALTER TABLE {$tableName} DROP INDEX {$mainIndexStruct['index_name']}";
        $this->generaAddIndexSql($tableName,$mainIndexStruct);
    }

    /**
     * 新增索引sql
     * @param $tableName
     * @param $indexStruct
     * @return void
     */
    private function generaAddIndexSql($tableName, $indexStruct)
    {
        $indexFields = implode(',',$indexStruct['field']);

        [$unique,$fulltext,$indexType] = $this->parseIndexDataType($indexStruct);

        $this->__modifyIndexSql[$tableName][] = "ALTER TABLE {$tableName} ADD {$unique}{$fulltext} INDEX {$indexStruct['index_name']} ({$indexFields}){$indexType}";
    }
}