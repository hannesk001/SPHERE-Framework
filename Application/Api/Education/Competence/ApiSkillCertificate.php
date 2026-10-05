<?php

namespace SPHERE\Application\Api\Education\Competence;

use SPHERE\Application\Api\ApiTrait;
use SPHERE\Application\Api\Dispatcher;
use SPHERE\Application\Education\Certificate\Prepare\Prepare;
use SPHERE\Application\Education\Competence\SkillRate\SkillRate;
use SPHERE\Application\Education\Lesson\DivisionCourse\DivisionCourse;
use SPHERE\Application\IApiInterface;
use SPHERE\Common\Frontend\Ajax\Emitter\ServerEmitter;
use SPHERE\Common\Frontend\Ajax\Pipeline;
use SPHERE\Common\Frontend\Ajax\Receiver\BlockReceiver;
use SPHERE\Common\Frontend\Icon\Repository\Exclamation;
use SPHERE\Common\Frontend\Message\Repository\Danger;
use SPHERE\Common\Window\Redirect;
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
        $Dispatcher->registerMethod('loadEditDivisionCourseSkillRateContent');
        $Dispatcher->registerMethod('saveEditDivisionCourseSkillRate');

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
     * @param $Route
     *
     * @return Pipeline
     */
    public static function pipelineLoadEditDivisionCourseSkillRateContent(
        $DivisionCourseId, $PrepareCertificateId, $Route
    ): Pipeline {
        $Pipeline = new Pipeline(false);
        $ModalEmitter = new ServerEmitter(self::receiverBlock('', 'SkillRateContent'), self::getEndpoint());
        $ModalEmitter->setGetPayload(array(
            self::API_TARGET => 'loadEditDivisionCourseSkillRateContent',
        ));
        $ModalEmitter->setPostPayload(array(
            'DivisionCourseId' => $DivisionCourseId,
            'PrepareCertificateId' => $PrepareCertificateId,
            'Route' => $Route
        ));
        $ModalEmitter->setLoadingMessage("Daten werden geladen");
        $Pipeline->appendEmitter($ModalEmitter);

        return $Pipeline;
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     * @param $Route
     * @param null $Data
     *
     * @return string
     *
     * @noinspection PhpUnused
     */
    public function loadEditDivisionCourseSkillRateContent($DivisionCourseId, $PrepareCertificateId, $Route, $Data = null): string
    {
        return SkillRate::useFrontend()->loadEditPrepareInterdisciplinaryContent(
            $DivisionCourseId, $PrepareCertificateId, $Route, $Data);
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     * @param $Route
     * @param $NextSkillId
     *
     * @return Pipeline
     */
    public static function pipelineSaveEditDivisionCourseSkillRate(
        $DivisionCourseId, $PrepareCertificateId, $Route, $NextSkillId
    ): Pipeline {
        $Pipeline = new Pipeline(false);
        $ModalEmitter = new ServerEmitter(self::receiverBlock('', 'SkillRateContent'), self::getEndpoint());
        $ModalEmitter->setGetPayload(array(
            self::API_TARGET => 'saveEditDivisionCourseSkillRate',
        ));
        $ModalEmitter->setPostPayload(array(
            'DivisionCourseId' => $DivisionCourseId,
            'PrepareCertificateId' => $PrepareCertificateId,
            'Route' => $Route,
            'NextSkillId' => $NextSkillId,
        ));
        $ModalEmitter->setLoadingMessage("Daten werden geladen");
        $Pipeline->appendEmitter($ModalEmitter);

        return $Pipeline;
    }

    /**
     * @param $DivisionCourseId
     * @param $PrepareCertificateId
     * @param $Route
     * @param $NextSkillId
     * @param null $Data
     *
     * @return string
     *
     * @noinspection PhpUnused
     */
    public function saveEditDivisionCourseSkillRate($DivisionCourseId, $PrepareCertificateId, $Route, $NextSkillId, $Data = null): string
    {
        if (!($tblDivisionCourse = DivisionCourse::useService()->getDivisionCourseById($DivisionCourseId))) {
            return new Danger('Kurs nicht gefunden.', new Exclamation());
        }

        if (!($tblPrepareCertificate = Prepare::useService()->getPrepareById($PrepareCertificateId))) {
            return new Danger('Zeugnisauftrag nicht gefunden.', new Exclamation());
        }

        return SkillRate::useService()->createDivisionCourseCertificateSkillRateList(
            $tblDivisionCourse, $tblPrepareCertificate, $Data)
            . ($NextSkillId
                ? new Redirect('/Education/Certificate/Prepare/Prepare/Setting', Redirect::TIMEOUT_SUCCESS, [
                    'PrepareId' => $PrepareCertificateId,
                    'NextSkillId' => $NextSkillId,
                    'Route' => $Route,
                    'IsNotGradeType' => false
                ])
                : new Redirect('/Education/Certificate/Prepare/Prepare/Setting', Redirect::TIMEOUT_SUCCESS, [
                    'PrepareId' => $PrepareCertificateId,
                    'Route' => $Route,
                    'IsNotGradeType' => true
                ])
            );
    }
}