<?php

namespace modules\winkelmandje\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use modules\winkelmandje\traits\StashTrait;
use yii\filters\AccessControl;

class StashController extends Controller
{
    use StashTrait; // some helper methods

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

    /**
     * Adds an item to the stash.
     *
     * This method handles the addition of an item to the user's stash.
     *
     * @return void
     */
    public function actionAddItem() {
        // get the drankje id from the request
        $drankjeId = Craft::$app->getRequest()->getRequiredParam('drankje');
        
        // get logged in user id
        $userId = $this->userId;

        // find existing open stash, if not found create a new one
        $entry = $this->findOrCreateStash($userId);

        // Get the matrix field data
        $itemQuery = $entry->getFieldValue('stash_items'); // stash_items is the handle of the matrix field
        $existing_items = $itemQuery->all(); // Get all the items
        
        // create a new array with the existing items, we need this to update the sortOrder array
        $stash_items = [
            'sortOrder' => array_map(fn($item) => $item->id, $existing_items)
        ];

        // finally, create a new stash item
        $newItem = $this->createNewStashItem($entry, $drankjeId);

        // add the new item to the sortOrder array
        $stash_items['sortOrder'][] = $newItem->id;

        // update the stash title
        $entry->title = "[OPENSTAAND] Rekening van " . Craft::$app->getUser()->getIdentity()->username . " (" . count($stash_items['sortOrder']) . " items)";

        // save the new sortOrder array
        $entry->stash_items = $stash_items;

        // save the entry
        Craft::$app->getElements()->saveElement($entry);

        // redirect to the stash page
        return $this->redirect('/drankenregistratie');
    }


    /**
     * Finds an existing stash for the given user ID or creates a new one if it doesn't exist.
     *
     * @param int $userId The ID of the user for whom to find or create the stash.
     * @return Stash The found or newly created stash.
     */
    private function findOrCreateStash($userId) {

        // find the stash entry for the given user
        $entry = Entry::find()
            ->section('stash_section')
            ->stash_status('open')
            ->relatedTo([
                'targetElement' => $userId,
                'field' => 'stash_user',
            ])
            ->orderBy('dateCreated DESC')
            ->one();

        // if no stash is found, create a new one
        if (!$entry) {
            $entry = $this->createNewStash($userId);
        }

        // return the found or newly created stash
        return $entry;
    }

    /**
     * Creates a new stash for the given user.
     *
     * @param int $userId The ID of the user for whom the stash is being created.
     * @return void
     */
    private function createNewStash($userId) {
        $section = $this->getSectionByHandle('stash_section');
        $entryType = $this->getEntryType($section);

        $entry = new Entry();
        $entry->sectionId = $section->id;
        $entry->typeId = $entryType->id;
        $entry->authorId = $userId;
        $entry->enabled = true;
        $entry->title = '[NEW] Stash voor ' . Craft::$app->getUser()->getIdentity()->username;
        $entry->stash_status = 'open';
        $entry->stash_user = [$userId];
        Craft::$app->getElements()->saveElement($entry);

        return $entry;
    }

    /**
     * Creates a new stash item.
     *
     * @param mixed $entry The entry data for the new stash item.
     * @param int $drankjeId The ID of the drankje to be associated with the stash item.
     * @return void
     */
    private function createNewStashItem($entry, $drankjeId) {
        $userId = $this->userId;
        $itemFieldId = $entry->stash_items->fieldId[0];
    
        // get the drankje entry for some basic data
        $drankje = Entry::find()
            ->section('drankjes')
            ->id($drankjeId)
            ->one();
    
        // Controleer of het drankje bestaat
        if (!$drankje) {
            throw new \Exception("Drankje met ID $drankjeId niet gevonden.");
        }
    
        // now we can add the new item to the array
        // for this we need to create a new item and save it
        $newItem = new Entry(); // Maak een nieuw item aan
        
        // meta data
        $newItem->fieldId = $itemFieldId; // De ID van het matrix veld
        $newItem->authorId = $userId; // De ID van de auteur
        $newItem->ownerId = $entry->id; // De ID van de entry waartoe het item behoort
        $newItem->enabled = true; // Zorg dat het item is ingeschakeld
        
        // fields
        $newItem->title = $drankje->title . ' - ' . date('D d M H:i', strtotime('+7 days')); // Zorg dat de titel uniek is
        $newItem->drankje = [ $drankjeId ];
        $newItem->prijs = $drankje->getFieldValue('prijs');

         
        // Sla het item op
        if (!Craft::$app->getElements()->saveElement($newItem)) {
            throw new \Exception('Failed to save the new stash item due to insufficient permissions.');
        }
        
        return $newItem;
    }



}



