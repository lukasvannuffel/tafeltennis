<?php

namespace modules\winkelmandje;

use modules\winkelmandje\events\PaymentUpdate;
use yii\base\BootstrapInterface;
use yii\base\Module;

class WinkelmandjeModule extends Module implements BootstrapInterface
{
    public function init()
    {
        parent::init();
        
        PaymentUpdate::handle();   

    }

    public function bootstrap($app)
    {
        
    }
}