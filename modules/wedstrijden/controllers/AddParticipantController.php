<?php

namespace modules\wedstrijden\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\elements\User;
use yii\web\Response;

class AddParticipantController extends Controller
{
    protected array|int|bool $allowAnonymous = false;

    public function actionAddParticipant(int $id): ?Response
    {
        $this->requirePostRequest(); // Zorg dat het een POST-verzoek is
        $user = Craft::$app->getUser()->getIdentity();
    
        if (!$user) {
            return $this->asErrorJson('Je moet ingelogd zijn om deel te nemen.');
        }
    
        // Zoek de wedstrijd op via de ID in de route
        $entry = Entry::find()->id($id)->one();
        if (!$entry) {
            return $this->asErrorJson('Wedstrijd niet gevonden.');
        }
    
        // Controleer of de gebruiker al deelneemt
        $matrixField = $entry->getFieldValue('deelnemers');
        foreach ($matrixField->all() as $block) {
            if ($block->speler->id === $user->id) {
                return $this->asErrorJson('Je neemt al deel aan deze wedstrijd.');
            }
        }
    
        // Voeg de gebruiker toe aan het matrixveld
        $matrixField->add([
            'type' => 'blockTypeHandle', // Pas aan met jouw matrix block type handle
            'fields' => [
                'speler' => $user->id,
                'spelerstatus' => 'wachtend',
            ],
        ]);
    
        $entry->setFieldValue('deelnemers', $matrixField);
    
        if (!Craft::$app->getElements()->saveElement($entry)) {
            return $this->asErrorJson('Kan de deelnemer niet toevoegen.');
        }
    
        return $this->asJson(['success' => true, 'message' => 'Je deelname is succesvol geregistreerd.']);
    }
    
}
