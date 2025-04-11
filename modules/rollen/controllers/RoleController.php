<?php
namespace modules\rollen\controllers;

use Craft;
use craft\web\Controller;
use yii\ForbiddenHttpException;


class RoleController extends Controller{
    protected $allowAnonymous = ['index'];

    /**
     * Hoofdactie om de gebruikersrollen te checken en de juiste view te renderen.
     */
    public function actionIndex()
    {
        // De huidige gebruiker ophalen
        $currentUser = Craft::$app->getUser()->getIdentity();
        
        // Checken of de gebruiker ingelogd is
        if (!$currentUser) {
            // Terugsturen naar de loginpagina als de gebruiker niet ingelogd is
            return $this->redirect('/login');
        }

        // Checken tot welke user group de gebruiker behoort
        $isBeheerder = $this->userBelongsToGroup($currentUser, 'Beheerders');
        $isSpeler = $this->userBelongsToGroup($currentUser, 'Spelers');


        $variables = [
            'currentUser' => $currentUser,
            'isBeheerder' => $isBeheerder,
            'isSpeler' => $isSpeler,
        ];

        // Render de view met de variabelen
        return $this->renderTemplate('home/_entry', $variables);
    }
        /**
         * Checkt of de gebruiker behoort tot een bepaalde user group.
         */
        private function UserBelongsToGroup($user, $groupName)
        {
           if(!$user){
            return false;
           }

           $userGroups = $user->GetGroups();

        // Check of een groepnaam matcht met de naam van de user group
        foreach ($userGroups as $group) {
            if ($group->name === $groupName) {
                return true;
            }
        }
        
        return false;
    }
}