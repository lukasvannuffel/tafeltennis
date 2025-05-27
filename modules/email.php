    <?php
    
    function sendMail($subject, $body){

    // Send a test email
    $message = new \craft\mail\Message();
    $message->setSubject($subject);
    $message->setHtmlBody($body);
    $message->setTextBody($body);
    $message->setFrom(['hello@hnoassenede.be' => 'HNO Assenede']);
    $message->setTo(['lukasvannuffel02@gmail.com' => 'Lukas Van Nuffel']);

    try {
        $success = Craft::$app->getMailer()->send($message);
        echo $success ? "Email sent successfully!\n" : "Email failed to send.\n";
    } catch (\Exception $e) {
        echo "Error sending email: " . $e->getMessage() . "\n";
    }
 }