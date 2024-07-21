<?php

namespace sheltie\SimpleManageDb;

use PDO;
use PDOException;
use RuntimeException;
use sheltie\SimpleManageDb\lib\Common;

final class SimpleManageDb extends Common
{
    private $_connect;

    private static $_instance;

    public static function getInstance(PDO $PDO): self
    {
        // 如果尚未创建实例，则创建一个新实例
        if (!isset(self::$_instance)) {
            self::$_instance = new self($PDO);
        }
        // 返回单例实例
        return self::$_instance;
    }

    private function __construct(PDO $PDO)
    {
        $this->_connect = $PDO;
    }

    public function run(string $mode,string $specifyDir = "",bool $force = false)
    {

        switch ($mode){
            case 'generate':
                $this->generateDbMapping();
                break;
            case 'update':
                $this->updateTableStruct($specifyDir);
                break;
            default:
                exit("node：need effective operational instructions");
                break;
        }
        $this->_connect = NULL;
        exit("\nrun complete\n");

    }

    private function generateDbMapping($force = false)
    {
        if ($this->_connect === NULL) throw new PDOException("miss connect");
        $generateObj = new GenerateDbMap($this->__dbMapDirectoryName);
        $generateObj->generate($this->_connect,$force);
    }

    private function updateTableStruct($specifyDir = "",$force = false)
    {
        if ($this->_connect === NULL) throw new PDOException("miss connect");
        $updateStructObj = new UpdateTableStruct($this->__dbMapDirectoryName);
        $updateStructObj->update($this->_connect,$specifyDir,$force);
    }


}