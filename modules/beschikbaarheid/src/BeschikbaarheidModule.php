<?php
namespace modules\beschikbaarheid;

use Craft;
use yii\base\Module;

class BeschikbaarheidModule extends Module
{
    public function init()
    {
        Craft::setAlias('@beschikbaarheid', __DIR__);
        parent::init();
    }
}
