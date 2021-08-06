<?php
require_once __DIR__.'/vendor/autoload.php';

use DeezerAlert\Handler;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

(new Dotenv())->load(__DIR__.'/.env');

if (!($deezerAccessToken = getenv('DEEZER_ACCESS_TOKEN'))
    || !($emailRecipient = getenv('EMAIL_RECIPIENT'))
    || !($emailSender = getenv('EMAIL_SENDER'))
    || !($mailerDSN = getenv('MAILER_DSN'))
) {
    throw new RuntimeException('Configuration is not complete in ".env" file.');
}

try {
    (new Handler($deezerAccessToken))->process();
} catch (Exception $e) {
	(new Mailer(Transport::fromDsn($mailerDSN)))->send(
        (new Email())
            ->from($emailSender)
            ->to($emailRecipient)
            ->subject('DeezerAlert error')
            ->html('<pre>'.$e->getMessage().'</pre>')
    );
}
