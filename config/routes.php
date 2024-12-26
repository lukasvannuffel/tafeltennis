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
    [
        'pattern' => 'winkelmandje/add-item/', // <goodie:\d+> is a regex pattern that matches a number
        'route' => 'winkelmandje-module/stash/add-item',
        'verb' => ['POST', 'GET'],
    ],
    [
        'pattern' => 'winkelmandje/remove-item/<id:\d+>',
        'route' => 'winkelmandje-module/stash/remove-item',
        'verb' => 'POST',
    ],
    // [
    //     'beschikbaarheid' => 'modules/beschikbaarheid/src/index',
    // ]
];
