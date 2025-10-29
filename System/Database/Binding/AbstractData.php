<?php
namespace SPHERE\System\Database\Binding;

use SPHERE\Application\Platform\System\Protocol\Protocol;
use SPHERE\System\Database\Filter\Logic\AbstractLogic;
use SPHERE\System\Database\Fitting\Binding;
use SPHERE\System\Database\Fitting\Cacheable;
use SPHERE\System\Database\Fitting\ColumnHydrator;
use SPHERE\System\Database\Fitting\Element;
use SPHERE\System\Database\Fitting\Manager;

/**
 * Class AbstractData
 *
 * @package SPHERE\System\Database\Binding
 */
abstract class AbstractData extends Cacheable
{

    /** @var null|Binding $Connection */
    private $Connection = null;

    /**
     * @param Binding $Connection
     */
    final public function __construct(Binding $Connection)
    {

        $this->Connection = $Connection;
    }

    /**
     * @return void
     */
    abstract public function setupDatabaseContent();

    /**
     * Internal
     *
     * @param Element $Entity
     * @param AbstractLogic $Logic
     * @return \SPHERE\System\Database\Fitting\Element[]
     * @throws \Exception
     */
    protected function getEntityAllByLogic(Element $Entity, AbstractLogic $Logic)
    {

        $Manager = $this->getEntityManager();
        $Builder = $Manager->getQueryBuilder();

        $Builder->select('E')->from($Entity->getEntityFullName(), 'E');
        $Builder->andWhere($Logic->getExpression());
        $Builder->distinct(true);
        $Query = $Builder->getQuery();
        $Query->useQueryCache(true);

        if( $Entity instanceof AbstractView ) {
            $Result = $Query->getResult();
            $Validation = $Query->getArrayResult();
            if (count($Result) != count($Validation)) {
                throw new \Exception( 'View '.$Entity->getViewClassName().' Element-ID Missmatch.'
                    ."\n".'Multiple View-Elements with same Id-Value, please restructure Setup'
                    ."\n".'Possible Missmatch-Key: '.array_search( $Entity->getId(), $Entity->__toArray() )
                );
            }
            return $Result;
        }

        return $Query->getResult();
    }

    /**
     * @return Binding
     */
    final public function getConnection()
    {

        return $this->Connection;
    }

    /**
     * Internal
     *
     * @param Element       $Entity
     * @param AbstractLogic $Logic
     * @param string        $Column
     *
     * @return array
     */
    protected function getColumnAllByLogic(Element $Entity, AbstractLogic $Logic, $Column = 'Id')
    {

        $Manager = $this->getEntityManager();
        $Builder = $Manager->getQueryBuilder();

        $Builder->select('E.'.$Column)->from($Entity->getEntityFullName(), 'E');
        $Builder->andWhere($Logic->getExpression());
        $Query = $Builder->getQuery();
        $Query->useQueryCache(true);

        return $Query->getResult(ColumnHydrator::HYDRATION_MODE);
    }

    /**
     * @param string  $__METHOD__ Initiator
     * @param Manager $EntityManager
     * @param string  $EntityName
     * @param int     $Id
     *
     * @return false|Element
     * @throws \Exception
     */
    final protected function getForceEntityById($__METHOD__, Manager $EntityManager, $EntityName, $Id)
    {

        $Parameter['Id'] = $Id;

        $Entity = $EntityManager->getEntity($EntityName)->findOneBy($Parameter);
        if (null === $Entity) {
            $Entity = false;
        }
        $this->debugFactory($__METHOD__, $Entity, $Parameter);
        return $Entity;
    }

    /**
     * @param string  $__METHOD__ Initiator
     * @param Manager $EntityManager
     * @param string  $EntityName
     * @param array   $Parameter  Initiator Parameter-Array
     *
     * @return false|Element
     * @throws \Exception
     */
    final protected function getForceEntityBy($__METHOD__, Manager $EntityManager, $EntityName, $Parameter)
    {

        $Entity = $EntityManager->getEntity($EntityName)->findOneBy($Parameter);
        if (null === $Entity) {
            $Entity = false;
        }
        $this->debugFactory($__METHOD__, $Entity, $Parameter);
        return $Entity;
    }

    /**
     * @param string  $__METHOD__ Initiator
     * @param Manager $EntityManager
     * @param string  $EntityName
     * @param array   $Parameter  Initiator Parameter-Array
     *
     * @return false|Element[]
     * @throws \Exception
     */
    final protected function getForceEntityListBy($__METHOD__, Manager $EntityManager, $EntityName, $Parameter, $OrderBy = array( 'EntityCreate' => self::ORDER_DESC ))
    {

        $EntityList = $EntityManager->getEntity($EntityName)->findBy($Parameter, $OrderBy);
        $this->debugFactory($__METHOD__, $EntityList, $Parameter);
        return ( empty( $EntityList ) ? false : $EntityList );
    }

    /**
     * @param string  $__METHOD__ Initiator
     * @param Manager $EntityManager
     * @param string  $EntityName
     *
     * @return false|Element[]
     * @throws \Exception
     */
    final protected function getForceEntityList($__METHOD__, Manager $EntityManager, $EntityName)
    {

        $EntityList = $EntityManager->getEntity($EntityName)->findAll();
        $this->debugFactory($__METHOD__, $EntityList, 'All');
        return ( empty( $EntityList ) ? false : $EntityList );
    }

    /**
     * @param string  $__METHOD__ Initiator
     * @param Manager $EntityManager
     * @param string  $EntityName
     * @param array   $Parameter  Initiator Parameter-Array
     *
     * @return false|Element
     * @throws \Exception
     */
    final protected function getForceEntityCountBy($__METHOD__, Manager $EntityManager, $EntityName, $Parameter)
    {

        $Entity = $EntityManager->getEntity($EntityName)->countBy($Parameter);
        if (null === $Entity) {
            $Entity = false;
        }
        $this->debugFactory($__METHOD__, $Entity, $Parameter);
        return $Entity;
    }

    /**
     * @param bool $useCache true
     *
     * @return Manager
     */
    final protected function getEntityManager( $useCache = true )
    {

        return $this->getConnection()->getEntityManager( $useCache );
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    protected function createEntityListBulk(array $tblEntityList): bool
    {
        $Manager = $this->getEntityManager();

        foreach ($tblEntityList as $tblEntity) {
            $Manager->bulkSaveEntity($tblEntity);
            Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $tblEntity, true);
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();

        return true;
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    protected function updateEntityListBulk(array $tblEntityList): bool
    {
        $Manager = $this->getEntityManager();

        /** @var Element $tblElement */
        foreach ($tblEntityList as $tblElement) {
            $Manager->bulkSaveEntity($tblElement);
            /** @var Element $Entity */
            $Entity = $Manager->getEntityById($tblElement->getEntityShortName(), $tblElement->getId());
            Protocol::useService()->createUpdateEntry($this->getConnection()->getDatabase(), $Entity, $tblElement, true);
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();

        return true;
    }

    /**
     * @param array $tblEntityList
     *
     * @return bool
     */
    protected function deleteEntityListBulk(array $tblEntityList): bool
    {
        $Manager = $this->getConnection()->getEntityManager();

        /** @var Element $tblElement */
        foreach ($tblEntityList as $tblElement) {
            /** @var Element $Entity */
            $Entity = $Manager->getEntityById($tblElement->getEntityShortName(), $tblElement->getId());

            $Manager->bulkKillEntity($Entity);
            Protocol::useService()->createDeleteEntry($this->getConnection()->getDatabase(), $Entity, true);
        }

        $Manager->flushCache();
        Protocol::useService()->flushBulkEntries();

        return true;
    }

    /**
     * @param Element $tblElement
     * @param array|null $findOneBy
     *
     * @return Element
     */
    protected function createEntity(Element $tblElement, ?array $findOneBy = null): Element
    {
        $Manager = $this->getEntityManager();

        $entity = null;
        if ($findOneBy) {
            $entity = $Manager->getEntity($tblElement->getEntityShortName())->findOneBy($findOneBy);
        }

        if (null === $entity) {
            $Manager->saveEntity($tblElement);
            Protocol::useService()->createInsertEntry($this->getConnection()->getDatabase(), $tblElement);

            return $tblElement;
        }

        return $entity;
    }
}
