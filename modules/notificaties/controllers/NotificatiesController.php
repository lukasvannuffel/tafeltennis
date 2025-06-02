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
        // Step 1: Check of de user een open rekening heeft
        $openStashes = Entry::find()
            ->section('stash_section')
            ->stash_status('open') 
            ->stash_user($this->userId) 
            ->all();

        if (empty($openStashes)) {
            // Als er geen open rekeningen zijn, verwijder oude meldingen
            $this->clearOldRekeningNotifications();
            Craft::$app->getSession()->setNotice('Geen openstaande rekeningen gevonden.');
            return $this->redirectToPostedUrl();
        }

        // Step 2: Check of de notificatie al bestaat voor deze user
        $existingNotification = Entry::find()
            ->section('notificaties')
            ->speler($this->userId) // Assuming 'speler' is your user relation field handle
            ->title(['Open Rekening', 'Open Rekeningen'])
            ->one();

        if ($existingNotification) {
            // Update de bestaande notificatie met de nieuwe count
            $this->updateExistingNotification($existingNotification, count($openStashes));
        } else {
            // maak een nieuwe notificatie aan
            $this->createNewNotification(count($openStashes));
        }

        Craft::$app->getSession()->setNotice('Meldingen bijgewerkt.');
        return $this->redirectToPostedUrl();
    }

    private function createNewNotification($stashCount)
    {
        $notification = new Entry();
        $notification->sectionId = $this->getNotificatiesSectionId();
        $notification->typeId = $this->getNotificatiesTypeId();
        
        // Set de titel gebaseerd op het aantal rekeningen
        $notification->title = $stashCount > 1 ? 'Open Rekeningen' : 'Open Rekening';
        
        // Set de messge
        $message = $stashCount > 1 
            ? "Je hebt {$stashCount} openstaande rekeningen. Klik hier om ze te bekijken."
            : "Je hebt 1 openstaande rekening. Klik hier om deze te bekijken.";
        
        $notification->setFieldValue('message', $message);
        $notification->setFieldValue('speler', [$this->userId]); 
        
        $notification->authorId = $this->userId; 
        $notification->enabled = true;

        if (!Craft::$app->getElements()->saveElement($notification)) {
            Craft::error('Failed to create notification: ' . implode(', ', $notification->getErrorSummary(true)), __METHOD__);
        }
    }

    private function updateExistingNotification($notification, $stashCount)
    {
        // Update de titel en message
        $notification->title = $stashCount > 1 ? 'Open Rekeningen' : 'Open Rekening';
        
        $message = $stashCount > 1 
            ? "Je hebt {$stashCount} openstaande rekeningen. Klik hier om ze te bekijken."
            : "Je hebt 1 openstaande rekening. Klik hier om deze te bekijken.";
        
        $notification->setFieldValue('message', $message);
        
        if (!Craft::$app->getElements()->saveElement($notification)) {
            Craft::error('Failed to update notification: ' . implode(', ', $notification->getErrorSummary(true)), __METHOD__);
        }
    }

    private function clearOldRekeningNotifications()
    {
        $oldNotifications = Entry::find()
            ->section('notificaties')
            ->speler($this->userId)
            ->title(['Open Rekening', 'Open Rekeningen'])
            ->all();

        foreach ($oldNotifications as $notification) {
            Craft::$app->getElements()->deleteElement($notification);
        }
    }

    private function getNotificatiesSectionId()
    {
        $entriesService = Craft::$app->getEntries();
        $section = $entriesService->getSectionByHandle('notificaties');
        return $section ? $section->id : null;
    }

    private function getNotificatiesTypeId()
    {
        $entriesService = Craft::$app->getEntries();
        $section = $entriesService->getSectionByHandle('notificaties');
        if ($section) {
            $entryTypes = $section->getEntryTypes();
            return $entryTypes[0]->id ?? null;
        }
        return null;
    }
}