<?php

namespace modules\notificaties;

use Craft;
use yii\base\BootstrapInterface;
use yii\base\Module;

class NotificatieModule extends Module implements BootstrapInterface
{
    public function init()
    {
        Craft::setAlias('@modules/notificaties', __DIR__);

    
        parent::init();
    }

    public function bootstrap($app)
    {

        
    }
}