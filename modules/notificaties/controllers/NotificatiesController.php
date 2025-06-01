<?php

namespace modules\notificaties\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use yii\filters\AccessControl;

class NotificatiesController extends Controller
{
    private $userId;

    public function init(): void {
        parent::init();
        // Get the logged in user id
        $this->userId = Craft::$app->getUser()->getIdentity()->getId();
    }

    public function behaviors(): array {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only logged-in users
                    ],
                ],
                'denyCallback' => function () {
                    return $this->redirect('/login');
                },
            ],
        ]);
    }


    public function actionCreateRekeningNotification()
    {

        dd('De CreateRekeningNotification route is bereikt.');
    }
}
