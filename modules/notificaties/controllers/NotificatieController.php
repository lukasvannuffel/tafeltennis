<?php

namespace modules\notificaties\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\elements\User;

class NotificatieController extends Controller
{
    /**
     * Maak notificaties aan voor gebruikers met open rekeningen
     */
    public function actionCreateOpenRekeningNotifications()
    {
        dd('test createOpenRekeningNotifications');


        // get alle spelers (users) in de database
        $spelers = User::find()
            ->group('spelers')
            ->all();

        foreach ($spelers as $speler) {
            // Check of de  gebruiker open rekeningen heeft
            $openRekeningen = Entry::find()
                ->section('stash_section') 
                ->stash_status('open')
                ->relatedTo([
                    'targetElement' => $speler,
                    'field' => 'stash_user' 
                ])
                ->all();

            if (!empty($openRekeningen)) {
                // Kijk of er al een notificatie bestaat voor deze speler
                $existingNotification = Entry::find()
                    ->section('notificaties')
                    ->relatedTo([
                        'targetElement' => $speler,
                        'field' => 'speler'
                    ])
                    ->title('*Open Rekening*')
                    ->one();

                // Creeer alleen een notificatie als deze nog niet bestaat
                if (!$existingNotification) {
                    $this->createOpenRekeningNotification($speler, count($openRekeningen));
                }
            } else {
                // Als er geen open rekeningen zijn, verwijder de notificatie als deze bestaat
                $this->removeOpenRekeningNotifications($speler);
            }
        }

        return $this->asJson(['success' => true, 'message' => 'Notifications updated']);
    }

    /**
     * Maak een notificatie aan voor een gebruiker met open rekeningen
     */
    private function createOpenRekeningNotification(User $speler, int $count)
    {
        $notification = new Entry();
        $notification->sectionId = Craft::$app->sections->getSectionByHandle('notificaties')->id;
        
        // Get de entry type voor notificaties
        $entryTypes = Craft::$app->sections->getSectionByHandle('notificaties')->getEntryTypes();
        $notification->typeId = $entryTypes[0]->id;
        
        // Set de title en message gebaseerd op het aantal open rekeningen
        if ($count == 1) {
            $notification->title = 'Open Rekening';
            $notification->setFieldValue('message', 'Je hebt 1 openstaande rekening. Klik hier om deze te bekijken en te betalen.');
        } else {
            $notification->title = 'Open Rekeningen';
            $notification->setFieldValue('message', "Je hebt {$count} openstaande rekeningen. Klik hier om deze te bekijken en te betalen.");
        }

        // Link de notificatie aan de speler
        $notification->setFieldValue('speler', [$speler->id]);

        // Save the notification
        if (!Craft::$app->elements->saveElement($notification)) {
            Craft::error('Failed to create notification for user: ' . $speler->email, __METHOD__);
        }
    }

    /**
     * Verwijder open rekening notificaties voor een gebruiker
     */
    private function removeOpenRekeningNotifications(User $speler)
    {
        $notifications = Entry::find()
            ->section('notificaties')
            ->relatedTo([
                'targetElement' => $speler,
                'field' => 'speler'
            ])
            ->title(['Open Rekening', 'Open Rekeningen']) 
            ->all();

        foreach ($notifications as $notification) {
            Craft::$app->elements->deleteElement($notification);
        }
    }

    /**
     * Markeer een notificatie als gelezen 
     */
    public function actionMarkAsRead()
    {
        $notificationId = Craft::$app->request->getRequiredBodyParam('notificationId');
        
        $notification = Entry::find()
            ->id($notificationId)
            ->one();

        if ($notification) {
            $notification->setFieldValue('isRead', true);
            Craft::$app->elements->saveElement($notification);
            
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['success' => false, 'error' => 'Notification not found']);
    }
}