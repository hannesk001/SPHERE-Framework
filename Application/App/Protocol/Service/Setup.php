<?php

namespace SPHERE\Application\App\Protocol\Service;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SPHERE\Application\App\AppException;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\System\Database\Binding\AbstractSetup;

class Setup extends AbstractSetup
{
    /**
     * @param bool $Simulate
     * @param bool $IsCollation
     *
     * @return string
     * @throws AppException
     */
    public function setupDatabaseSchema($Simulate = true, $IsCollation = false): string
    {
        /**
         * Connection
         */
        $connection = $this->getConnection();
        if (null === $connection) {
            throw new AppException('Connection not set');
        }
        /**
         * Table
         */
        $schema = clone $connection->getSchema();
        $tblRequest = $this->setTableRequest($schema);
        $this->setTableResponse($schema, $tblRequest);

        /**
         * Migration & Protocol
         */
        $connection->addProtocol(__CLASS__);
        if (!$IsCollation) {
            $connection->setMigration($schema, $Simulate);
        } else {
            $connection->setUTF8();
        }
        return $connection->getProtocol($Simulate);
    }

    private function setTableRequest(Schema $Schema): Table
    {
        $table = $this->createTable($Schema, 'tblRequest');
        $this->createServiceKey($table, new TblAccount(''));
        $this->createColumn($table, 'DeviceFactor');
        $this->createColumn($table, 'Route');
        $this->createColumn($table, 'Method');
        $this->createColumn($table, 'Params', self::FIELD_TYPE_TEXT, true);
        $this->createColumn($table, 'Headers', self::FIELD_TYPE_TEXT, true);
        $this->createColumn($table, 'Data', self::FIELD_TYPE_TEXT, true);

        return $table;
    }

    private function setTableResponse(Schema $Schema, Table $tblRequest): void
    {
        $table = $this->createTable($Schema, 'tblResponse');
        $this->createForeignKey($table, $tblRequest);
        $this->createColumn($table, 'Code');
        $this->createColumn($table, 'Content', self::FIELD_TYPE_TEXT, true);
    }
}