<?php


namespace flipbox\saml\core\validators;

use flipbox\saml\core\records\AbstractProvider;
use SAML2\Response as SamlResponse;
use SAML2\Response\Validation\Result;
use SAML2\Response\Validation\ConstraintValidator\IsSuccessful;
use SAML2\Response\Validation\ConstraintValidator\DestinationMatches;
use SAML2\Configuration\Destination;

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

    public function __construct(
        AbstractProvider $identityProvider,
        AbstractProvider $serviceProvider
    )
    {

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
                    $this->serviceProvider->getFirstSpAcsService()->getLocation()
                )
            ),

        ];
        if($key = $this->identityProvider->getSigningXMLSecurityKey()) {
            $this->validators[] = new SignedElement($key);
        }
    }

    public function validate(SamlResponse $response)
    {

        $this->addAssertionValidators($response->getAssertions());

        $responseResult = new ResponseResult();
        foreach ($this->validators as $validator) {
            $validator->validate($response, $responseResult);
        }

    }


}