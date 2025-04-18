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

    
    }
}
