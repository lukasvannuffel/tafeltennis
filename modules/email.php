<?php

function sendMail($subject, $body)
{
    $user = \Craft::$app->getUser()->getIdentity();
    $toEmail = $user ? $user->email : 'fallback@fallback.com';

    // Voeg hier je CSS toe
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f9fafb; color: #222; padding: 32px; }
            .container { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #e5e7eb; padding: 32px; max-width: 600px; margin: 0 auto; }
            h1 { color: #2563eb; }
            p { line-height: 1.6; }
            ul { padding-left: 20px; }
            li { margin-bottom: 8px; }
            .footer { color: #6b7280; font-size: 12px; margin-top: 32px; }
        </style>
    </head>
    <body>
        <div class="container">
            ' . $body . '
            <div class="footer">HNO Assenede &copy; ' . date('Y') . '</div>
        </div>
    </body>
    </html>
    ';

    $message = new \craft\mail\Message();
    $message->setSubject($subject);
    $message->setHtmlBody($html);
    $message->setTextBody(strip_tags($body));
    $message->setFrom(['hello@hnoassenede.be' => 'HNO Assenede']);
    $message->setTo([$toEmail => $user ? $user->friendlyName : 'Gebruiker']);

    try {
        $success = \Craft::$app->getMailer()->send($message);
        echo $success ? "Email sent successfully!\n" : "Email failed to send.\n";
    } catch (\Exception $e) {
        echo "Error sending email: " . $e->getMessage() . "\n";
    }
}