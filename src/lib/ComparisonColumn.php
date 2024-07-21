<?php
namespace sheltie\SimpleManageDb\lib;

class ComparisonColumn extends Common
{

    private $__modifyColumnSql = [];

    /**
     * dbMap配置文件与db上的 字段比对
     * @param $tableName
     * @param $mainColumn
     * @param $dbColumn
     * @return array
     */
    public function execComparisonColumns($tableName, $mainColumn, $dbColumn):array
    {
        $dbColumn = array_column($dbColumn, NULL, 'Field');

        foreach ($mainColumn as $k => $columnStruct) {
            if(isset($columnStruct['key']) && $columnStruct['key'] == 'PRI') continue;

            if (empty($columnStruct["field"])) {
                $this->outputError("The 'field' configured for 'column' in the '{$tableName}.php' file is illegal, specifically at the {$k}th element of the array.");
                continue;
            }
            if (empty($columnStruct["type"])) {
                $this->outputError("The 'type' configured for 'column' in the '{$tableName}.php' file is illegal, specifically at the {$k}th element of the array.");
                continue;
            }
            // todo 缺少主键字段、主键索引 的修改比对逻辑。
            // 检查是否需要修改字段名称
            if (isset($columnStruct['new_field'])) {
                // 修改字段名
                $this->changeColumnFieldSql($tableName, $columnStruct);
            } else {
                // 检查字段是否存在于db表里
                $fieldName = $columnStruct["field"];
                if (isset($dbColumn[$fieldName])) {
                    // 存在，分析修改与否
                    $this->checkFieldIsDiff($tableName, $columnStruct, $dbColumn[$fieldName]);
                } else {
                    // 不存在，新增该字段到db上
                    if ($k > 0) $previousField = $mainColumn[$k - 1]["field"];
                    $this->generaAddFieldSql($tableName, $columnStruct, $previousField);

                }
            }
        }

        return $this->__modifyColumnSql;
    }


    /**
     * 检查字段类型是否有变更
     * @param string $tableName
     * @param array $mainFieldStruct
     * @param array $dbFieldStruct
     * @return void
     */
    private function checkFieldIsDiff(string $tableName, array $mainFieldStruct, array $dbFieldStruct): void
    {
        $mainFieldStruct2 = $dbFieldStruct2 = [];
        foreach ($mainFieldStruct as $key => $val) $mainFieldStruct2[strtolower($key)] = $val;
        foreach ($dbFieldStruct as $key => $val) $dbFieldStruct2[strtolower($key)] = $val;

        if(substr($mainFieldStruct2['type'],0,4) == 'enum') $mainFieldStruct2['type'] = $this->restoreTypeOfEnum($mainFieldStruct2['type']);

        if($mainFieldStruct2['type'] !== $dbFieldStruct2['type']){
            $this->generaModifyFieldSql($tableName, $mainFieldStruct2);
        }else if($mainFieldStruct2['comment'] !== $dbFieldStruct2['comment']){
            $this->generaModifyFieldSql($tableName, $mainFieldStruct2);
        }else if (strtolower($mainFieldStruct2['null']) !== strtolower($dbFieldStruct2['null'])){
            $this->generaModifyFieldSql($tableName, $mainFieldStruct2);
        }else if($mainFieldStruct2['default'] !== $dbFieldStruct2['default']){
            $this->generaModifyFieldSql($tableName, $mainFieldStruct2);
        }

    }

    // 字段数据类型修改sql
    private function generaModifyFieldSql(string $tableName, array $mainFieldStruct): void
    {
        [$isNull, $default, $comment] = $this->parseFieldDataType($mainFieldStruct);
        $this->__modifyColumnSql[$tableName][] = "ALTER TABLE `{$tableName}` MODIFY {$mainFieldStruct['field']} {$mainFieldStruct['type']} {$isNull} {$default} {$comment};";
    }

    // 字段名称修改sql
    private function changeColumnFieldSql(string $tableName, array $mainFieldStruct): void
    {
        [$isNull, $default, $comment] = $this->parseFieldDataType($mainFieldStruct);
        $this->__modifyColumnSql[$tableName][] = "ALTER TABLE `{$tableName}` CHANGE {$mainFieldStruct['field']} {$mainFieldStruct['new_field']} {$mainFieldStruct['type']} {$isNull} {$default} {$comment};";
    }


    /**
     * 新增一条新增字段的sql语句
     * @param $tableName string 数据表名
     * @param $fieldInfo array 新增字段的信息，也就是dbMap文件里的配置内容
     * @param $previousField string 前一个字段名
     * @return void
     */
    private function generaAddFieldSql(string $tableName, array $fieldInfo, string $previousField = "")
    {

        [$isNull, $default, $comment] = $this->parseFieldDataType($fieldInfo);

        if (empty($previousField)) {
            $after = "";
        } else {
            $after = "AFTER `{$previousField}`";
        }

        $this->__modifyColumnSql[$tableName][] = "ALTER TABLE `{$tableName}` ADD COLUMN {$fieldInfo['field']} {$fieldInfo['type']} {$isNull} {$default} {$comment} {$after};";
    }


    private function restoreTypeOfEnum($mainType):string
    {
        preg_match('#\((.*?)\)#', $mainType, $match);
        $arr = explode(',', $match[1]);
        $str = "enum(";
        foreach ($arr as $v){
            $str .= "'{$v}',";
        }
        return rtrim($str,',').')';
    }
}