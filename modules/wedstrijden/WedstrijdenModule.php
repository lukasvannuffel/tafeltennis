<?php

namespace modules\wedstrijden;

use Craft;
use yii\base\BootstrapInterface;
use yii\base\Module;

class WedstrijdenModule extends Module implements BootstrapInterface
{
    public function init()
    {
        parent::init();


        // Optioneel: Log een bericht om te controleren of de module correct wordt geladen
        Craft::info('Wedstrijden module is geladen', __METHOD__);
    }

    public function bootstrap($app)
    {
        
    }
}
