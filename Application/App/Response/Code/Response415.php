<?php

namespace SPHERE\Application\App\Response\Code;

use SPHERE\Application\App\Response\AbstractErrorResponse;

/**
 * Using the wrong content type
 *
 * AbstractResponse::HTTP_UNSUPPORTED_MEDIA_TYPE
 */
class Response415 extends AbstractErrorResponse
{
    public function __construct(mixed $content, mixed $context = null)
    {
        parent::__construct($content, self::HTTP_UNSUPPORTED_MEDIA_TYPE, $context);
    }
}