<?php


namespace flipbox\saml\core\validators;

use flipbox\saml\core\records\AbstractProvider;
use SAML2\Configuration\Destination;
use SAML2\Response\Validation\ConstraintValidator\DestinationMatches;
use SAML2\Response\Validation\ConstraintValidator\IsSuccessful;
use SAML2\Response\Validation\Result as ResponseResult;
use SAML2\Assertion\Validation\Result as AssertionResult;
use SAML2\Response as SamlResponse;

class Response
{
    /**
     * @var AbstractProvider
     */
    private $identityProvider;

    /**
     * @var AbstractProvider
     */
    private $serviceProvider;

    /**
     * @var array
     */
    private $validators = [];

    /**
     * Response constructor.
     * @param AbstractProvider $identityProvider
     * @param AbstractProvider $serviceProvider
     */
    public function __construct(
        AbstractProvider $identityProvider,
        AbstractProvider $serviceProvider
    ) {

        $this->identityProvider = $identityProvider;
        $this->serviceProvider = $serviceProvider;

        $this->addValidators();
    }

    private function addValidators()
    {
        $this->validators = [
            new IsSuccessful(),
            new DestinationMatches(
                new Destination(
                    $this->serviceProvider->firstSpAcsService()->getLocation()
                )
            ),

        ];
        if ($keyStore = $this->identityProvider->signingXMLSecurityKeyStore()) {
            $this->validators[] = new SignedElement($keyStore);
        }
    }

    /**
     * @param $response
     * @return ResponseResult
     */
    public function validate($response): ResponseResult
    {
        $responseResult = new ResponseResult();
        foreach ($this->validators as $validator) {
            $validator->validate($response, $responseResult);
        }

        $this->validateAssertions($response, $responseResult);


        return $responseResult;
    }

    /**
     * @param SamlResponse $response
     * @param ResponseResult $responseResult
     */
    protected function validateAssertions(SamlResponse $response, ResponseResult $responseResult)
    {
        $assertionResult = null;
        foreach ($response->getAssertions() as $assertion) {
            $validator = new Assertion(
                $response,
                $this->identityProvider,
                $this->serviceProvider
            );

            $assertionResult = $validator->validate($assertion);

            $this->addErrorsToResult($responseResult, $assertionResult);
        }
    }

    /**
     * @param ResponseResult $responseResult
     * @param AssertionResult $assertionResult
     */
    private function addErrorsToResult(ResponseResult $responseResult, AssertionResult $assertionResult)
    {
        foreach ($assertionResult->getErrors() as $error) {
            $responseResult->addError($error);
        }
    }
}
