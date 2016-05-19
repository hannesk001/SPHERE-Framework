<?php
namespace SPHERE\System\Database\Fitting;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use SPHERE\System\Extension\Extension;

/**
 * Class Element
 *
 * - Id (bigint)
 * - EntityCreate (datetime)
 * - EntityUpdate (datetime)
 *
 * @package SPHERE\System\Database\Fitting
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class Element extends Extension
{

    const ENTITY_ID = 'Id';
    const ENTITY_REMOVE = 'EntityRemove';
    const ENTITY_UPDATE = 'EntityUpdate';
    const ENTITY_CREATE = 'EntityCreate';

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="bigint")
     */
    protected $Id;
    /**
     * @Column(type="datetime")
     */
    protected $EntityCreate;
    /**
     * @Column(type="datetime")
     */
    protected $EntityUpdate;
    /**
     * @Column(type="datetime")
     */
    protected $EntityRemove;

    /**
     * @PrePersist
     */
    final public function lifecycleCreate()
    {

        if (empty( $this->EntityCreate )) {
            $this->EntityCreate = new \DateTime("now");
        }
    }

    /**
     * @PreUpdate
     */
    final public function lifecycleUpdate()
    {

        $this->EntityUpdate = new \DateTime("now");
    }

    /**
     * @throws \Exception
     */
    final public function __toArray()
    {

        $Array = get_object_vars($this);
        foreach ($Array as $Key => $Value) {
            if ($Value instanceof \DateTime) {
                $Array[$Key] = $Value->format('d.m.Y H:i:s');
            }
        }

        return $Array;
    }

    /**
     * @return \DateTime
     */
    public function getEntityCreate()
    {

        return $this->EntityCreate;
    }

    /**
     * @return null|\DateTime
     */
    public function getEntityUpdate()
    {

        return $this->EntityUpdate;
    }

    /**
     * @param Element $Required
     *
     * @return \DateTime|null
     */
    public function getEntityRemove(Element $Required = null)
    {

        // Default
        if ($this->EntityRemove) {
            return $this->EntityRemove;
        }

        // Joined Element Overload
        if (null === $Required) {
            // No Overload
            return $this->EntityRemove;
        } else {
            $Method = 'getService'.(new \ReflectionClass($Required))->getShortName();
            if (method_exists($this, $Method)) {
                /** @var bool|Element $tblJoin */
                $tblJoin = $this->$Method();
                if ($tblJoin) {
                    // Exists
                    if ($tblJoin->getEntityRemove()) {
                        return $tblJoin->getEntityRemove();
                    } else {
                        return $this->EntityRemove;
                    }
                } else {
                    // Missing Element
                    return new \DateTime();
                }
            } else {
                // Missing Method
                return $this->EntityRemove;
            }
        }
    }

    /**
     * @param bool $Toggle
     *
     * @return Element
     */
    public function setEntityRemove($Toggle = true)
    {

        if ($Toggle) {
            $this->EntityRemove = new \DateTime("now");
        } else {
            $this->EntityRemove = null;
        }
        return $this;
    }

    /**
     * Return Object-Id
     * Fix: Doctrine - Entity can't be converted to 'string' while getting Entity-Id
     *
     * @return string
     */
    final public function __toString()
    {

        return strval($this->getId());
    }

    /**
     * @return integer
     */
    final public function getId()
    {

        return $this->Id;
    }

    /**
     * @param integer $Id
     */
    final public function setId($Id)
    {

        $this->Id = $Id;
    }

    /**
     * @return string
     */
    final public function getEntityShortName()
    {

        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return string
     */
    final public function getEntityFullName()
    {

        return (new \ReflectionClass($this))->getName();
    }
}
