<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 27.04.2016
 * Time: 14:51
 */

namespace SPHERE\Application\Reporting\SerialLetter\Service;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use SPHERE\System\Database\Binding\AbstractSetup;

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
        $tblSerialLetter = $this->setTableSerialLetter($Schema);
        $this->setTableAddressPerson($Schema, $tblSerialLetter);

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
    private function setTableSerialLetter(Schema &$Schema)
    {

        $Table = $this->getConnection()->createTable($Schema, 'tblSerialLetter');
        if (!$this->getConnection()->hasColumn('tblSerialLetter', 'Name')) {
            $Table->addColumn('Name', 'string');
        }
        if (!$this->getConnection()->hasColumn('tblSerialLetter', 'Description')) {
            $Table->addColumn('Description', 'string');
        }
        if (!$this->getConnection()->hasColumn('tblSerialLetter', 'serviceTblGroup')) {
            $Table->addColumn('serviceTblGroup', 'bigint', array('notnull' => false));
        }

        return $Table;
    }

    /**
     * @param Schema $Schema
     * @param Table  $tblSerialLetter
     *
     * @return Table
     */
    private function setTableAddressPerson(Schema &$Schema, Table $tblSerialLetter)
    {

        $Table = $this->getConnection()->createTable($Schema, 'tblAddressPerson');
        if (!$this->getConnection()->hasColumn('tblAddressPerson', 'serviceTblPerson')) {
            $Table->addColumn('serviceTblPerson', 'bigint', array('notnull' => false));
        }
        if (!$this->getConnection()->hasColumn('tblAddressPerson', 'serviceTblToPerson')) {
            $Table->addColumn('serviceTblToPerson', 'bigint', array('notnull' => false));
        }
        if (!$this->getConnection()->hasColumn('tblAddressPerson', 'serviceTblPersonToAddress')) {
            $Table->addColumn('serviceTblPersonToAddress', 'bigint', array('notnull' => false));
        }
        if (!$this->getConnection()->hasColumn('tblAddressPerson', 'serviceTblSalutation')) {
            $Table->addColumn('serviceTblSalutation', 'bigint', array('notnull' => false));
        }

        $this->getConnection()->addForeignKey($Table, $tblSerialLetter, true);

        return $Table;
    }
}