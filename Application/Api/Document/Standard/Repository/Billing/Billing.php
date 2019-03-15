<?php
/**
 * Created by PhpStorm.
 * User: Kauschke
 * Date: 13.03.2019
 * Time: 13:05
 */

namespace SPHERE\Application\Api\Document\Standard\Repository\Billing;

use SPHERE\Application\Billing\Inventory\Document\Service\Entity\TblDocument;
use SPHERE\Application\Billing\Inventory\Item\Service\Entity\TblItem;
use SPHERE\Application\Contact\Address\Address;
use SPHERE\Application\Document\Generator\Repository\Document;
use SPHERE\Application\Document\Generator\Repository\Element;
use SPHERE\Application\Document\Generator\Repository\Frame;
use SPHERE\Application\Document\Generator\Repository\Page;
use SPHERE\Application\Document\Generator\Repository\Section;
use SPHERE\Application\Document\Generator\Repository\Slice;
use SPHERE\Application\People\Person\Service\Entity\TblPerson;
use SPHERE\Application\Setting\Consumer\Consumer;

/**
 * Class Billing
 *
 * @package SPHERE\Application\Api\Document\Standard\Repository\Billing
 */
class Billing
{

    /** @var null|Frame $Document */
    private $Document = null;

    /** @var null|TblItem $tblItem */
    private $tblItem = null;

    /** @var null|TblDocument $tblDocument */
    private $tblDocument = null;

    /** @var null|array $Data  */
    private $Data = null;

    const TEXT_SIZE = '14px';

    public function __construct(TblItem $tblItem, TblDocument $tblDocument, $Data)
    {
        $this->tblItem = $tblItem;
        $this->tblDocument = $tblDocument;
        $this->Data = $Data;
    }

    /**
     * @param TblPerson $tblPersonDebtor
     * @param TblPerson $tblPersonCauser
     * @param $TotalPrice
     *
     * @return \MOC\V\Component\Template\Component\IBridgeInterface
     */
    public function createSingleDocument(
        TblPerson $tblPersonDebtor,
        TblPerson $tblPersonCauser,
        $TotalPrice
    ) {
        $pageList[] = $this->buildPage($tblPersonDebtor, $tblPersonCauser, $TotalPrice);

        $this->Document = $this->buildDocument($pageList);

        return $this->Document->getTemplate();
    }

    /**
     * @param array $pageList
     *
     * @return Frame
     */
    private function buildDocument($pageList = array())
    {
        $document = new Document();

        foreach ($pageList as $subjectPages) {
            if (is_array($subjectPages)) {
                foreach ($subjectPages as $page) {
                    $document->addPage($page);
                }
            } else {
                $document->addPage($subjectPages);
            }
        }

        $InjectStyle = 'body { margin-left: 1.0cm !important; margin-right: 1.0cm !important; }';

        return (new Frame($InjectStyle))->addDocument($document);
    }

    /**
     * @param $Text
     * @param $ItemName
     * @param $Year
     * @param $TotalPrice
     * @param $DebtorSalutation
     * @param $DebtorFirstName
     * @param $DebtorLastName
     * @param $CauserSalutation
     * @param $CauserFirstName
     * @param $CauserLastName
     * @param $From
     * @param $To
     * @param $Date
     * @param $Location
     * @param $ConsumerName
     * @param $ConsumerExtendedName
     * @param $ConsumerAddress
     *
     * @return string
     */
    private function setPlaceholders(
        $Text,
        $ItemName,
        $Year,
        $TotalPrice,
        $DebtorSalutation,
        $DebtorFirstName,
        $DebtorLastName,
        $CauserSalutation,
        $CauserFirstName,
        $CauserLastName,
        $From,
        $To,
        $Date,
        $Location,
        $ConsumerName,
        $ConsumerExtendedName,
        $ConsumerAddress
    ) {
        $Text = str_replace('[Beitragsart]', $ItemName, $Text);
        $Text = str_replace('[Beitragsjahr]', $Year, $Text);
        $Text = str_replace('[Beitragssumme]', $TotalPrice, $Text);
        $Text = str_replace('[Beitragszahler Anrede]', $DebtorSalutation, $Text);
        $Text = str_replace('[Beitragszahler Vorname]', $DebtorFirstName, $Text);
        $Text = str_replace('[Beitragszahler Nachname]', $DebtorLastName, $Text);
        $Text = str_replace('[Beitragsverursacher Anrede]', $CauserSalutation, $Text);
        $Text = str_replace('[Beitragsverursacher Vorname]', $CauserFirstName, $Text);
        $Text = str_replace('[Beitragsverursacher Nachname]', $CauserLastName, $Text);
        $Text = str_replace('[Zeitraum von]', $From, $Text);
        $Text = str_replace('[Zeitraum bis]', $To, $Text);
        $Text = str_replace('[Datum]', $Date, $Text);
        $Text = str_replace('[Ort]', $Location, $Text);
        $Text = str_replace('[Trägername]', $ConsumerName, $Text);
        $Text = str_replace('[Trägerzusatz]', $ConsumerExtendedName, $Text);
        $Text = str_replace('[Trägeradresse]', $ConsumerAddress, $Text);

        return $Text;
    }

    /**
     * @param TblPerson $tblPersonDebtor
     * @param TblPerson $tblPersonCauser
     * @param $TotalPrice
     *
     * @return Page
     */
    private function buildPage(
        TblPerson $tblPersonDebtor,
        TblPerson $tblPersonCauser,
        $TotalPrice
    ) {
        $Data = $this->Data;
        $ConsumerName = $Data['ConsumerName'];
        $ConsumerExtendedName = $Data['ConsumerExtendedName'];
        $ConsumerAddress = $Data['ConsumerAddress'];
        $Location = $Data['Location'];
        $Date = $Data['Date'];
        $Subject = $Data['Subject'];
        $Content = $Data['Content'];
        $Year = $Data['Year'];
        $From = $Data['From'];
        $To = $Data['To'];

        $ItemName = $this->tblItem->getName();
        $DebtorSalutation = $tblPersonDebtor->getSalutation();
        $DebtorFirstName = $tblPersonDebtor->getFirstSecondName();
        $DebtorLastName = $tblPersonDebtor->getLastName();
        $CauserSalutation = $tblPersonCauser->getSalutation();
        $CauserFirstName = $tblPersonCauser->getFirstSecondName();
        $CauserLastName = $tblPersonCauser->getLastName();

        $Subject = $this->setPlaceholders(
            $Subject,
            $ItemName,
            $Year,
            $TotalPrice,
            $DebtorSalutation,
            $DebtorFirstName,
            $DebtorLastName,
            $CauserSalutation,
            $CauserFirstName,
            $CauserLastName,
            $From,
            $To,
            $Date,
            $Location,
            $ConsumerName,
            $ConsumerExtendedName,
            $ConsumerAddress
        );

        $Content = $this->setPlaceholders(
            $Content,
            $ItemName,
            $Year,
            $TotalPrice,
            $DebtorSalutation,
            $DebtorFirstName,
            $DebtorLastName,
            $CauserSalutation,
            $CauserFirstName,
            $CauserLastName,
            $From,
            $To,
            $Date,
            $Location,
            $ConsumerName,
            $ConsumerExtendedName,
            $ConsumerAddress
        );

        return (new Page())
            // todo exakte Höhe bestimmen
            ->addSlice($this->getHeaderSlice('200px'))
            ->addSlice($this->getAddressSlice($ConsumerName, $ConsumerExtendedName, $ConsumerAddress, $tblPersonDebtor))
            ->addSlice((new Slice())
                ->addElement((new Element())
                    ->setContent($Location . ', ' . $Date)
                    ->styleTextSize(self::TEXT_SIZE)
                    ->styleAlignRight()
                    ->styleMarginTop('50px')
                )
            )
            ->addSlice((new Slice())
                ->addElement((new Element())
                    ->setContent($Subject)
                    ->styleTextSize('18px')
                    ->styleTextBold()
                    ->styleMarginTop('30px')
                )
            )
            ->addSlice((new Slice())
                ->addElement((new Element())
                    ->setContent(nl2br($Content))
                    ->styleTextSize(self::TEXT_SIZE)
                    ->styleAlignJustify()
                    ->styleMarginTop('25px')
                )
            );
    }

    /**
     * @param string $Height
     *
     * @return Slice
     */
    private function getHeaderSlice($Height = '200px')
    {
        if (($tblSetting = Consumer::useService()->getSetting(
            'Api', 'Document', 'Standard', 'Billing_PictureAddress'))
        ) {
            $pictureAddress = (string)$tblSetting->getValue();
        } else {
            $pictureAddress = '';
        }

        if (($tblSetting = Consumer::useService()->getSetting(
            'Api', 'Document', 'Standard', 'Billing_PictureHeight'))
        ) {
            $pictureHeight = (string)$tblSetting->getValue();
        } else {
            $pictureHeight = '';
        }

        if ($pictureAddress != '') {
            $pictureHeight = $pictureHeight ? $pictureHeight : '90px';
            $element = (new Element\Image($pictureAddress, 'auto', $pictureHeight));
        } else {
            $element = (new Element())
                ->setContent('&nbsp;');
        }

        return (new Slice())
            ->addElement($element)
            ->styleAlignRight()
            ->styleHeight($Height);
    }

    /**
     * @param $ConsumerName
     * @param $ConsumerExtendedName
     * @param $ConsumerAddress
     * @param TblPerson $tblPersonDebtor
     * @param string $TextSize
     *
     * @return Slice
     */
    private function getAddressSlice(
        $ConsumerName,
        $ConsumerExtendedName,
        $ConsumerAddress,
        TblPerson $tblPersonDebtor,
        $TextSize = '8px'
    ) {
        $address1 = '&nbsp;';
        $address2 = '&nbsp;';
        if(($tblAddress = Address::useService()->getInvoiceAddressByPerson($tblPersonDebtor))) {
            $address1 = $tblAddress->getStreetName() . ' ' . $tblAddress->getStreetNumber();
            if (($tblCity = $tblAddress->getTblCity())) {
                $address2 = $tblCity->getCode() . ' ' . $tblCity->getDisplayName();
            }
        }

        return (new Slice())
            ->addElement((new Element())
                ->setContent($ConsumerName . ($ConsumerExtendedName ?  ' ' . $ConsumerExtendedName : ''))
                ->styleTextSize($TextSize)
            )
            ->addSection((new Section())
                ->addElementColumn((new Element())
                    ->setContent($ConsumerAddress)
                    ->styleTextSize($TextSize)
                    ->styleBorderBottom()
                , '25%')
                ->addElementColumn((new Element()))
            )
            ->addElement((new Element())
                ->setContent($tblPersonDebtor->getSalutation())
                ->styleTextSize(self::TEXT_SIZE)
                ->styleMarginTop('14px')
            )
            ->addElement((new Element())
                ->setContent($tblPersonDebtor->getFirstSecondName() . ' ' . $tblPersonDebtor->getLastName())
                ->styleTextSize(self::TEXT_SIZE)
            )
            ->addElement((new Element())
                ->setContent($address1)
                ->styleTextSize(self::TEXT_SIZE)
            )
            ->addElement((new Element())
                ->setContent($address2)
                ->styleTextSize(self::TEXT_SIZE)
            )
        ;
    }
}