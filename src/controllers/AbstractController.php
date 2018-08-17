<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers;

use flipbox\ember\filters\FlashMessageFilter;
use flipbox\ember\filters\ModelErrorFilter;
use flipbox\ember\filters\RedirectFilter;
use yii\helpers\ArrayHelper;

class AbstractController extends \flipbox\ember\controllers\AbstractController
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'redirect' => [
                    'class' => RedirectFilter::class
                ],
                'error'    => [
                    'class' => ModelErrorFilter::class
                ],
                'flash'    => [
                    'class' => FlashMessageFilter::class
                ]
            ]
        );
    }

    /************************************************
     * Access Control
     ************************************************/

    /**
     * @return bool
     * @throws \yii\web\ForbiddenHttpException
     */
    public function checkAdminAccess(): bool
    {
        $this->requireAdmin();
        return true;
    }
}
