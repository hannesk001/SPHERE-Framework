<?php

namespace SPHERE\Application\Api\Education\Competence;

use SPHERE\Application\Api\ApiTrait;
use SPHERE\Application\Api\Dispatcher;
use SPHERE\Application\Education\Competence\SkillRate\SkillRate;
use SPHERE\Application\IApiInterface;
use SPHERE\Common\Frontend\Ajax\Emitter\ServerEmitter;
use SPHERE\Common\Frontend\Ajax\Pipeline;
use SPHERE\Common\Frontend\Ajax\Receiver\BlockReceiver;
use SPHERE\System\Extension\Extension;

class ApiSkillCertificate extends Extension implements IApiInterface
{
    use ApiTrait;

    /**
     * @param $Method
     *
     * @return string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function exportApi($Method = ''): string
    {
        $Dispatcher = new Dispatcher(__CLASS__);

        // DivisionCourse
//        $Dispatcher->registerMethod('loadEditDivisionCourseContent');
        $Dispatcher->registerMethod('loadEditDivisionCourseSkillRateContent');
//        $Dispatcher->registerMethod('saveEditDivisionCourseSkillRate');

        return $Dispatcher->callMethod($Method);
    }

    /**
     * @param string $Content
     * @param string $Identifier
     *
     * @return BlockReceiver
     */
    public static function receiverBlock(string $Content = '', string $Identifier = ''): BlockReceiver
    {
        return (new BlockReceiver($Content))->setIdentifier($Identifier);
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     *
     * @return Pipeline
     */
    public static function pipelineLoadEditDivisionCourseSkillRateContent(
        $DivisionCourseId, $PrepareCertificateId
    ): Pipeline {
        $Pipeline = new Pipeline(false);
        $ModalEmitter = new ServerEmitter(self::receiverBlock('', 'SkillRateContent'), self::getEndpoint());
        $ModalEmitter->setGetPayload(array(
            self::API_TARGET => 'loadEditDivisionCourseSkillRateContent',
        ));
        $ModalEmitter->setPostPayload(array(
            'DivisionCourseId' => $DivisionCourseId,
            'PrepareCertificateId' => $PrepareCertificateId,
        ));
        $ModalEmitter->setLoadingMessage("Daten werden geladen");
        $Pipeline->appendEmitter($ModalEmitter);

        return $Pipeline;
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     * @param null $Data
     *
     * @return string
     *
     * @noinspection PhpUnused
     */
    public function loadEditDivisionCourseSkillRateContent($DivisionCourseId, $PrepareCertificateId, $Data = null): string
    {
        return SkillRate::useFrontend()->loadEditPrepareInterdisciplinaryContent(
            $DivisionCourseId, $PrepareCertificateId, $Data);
    }
}