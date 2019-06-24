<?php


namespace flipbox\saml\core\exceptions;

use yii\web\HttpException;

class AccessDenied extends HttpException
{

    public $statusCode=403;

    /**
     * Constructor.
     * @param string $message error message
     * @param int $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($this->statusCode, $message, $code, $previous);
    }
}
