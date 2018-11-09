<?php
namespace SPHERE\Application\Billing\Accounting\SchoolAccount\Service;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SPHERE\System\Database\Binding\AbstractSetup;

/**
 * Class Setup
 *
 * @package SPHERE\Application\Billing\Accounting\SchoolAccount\Service
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
        $this->setTableSchoolAccount($Schema);

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
     * @return Table $tblSchoolAccount
     *
     * @return Table
     */
    private function setTableSchoolAccount(Schema &$Schema)
    {

        $Table = $this->createTable($Schema, 'tblSchoolAccount');
        $this->createColumn($Table, 'BankName', self::FIELD_TYPE_STRING);
        $this->createColumn($Table, 'IBAN', self::FIELD_TYPE_STRING);
        $this->createColumn($Table, 'BIC', self::FIELD_TYPE_STRING);
        $this->createColumn($Table, 'Owner', self::FIELD_TYPE_STRING);
        $this->createColumn($Table, 'serviceTblCompany', self::FIELD_TYPE_BIGINT, true);
        $this->createColumn($Table, 'serviceTblType', self::FIELD_TYPE_BIGINT, true);

        return $Table;
    }
}
