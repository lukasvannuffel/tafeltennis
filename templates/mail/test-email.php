<?php

define('CRAFT_BASE_PATH', __DIR__);
require_once CRAFT_BASE_PATH . '/vendor/autoload.php';
$app = require CRAFT_BASE_PATH . '/bootstrap.php';

// Send a test email
$message = new \craft\mail\Message();
$message->setSubject('Test Email from HNO Assenede');
$message->setHtmlBody('<p>This is a test email to verify that Mailpit is working correctly.</p>');
$message->setTextBody('This is a test email to verify that Mailpit is working correctly.');
$message->setFrom(['hello@hnoassenede.be' => 'HNO Assenede']);
$message->setTo(['lukasvannuffel02@gmail.com' => 'Lukas Van Nuffel']);

try {
    $success = Craft::$app->getMailer()->send($message);
    echo $success ? "Email sent successfully!\n" : "Email failed to send.\n";
} catch (\Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
}