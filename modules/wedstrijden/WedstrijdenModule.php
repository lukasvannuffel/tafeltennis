<?php

namespace modules\wedstrijden;

use Craft;
use yii\base\BootstrapInterface;
use yii\base\Module;

class WedstrijdenModule extends Module implements BootstrapInterface
{
    public function init()
    {
        Craft::setAlias('@modules/wedstrijden', __DIR__);

    
        parent::init();
    }

    public function bootstrap($app)
    {
        // Register our module routes
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }
        
        // Register our site URL rules
        $app->urlManager->addRules([
            'wedstrijden/update-status' => 'wedstrijden-module/wedstrijden/update-player-status',
        ]);
    }
}
