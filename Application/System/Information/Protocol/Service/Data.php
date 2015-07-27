<?php
namespace SPHERE\Application\System\Information\Protocol\Service;

use SPHERE\Application\System\Gatekeeper\Authorization\Account\Service\Entity\TblAccount;
use SPHERE\Application\System\Gatekeeper\Authorization\Consumer\Service\Entity\TblConsumer;
use SPHERE\Application\System\Information\Protocol\Service\Entity\TblProtocol;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\System\Database\Fitting\Binding;
use SPHERE\System\Database\Fitting\Element;

/**
 * Class Data
 *
 * @package SPHERE\Application\System\Information\Protocol\Service
 */
class Data
{

    /** @var null|Binding $Connection */
    private $Connection = null;

    /**
     * @param Binding $Connection
     */
    function __construct( Binding $Connection )
    {

        $this->Connection = $Connection;
    }

    /**
     * Takes an __PHP_Incomplete_Class and casts it to a stdClass object.
     * All properties will be made public in this step.
     *
     * @since  1.1.0
     *
     * @param  object $object __PHP_Incomplete_Class
     *
     * @return object
     */
    private static function fixObject( $object )
    {

        if (!is_object( $object ) && gettype( $object ) == 'object') {
            // preg_replace_callback handler. Needed to calculate new key-length.
            $fix_key = create_function(
                '$matches',
                'return ":" . strlen( $matches[1] ) . ":\"" . $matches[1] . "\"";'
            );
            // 1. Serialize the object to a string.
            $dump = serialize( $object );
            // 2. Change class-type to 'stdClass'.
            preg_match( '/^O:\d+:"[^"]++"/', $dump, $match );
            $dump = preg_replace( '/^O:\d+:"[^"]++"/', 'O:8:"stdClass"', $dump );
            // 3. Make private and protected properties public.
            $dump = preg_replace_callback( '/:\d+:"\0.*?\0([^"]+)"/', $fix_key, $dump );
            // 4. Unserialize the modified object again.
            $dump = unserialize( $dump );
            $dump->ERROR = new Danger( "Structure mismatch!<br/>".$match[0]."<br/>Please delete this Item" );
            return $dump;
        } else {
            return $object;
        }
    }

    /**
     * @return TblProtocol[]|bool
     */
    public function getProtocolAll()
    {

        $EntityList = $this->Connection->getEntityManager()->getEntity( 'TblProtocol' )->findAll();
        return ( empty( $EntityList ) ? false : $EntityList );
    }

    /**
     * @param string           $DatabaseName
     * @param null|\SPHERE\Application\System\Gatekeeper\Authorization\Account\Service\Entity\TblAccount  $tblAccount
     * @param null|\SPHERE\Application\System\Gatekeeper\Authorization\Consumer\Service\Entity\TblConsumer $tblConsumer
     * @param null|Element     $FromEntity
     * @param null|Element     $ToEntity
     *
     * @return false|TblProtocol
     */
    public function createProtocolEntry(
        $DatabaseName,
        TblAccount $tblAccount = null,
        TblConsumer $tblConsumer = null,
        Element $FromEntity = null,
        Element $ToEntity = null
    ) {

        // Skip if nothing changed
        if (null !== $FromEntity && null !== $ToEntity) {
            $From = $FromEntity->__toArray();
            sort( $From );
            $To = $ToEntity->__toArray();
            sort( $To );
            if ($From === $To) {
                return false;
            }
        }

        $Manager = $this->Connection->getEntityManager();

        $Entity = new TblProtocol();
        $Entity->setProtocolDatabase( $DatabaseName );
        $Entity->setProtocolTimestamp( time() );
        if ($tblAccount) {
            $Entity->setServiceTblAccount( $tblAccount );
            $Entity->setAccountUsername( $tblAccount->getUsername() );
        }
        if ($tblConsumer) {
            $Entity->setServiceTblConsumer( $tblConsumer );
            $Entity->setConsumerName( $tblConsumer->getName() );
            $Entity->setConsumerAcronym( $tblConsumer->getAcronym() );
        }
        $Entity->setEntityFrom( ( $FromEntity ? serialize( $FromEntity ) : null ) );
        $Entity->setEntityTo( ( $ToEntity ? serialize( $ToEntity ) : null ) );

        $Manager->saveEntity( $Entity );

        return $Entity;
    }
}
