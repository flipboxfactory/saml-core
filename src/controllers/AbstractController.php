<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers;

class AbstractController extends \craft\web\Controller
{

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
