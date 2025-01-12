<?php

namespace modules\wedstrijden\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\elements\User;
use yii\web\Response;

class AddParticipantController extends Controller
{
    private $userId;

    public function init() : void {
        parent::init();

        // get the logged in user id
        $this->userId = Craft::$app->getUser()->getIdentity()->getId();
    }
    
    /**
     * Defines the behaviors for the StashController.
     * By adding the AccessControl behavior, we can ensure that only logged in users can access the methods in this controller.
     *
     * @return array An array of behaviors to be applied to the controller.
     */
    public function behaviors(): array {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Alleen ingelogde gebruikers
                    ],
                ],
                'denyCallback' => function () {
                    return $this->redirect('/login');
                },
            ],
        ]);
    }
      public function actionAddParticipant()
      {
        
        //
        $wedstrijdId = Craft::$app->getRequest()->getRequiredParam('wedstrijd');

        // get logged in userId
        $userId = $this->userId;

        //Vind een bestaande of open planning, zoniet -> maak een nieuwe
        $entry = $this->findOrCreatePlanning($userId);

        //Krijg de matrix-veld data
        $itemQuery = $entry->getFieldValue('matches'); // matches is de handle van het matrix-veld
        $existing_items = $itemQuery->all(); // Alle items in de matrix-veld

        //maak een nieuwe array met de bestaande items, we hebben dit nodig om de sortOrder array up-to-date te houden
        $matches = [
            'sortOrder' => array_map(fn($item) => $item->id, $existing_items)
        ];

        //maak een nieuw planning item
        $newItem = $this->createNewPlanningItem($entry, $wedstrijdId);

        //voeg het nieuwe item toe aan de SortOrder array
        $matches['sortOrder'][] = $newItem->id;

        //update de planning titel
        $entry->title = "Planning voor " . Craft::$app->getUser()->getIdentity()->username;

        //sla de nieuwe sortOrder array op
        $entry->matches = $matches;

        //sla de planning entry op
        Craft::$app->getElements()->saveElement($entry);


        //verwijs terug naar de wedstrijd pagina
        return $this->redirect('/wedstrijden');

        Craft::info('Route werd aangesproken.', __METHOD__);

        $wedstrijdId = Craft::$app->getRequest()->getRequiredParam('wedstrijd');
        Craft::info('Wedstrijd ID ontvangen: ' . $wedstrijdId, __METHOD__);

        var_dump('Actie bereikt');
        die;
      }

      private function findOrCreatePlanning($userId) {

        //Vind de planning entry voor de gegeven user
        $entry = Entry::find()
          ->section('planning_section')
          ->spelerstatus('wachtend')
          ->relatedTo([
            'targetElement' => $userId,
            'field' => 'speler',
        ])
        ->orderBy('dateCreated DESC')
        ->one();

        //Als er geen planning entry is gevonden, maak een nieuwe
        if (!$entry) {
            $entry = $this->createNewPlanning($userId);
        }
        //Return de gevonden of nieuwe planning entry
        return $entry;
      }

        private function createNewPlanning($userId) {
            $section = $this->getSectionByHandle('planning_section');
            $entryType = $this->getEntryType($section);

            $entry = new Entry();
            $entry->sectionId = $section->id;
            $entry->typeId = $entryType->id;
            $entry->authorId = $userId;
            $entry->enabled = true;
            $entry->title = "Planning voor " . Craft::$app->getUser()->getIdentity()->username;
            $entry->spelerstatus = 'wachtend';
            $entry->speler = [$userId];
            Craft::$app->getElements()->saveElement($entry);

            return $entry;
        }

        private function createNewPlanningItem($entry, $wedstrijdId){
            $userId = $this->userId;
            $itemFieldId = $entry->matches->fieldId[0];

            //krijg de wedstrijd entry voor basisdata
            $wedstrijd = Entry::find()
                ->section('wedstrijden')
                ->id($wedstrijdId)
                ->one();

            //Controleer of de wedstrijd bestaat
            if (!$wedstrijd) {
                throw new \Exception('Wedstrijd niet gevonden');
            }

            // nu kunnen we het nieuwe item toevoegen aan de array
            // hiervoor moeten we een nieuw item maken en opslaan
            $newItem = new Entry(); // Nieuw item aanmaken

            // meta data
            $newItem->fieldId = $itemFieldId;
            $newItem->authorId = $userId;
            $newItem->ownerId = $entry->id;
            $newItem->enabled = true;

            // velden
            $newItem->title = $wedstrijd->title . ' - ' . date('D d M H:i', strtotime('+7 days'));
            $newItem->wedstrijd = [$wedstrijdId];
            $newItem->datum = date('Y-m-d H:i', strtotime('+7 days'));
            $newItem->adres = $wedstrijd->adres;

            // sla het nieuwe item op
            if(!Craft::$app->getElements()->saveElement($newItem)) {
                throw new \Exception('Er is een fout opgetreden bij het opslaan van het nieuwe item');
            }

            return $newItem;

        }


}

