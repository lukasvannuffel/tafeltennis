<?php


namespace modules\wedstrijden\controllers;

use Craft;
use craft\web\Controller;
use modules\wedstrijden\traits\WedstrijdenTrait;
use craft\elements\Entry;
use yii\filters\AccessControl;

class WedstrijdenController extends Controller
{
    use WedstrijdenTrait; // some helper methods

    private $userId;

    public function init() : void {
        parent::init();

        // get the logged in user id
        $this->userId = Craft::$app->getUser()->getIdentity()->getId();
    }
    
    /**
     * Defines the behaviors for the WedstrijdenController.
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
                        'roles' => ['@'], // Only logged-in users
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
        // dd('De AddParticipant route is bereikt.', __METHOD__);
        //
        $wedstrijdId = Craft::$app->getRequest()->getRequiredParam('wedstrijdId');

        // get logged in userId
        $userId = $this->userId;

        // Find or create a planning entry
        $entry = $this->findOrCreatePlanning($userId);

        // Get the matrix field data
        $itemQuery = $entry->getFieldValue('matches'); // matches is the handle of the matrix field
        $existing_items = $itemQuery->all(); // Get all the items

        // Create a new array with the existing items, we need this to update the sortOrder array
        $matches = [
            'sortOrder' => array_map(fn($item) => $item->id, $existing_items)
        ];

        // Create a new planning item
        $newItem = $this->createNewPlanningItem($entry, $wedstrijdId);

        // Add the new item to the sortOrder array
        $matches['sortOrder'][] = $newItem->id;

        // Update the planning title
        $entry->title = "Planning voor " . Craft::$app->getUser()->getIdentity()->username;

        // Save the new sortOrder array
        $entry->matches = $matches;

        // Save the planning entry
        Craft::$app->getElements()->saveElement($entry);

        // Redirect back to the wedstrijden page
        return $this->redirect(Craft::$app->request->referrer);
    }

     /**
     * Action to update the player status from 'wachtend' to 'bevestigd'
     * Only administrators can perform this action
     */
    public function actionUpdatePlayerStatus()
    {
        // Check if the current user is an administrator
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser || !$currentUser->isInGroup('beheerders')) {
            Craft::$app->getSession()->setError('Alleen beheerders kunnen de spelerstatus bijwerken.');
            return $this->redirect(Craft::$app->request->referrer);
        }

        // Get required parameters
        $planningId = Craft::$app->getRequest()->getRequiredParam('planningId');
        $status = Craft::$app->getRequest()->getRequiredParam('status');
        $wedstrijdId = Craft::$app->getRequest()->getRequiredParam('wedstrijdId');

        // Find the planning entry
        $planningEntry = Entry::find()
            ->section('planning_section')
            ->id($planningId)
            ->one();

        if (!$planningEntry) {
            Craft::$app->getSession()->setError('Planning niet gevonden.');
            return $this->redirect(Craft::$app->request->referrer);
        }

        // Update the status
        $planningEntry->speler_status = $status;

        // Save the entry
        if (Craft::$app->getElements()->saveElement($planningEntry)) {
            Craft::$app->getSession()->setNotice('Spelerstatus is bijgewerkt.');
        } else {
            Craft::$app->getSession()->setError('Er is een fout opgetreden bij het bijwerken van de spelerstatus.');
        }

        // Redirect back to the wedstrijd detail page
        return $this->redirect(Craft::$app->request->referrer);
    }


    private function findOrCreatePlanning($userId) {
        // Find the planning entry for the given user
        $entry = Entry::find()
            ->section('planning_section')
            ->speler_status('wachtend')
            ->relatedTo([
                'targetElement' => $userId,
                'field' => 'speler',
            ])
            ->orderBy('dateCreated DESC')
            ->one();

        // If no planning entry is found, create a new one
        if (!$entry) {
            $entry = $this->createNewPlanning($userId);
        }

        // Return the found or newly created planning entry
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
        $entry->speler_status = 'wachtend';
        $entry->speler = [$userId];
        Craft::$app->getElements()->saveElement($entry);

        return $entry;
    }

    private function createNewPlanningItem($entry, $wedstrijdId){
        $userId = $this->userId;
        $itemFieldId = $entry->matches->fieldId[0];

        // Get the wedstrijd entry for basic data
        $wedstrijd = Entry::find()
            ->section('wedstrijden')
            ->id($wedstrijdId)
            ->one();

        // Check if the wedstrijd exists
        if (!$wedstrijd) {
            throw new \Exception('Wedstrijd niet gevonden');
        }

        // Now we can add the new item to the array
        // For this we need to create a new item and save it
        $newItem = new Entry(); // Create a new item

        // Meta data
        $newItem->fieldId = $itemFieldId;
        $newItem->authorId = $userId;
        $newItem->ownerId = $entry->id;
        $newItem->enabled = true;

        // Fields
        $newItem->title = $wedstrijd->title . ' - ' . date('D d M H:i', strtotime('+7 days'));
        $newItem->wedstrijd = [$wedstrijdId];
        $newItem->setFieldValue('datum', (new \DateTime('+7 days'))->format('Y-m-d H:i'));
        if ($wedstrijd->adres) {
            $newItem->setFieldValue('adres', $wedstrijd->adres);
        }

        // Save the new item
        if(!Craft::$app->getElements()->saveElement($newItem)) {
            throw new \Exception('Er is een fout opgetreden bij het opslaan van het nieuwe item');
        }

        return $newItem;
    }
}