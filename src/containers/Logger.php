<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipbox/craft-psr3/blob/master/LICENSE
 * @link       https://github.com/flipbox/craft-psr3
 */

namespace flipbox\saml\core\containers;

use Craft;
use Psr\Log\LoggerInterface;
use yii\base\component;
use yii\helpers\ArrayHelper;
use yii\log\Logger as YiiLogger;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Logger extends Component implements LoggerInterface
{
    /**
     * The Yii2 category to use when logging
     *
     * @var string
     */
    public $category = 'saml-core';

    /**
     * The logger
     *
     * @var null|YiiLogger
     */
    public $logger;

    /**
     * The default level to use when an arbitrary level is used.
     *
     * @var string
     */
    public $level = YiiLogger::LEVEL_INFO;

    /**
     * The PSR-3 to Yii2 log level map
     *
     * @var array
     */
    public $map = [
        'emergency' => YiiLogger::LEVEL_ERROR,
        'alert' => YiiLogger::LEVEL_ERROR,
        'critical' => YiiLogger::LEVEL_ERROR,
        'error' => YiiLogger::LEVEL_ERROR,
        'warning' => YiiLogger::LEVEL_WARNING,
        'notice' => YiiLogger::LEVEL_INFO,
        'info' => YiiLogger::LEVEL_INFO,
        'debug' => YiiLogger::LEVEL_TRACE,
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->logger = Craft::$app->log->getLogger();
    }

    /**
     * Log a message, transforming from PSR3 to the closest Yii2.
     *
     * @inheritdoc
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Resolve category from 'context'
        $category = ArrayHelper::remove($context, 'category', $this->category);

        // Resolve level
        $level = ArrayHelper::getValue($this->map, $level, $this->level);

        $this->logger->log(
            $this->interpolate($message, $context),
            $level,
            $category
        );
    }

    /**
     * @inheritdoc
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return string
     */
    private function interpolate(string|\Stringable $message, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
