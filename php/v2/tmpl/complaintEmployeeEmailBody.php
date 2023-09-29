<?php
/**
 * @package NW\WebService\References\Operations\Notification
 * @var array $templateData
 * @var Seller $reseller
 */

use NW\WebService\References\Operations\Notification\Seller;

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <style>
            .some-css-here {

            }
        </style>
        <meta charset="utf-8" >
        <title><?= $templateData['DIFFERENCES'] ?></title>
    </head>
    <body>
        <main>
            <h1><?= $templateData['DIFFERENCES'] ?></h1>
            <p>Dear customer, <?= $templateData['DIFFERENCES'] ?> at <?= $templateData['DATE'] ?></p>
            <p>Affected complaint:</p>
            <ul>
                <li>id: <?= $templateData['COMPLAINT_ID'] ?></p></li>
                <li>#: <?= $templateData['COMPLAINT_NUMBER'] ?></p></li>
                <li>Created by: <?= $templateData['CREATOR_NAME'] ?></p></li>
                <li>Expertise: <?= $templateData['EXPERT_NAME'] ?></p></li>
                <li>Consumption: <?= $templateData['CONSUMPTION_NUMBER'] ?></p></li>
                ...
                Lets consider, I used all necessary fields and params ;-)
            </ul>
        </main>
        <section id="unsubscribe">
            <p>You got this message, because You subscribed to notifications at SomeSite</p>
            <p><a href="https://example.com/url/to/unsubscribe/script?email=<?= $templateData['CLIENT_EMAIL'] ?>">Unsubscribe!</a></p>
        </section>
    </body>
</html>