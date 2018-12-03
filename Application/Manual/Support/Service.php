<?php
namespace SPHERE\Application\Manual\Support;

use MOC\V\Component\Mail\Component\Bridge\Repository\EdenPhpSmtp;
use MOC\V\Component\Mail\Component\Parameter\Repository\FileParameter;
use MOC\V\Component\Mail\Mail;
use SPHERE\Application\Platform\Gatekeeper\Authorization\Account\Account;
use SPHERE\Common\Frontend\Form\IFormInterface;
use SPHERE\Common\Frontend\Layout\Repository\Listing;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Frontend\Message\Repository\Success;
use SPHERE\Common\Window\Error;
use SPHERE\Common\Window\Redirect;
use SPHERE\System\Extension\Extension;
use SPHERE\System\Support\Type\YouTrackMail;

/**
 * Class Service
 *
 * @package SPHERE\Application\Manual\Support
 */
class Service extends Extension
{

    /**
     * @param IFormInterface|null $Form
     * @param null|array          $Ticket
     * @param                     $Attachment
     *
     * @return IFormInterface|string
     */
    public function createTicket(IFormInterface $Form = null, $Ticket, $Attachment)
    {

        /**
         * Skip to Frontend
         */
        if (null === $Ticket) {
            return $Form;
        }

        $Error = false;

        if (isset( $Ticket['Mail'] ) && empty( $Ticket['Mail'] )) {
            $Form->setError('Ticket[Mail]', 'Bitte geben Sie Ihre Email-Adresse an');
            $Error = true;
        } elseif (isset( $Ticket['Mail'] )) {
            $Ticket['Mail'] = $this->validateMailAddress($Ticket['Mail']);
            if (!$Ticket['Mail']) {
                $Form->setError('Ticket[Mail]', 'Bitte geben Sie eine gültige Email-Adresse an');
                $Error = true;
            }
        }
        if (isset( $Ticket['Subject'] ) && empty( $Ticket['Subject'] )) {
            $Form->setError('Ticket[Subject]', 'Bitte geben Sie einen Betreff an');
            $Error = true;
        }
        if (isset( $Ticket['Body'] ) && empty( $Ticket['Body'] )) {
            $Form->setError('Ticket[Body]', 'Bitte geben Sie einen Inhalt an');
            $Error = true;
        }

        if ($Attachment) {
            try {
                $Upload = $this->getUpload('Attachment', sys_get_temp_dir(), true)
                    ->validateMaxSize('5M')
                    ->doUpload();
            } catch (\Exception $Exception) {

                $Form->setError('Attachment', new Listing(json_decode($Exception->getMessage())));
                $Error = true;
            }
        }

        if (!$Error) {

            try {
                /** @var YouTrackMail $Config */
                $Config = (new \SPHERE\System\Support\Support(new YouTrackMail()))->getSupport();
                /** @var EdenPhpSmtp $Mail */
                $Mail = Mail::getSmtpMail()->connectServer(
                    $Config->getHost(), $Config->getUsername(), $Config->getPassword(), 465, true
                );
                $Mail->setMailSubject(utf8_decode($Ticket['Subject']).' - Account: '.Account::useService()->getAccountBySession()->getId().' ('.$Ticket['Mail'].')');
                if (!empty( $Ticket['CallBackNumber'] )) {
                    $Mail->setMailBody($Ticket['Body'].'<br/>'.
                        'Rückrufnummer: '.$Ticket['CallBackNumber']);
                } else {
                    $Mail->setMailBody($Ticket['Body']);
                }
                $Mail->addRecipientTO($Config->getMail());
                if (isset( $Upload )) {
                    $Mail->addAttachment(new FileParameter($Upload->getLocation().DIRECTORY_SEPARATOR.$Upload->getFilename()));
                }
                $Mail->setFromHeader($Config->getMail());
                $Mail->sendMail();
                $Mail->disconnectServer();
            } catch (\Exception $Exception) {
                return new Danger('Das Ticket konnte leider nicht erstellt werden')
                .new Error( $Exception->getCode(), $Exception->getMessage(), false )
                .new Redirect('/Manual/Support', Redirect::TIMEOUT_ERROR);
            }
            return new Success('Das Ticket wurde erfolgreich erstellt')
            .new Redirect('/Manual/Support', Redirect::TIMEOUT_SUCCESS);
        }

        return $Form;
    }
}
