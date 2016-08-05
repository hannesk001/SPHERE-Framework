<?php
namespace SPHERE\Application\Billing\Bookkeeping\Invoice\Service\Entity;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use SPHERE\Application\Billing\Bookkeeping\Invoice\Invoice;
use SPHERE\System\Database\Fitting\Element;

/**
 * @Entity
 * @Table(name="tblOrderItem")
 * @Cache(usage="READ_ONLY")
 */
class TblOrderItem extends Element
{

    const ATTR_TBL_ORDER = 'tblOrder';

    /**
     * @Column(type="string")
     */
    protected $CommodityDescription;
    /**
     * @Column(type="string")
     */
    protected $CommodityName;
    /**
     * @Column(type="string")
     */
    protected $ItemDescription;
    /**
     * @Column(type="string")
     */
    protected $ItemName;
    /**
     * @Column(type="decimal", precision=14, scale=4)
     */
    protected $ItemPrice;
    /**
     * @Column(type="decimal", precision=14, scale=4)
     */
    protected $ItemQuantity;
    /**
     * @Column(type="bigint")
     */
    protected $tblOrder;

    /**
     * @return string
     */
    public function getTotalPriceString()
    {

        $result = 0.00;
        if ($this->ItemPrice > 0 && $this->ItemQuantity > 0) {
            $result = sprintf("%01.4f", $this->ItemPrice * $this->ItemQuantity);
        }

        return str_replace('.', ',', $result)." €";
    }

    /**
     * @return string
     */
    public function getPriceString()
    {

        $result = sprintf("%01.4f", $this->ItemPrice);
        return str_replace('.', ',', $result)." €";
    }

    /**
     * @return string
     */
    public function getCommodityDescription()
    {

        return $this->CommodityDescription;
    }

    /**
     * @param string $CommodityDescription
     */
    public function setCommodityDescription($CommodityDescription)
    {

        $this->CommodityDescription = $CommodityDescription;
    }

    /**
     * @return string
     */
    public function getCommodityName()
    {

        return $this->CommodityName;
    }

    /**
     * @param string $CommodityName
     */
    public function setCommodityName($CommodityName)
    {

        $this->CommodityName = $CommodityName;
    }

    /**
     * @return string
     */
    public function getItemDescription()
    {

        return $this->ItemDescription;
    }

    /**
     * @param string $ItemDescription
     */
    public function setItemDescription($ItemDescription)
    {

        $this->ItemDescription = $ItemDescription;
    }

    /**
     * @return string
     */
    public function getItemName()
    {

        return $this->ItemName;
    }

    /**
     * @param string $ItemName
     */
    public function setItemName($ItemName)
    {

        $this->ItemName = $ItemName;
    }

    /**
     * @return (type="decimal", precision=14, scale=4)
     */
    public function getItemPrice()
    {

        return $this->ItemPrice;
    }

    /**
     * @param (type="decimal", precision=14, scale=4) $ItemPrice
     */
    public function setItemPrice($ItemPrice)
    {

        $this->ItemPrice = $ItemPrice;
    }

    /**
     * @return (type="decimal", precision=14, scale=4)
     */
    public function getItemQuantity()
    {

        return $this->ItemQuantity;
    }

    /**
     * @param (type="decimal", precision=14, scale=4) $ItemQuantity
     */
    public function setItemQuantity($ItemQuantity)
    {

        $this->ItemQuantity = $ItemQuantity;
    }

    /**
     * @return bool|TblOrder
     */
    public function getTblOrder()
    {

        if (null === $this->tblOrder) {
            return false;
        } else {
            return Invoice::useService()->getOrderById($this->tblOrder);
        }
    }

    /**
     * @param null|TblOrder $tblOrder
     */
    public function setTblOrder(TblOrder $tblOrder = null)
    {

        $this->tblOrder = ( null === $tblOrder ? null : $tblOrder->getId() );
    }
}