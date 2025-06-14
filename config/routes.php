<?php
/**
 * Site URL Rules
 *
 * You can define custom site URL rules here, which Craft will check in addition
 * to routes defined in Settings → Routes.
 *
 * Read all about Craft’s routing behavior, here:
 * https://craftcms.com/docs/4.x/routing.html
 */

return [

    'rekeningbeheer/speler/<userId:\d+>' => ['template' => 'rekeningbeheer/speler'],
    'rekeningbeheer/gast/<userId:\d+>' => ['template' => 'rekeningbeheer/gast'],



    // Routes voor winkelmandje
    [
        'pattern' => 'winkelmandje/add-item/',
        'route' => 'winkelmandje-module/stash/add-item',
        'verb' => ['POST', 'GET'],
    ],
    [
        'pattern' => 'winkelmandje/remove-item/<id:\d+>',
        'route' => 'winkelmandje-module/stash/remove-item',
        'verb' => 'POST',
    ],

    // Routes voor notificaties
    [
        'pattern' => 'notificaties/check-rekeningen',
        'route' => 'notificaties-module/notificaties/create-rekening-notification',
        'verb' => 'POST',
    ],

];
