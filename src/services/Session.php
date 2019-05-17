<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services;

use craft\base\Component;

class Session extends Component
{
    const CORE_NAMESPACE = 'saml.core';
    const REQUEST_ID_KEY = 'request.id';
    const HASH_ALGO = 'sha256';

    /**
     * @param $id
     */
    public function setRequestId($id)
    {
        \Craft::$app->getSession()->set(
            $this->getName(static::REQUEST_ID_KEY),
            $id
        );
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return \Craft::$app->getSession()->get(
            $this->getName(static::REQUEST_ID_KEY)
        );
    }
    
    /**
     * @return string
     */
    public function getId()
    {
        return hash(static::HASH_ALGO, \Craft::$app->session->getId());
    }

    /**
     * @param $key
     * @return string
     */
    protected function getName($key)
    {
        return static::CORE_NAMESPACE . '/' . $key;
    }
}
