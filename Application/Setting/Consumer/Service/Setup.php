<?php
namespace SPHERE\Application\Setting\Consumer\Service;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SPHERE\System\Database\Binding\AbstractSetup;

/**
 * Class Setup
 *
 * @package SPHERE\Application\Setting\Consumer\Service
 */
class Setup extends AbstractSetup
{

    /**
     * @param bool $Simulate
     *
     * @return string
     */
    public function setupDatabaseSchema($Simulate = true)
    {

        /**
         * Table
         */
        $Schema = clone $this->getConnection()->getSchema();
        $this->setTableSetting($Schema);

        /**
         * Migration & Protocol
         */
        $this->getConnection()->addProtocol(__CLASS__);
        $this->getConnection()->setMigration($Schema, $Simulate);
        return $this->getConnection()->getProtocol($Simulate);
    }

    /**
     * @param Schema $Schema
     *
     * @return Table
     */
    private function setTableSetting(Schema &$Schema)
    {

        $table = $this->createTable($Schema, 'tblSetting');
        $this->createColumn($table, 'Cluster', self::FIELD_TYPE_STRING);
        $this->createColumn($table, 'Application', self::FIELD_TYPE_STRING);
        $this->createColumn($table, 'Module', self::FIELD_TYPE_STRING, true);
        $this->createColumn($table, 'Identifier', self::FIELD_TYPE_STRING);
        $this->createColumn($table, 'Type', self::FIELD_TYPE_STRING);
        $this->createColumn($table, 'Value', self::FIELD_TYPE_TEXT);

        $this->createIndex($table, array('Cluster', 'Application', 'Module', 'Identifier'));

        return $table;
    }
}