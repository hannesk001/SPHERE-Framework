<?php
namespace SPHERE\Common\Frontend\Layout\Structure;

use MOC\V\Component\Template\Component\IBridgeInterface;
use SPHERE\Common\Frontend\ITemplateInterface;
use SPHERE\System\Authenticator\Authenticator;
use SPHERE\System\Authenticator\Type\Get;
use SPHERE\System\Extension\Extension;

class LayoutTab extends Extension implements ITemplateInterface
{

    /** @var IBridgeInterface $Template */
    private $Template = null;

    /**
     * @param string $TabName
     * @param int    $TabParameter
     */
    public function __construct( $TabName, $TabParameter )
    {

        $this->Template = $this->getTemplate( __DIR__.'/LayoutTab.twig' );

        $this->Template->setVariable( 'TabName', $TabName );
        $this->Template->setVariable( 'TabParameter', '?'.http_build_query( ( new Authenticator( new Get() ) )
                ->getAuthenticator()->createSignature(
                    array( 'TabActive' => $TabParameter ), $this->getRequest()->getPathInfo()
                ) )
        );
        $this->Template->setVariable( 'TabRoute', $this->getRequest()->getPathInfo() );

        $Global = $this->getGlobal();
        if (isset( $Global->GET['TabActive'] )) {
            if ($Global->GET['TabActive'] == $TabParameter) {
                $this->Template->setVariable( 'TabActive', true );
            } else {
                $this->Template->setVariable( 'TabActive', false );
            }
        }
    }

    /**
     * @return LayoutTab
     */
    public function setActive()
    {

        $this->Template->setVariable( 'TabActive', true );
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {

        return $this->getContent();
    }

    /**
     * @return string
     */
    public function getContent()
    {

        return $this->Template->getContent();
    }
}
