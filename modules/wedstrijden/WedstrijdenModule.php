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
    
        // Zorg ervoor dat Craft de controllers in deze module herkent
        $this->controllerNamespace = 'modules\wedstrijden\controllers';
    
        parent::init();
    }

    public function bootstrap($app)
    {

    
    }
}
