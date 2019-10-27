<?php
require_once __DIR__.'/vendor/autoload.php';

use DeezerAlert\Handler;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Email;

(new Dotenv())->load(__DIR__.'/.env');

if (!($deezerUserId = getenv('DEEZER_USERID')) || !($emailRecipient = getenv('EMAIL_RECIPIENT')) || !($emailSender = getenv('EMAIL_SENDER'))) {
    throw new RuntimeException('Configuration is not complete in ".env" file.');
}

$newContent = (new Handler($deezerUserId))->process();

if ($newContent) {
    ob_start();
    include(__DIR__.'/templates/mail.content.php');
    $content = ob_get_clean();

    (new Mailer(new SmtpTransport()))->send(
        (new Email())
            ->from($emailSender)
            ->to($emailRecipient)
            ->subject('Deezer - New release!')
            ->html($content)
    );
}
