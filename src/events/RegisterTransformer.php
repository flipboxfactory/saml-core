<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\events;


use yii\base\InvalidConfigException;

class RegisterTransformer
{

    protected $transformers = [];

    /**
     * @param string $entityId
     * @param string $class
     * @return $this
     * @throws InvalidConfigException
     */
    public function setTransformer(string $transformerClass, string $entityId, string $mustInheritClass = null)
    {
        if ($mustInheritClass && (! $transformerClass instanceof $mustInheritClass)) {
            throw new InvalidConfigException("Transformer must be instance of " . $mustInheritClass);
        }
        $this->transformers[$entityId] = $transformerClass;
        return $this;
    }

    /**
     * @param $entityId
     * @return mixed|null
     */
    public function getTransformer($entityId)
    {
        return isset($this->transformers[$entityId]) ? $this->transformers[$entityId] : null;
    }
}