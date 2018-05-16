<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../vendor/autoload.php';

use Slim\App;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;

$app = new Slim\App([
    'settings' => [
        'displayErrorDetails' => false,
        'Line'                => [
            'AccessToken' => getenv('LINE_CHANNEL_ACCESSTOKEN'),
            'SecretToken' => getenv('LINE_CHANNEL_SECRET'),
        ],
        'APIURL'=>getenv('APIURL')
    ],
]);

$app->post('/', function ($request, $response, $args) use ($app) {
    $container  = $app->getContainer();
    //$imgs       = json_decode(file_get_contents("./img_result.json"), true);
    //$rand_num   = rand(0, count($imgs));
    $httpClient = new LINE\LINEBot\HTTPClient\CurlHTTPClient($container->settings['Line']['AccessToken']);
    $bot        = new LINE\LINEBot($httpClient, ['channelSecret' => $container->settings['Line']['SecretToken']]);

    // Check request with signature and parse request
    $signature = $request->getHeader(HTTPHeader::LINE_SIGNATURE);
    if (empty($signature)) {
        return $request->withStatus(400, 'Bad Request');
    }

    try {
        $events = $bot->parseEventRequest($request->getBody(), $signature[0]);
    } catch (InvalidSignatureException $e) {
        return $request->withStatus(400, 'Invalid signature');
    } catch (InvalidEventRequestException $e) {
        return $request->withStatus(400, "Invalid event request");
    }

    foreach ($events as $event) {
        if (!($event instanceof MessageEvent)) {
            continue;
        }
        if (!($event instanceof TextMessage)) {
            continue;
        }
        //$replyText = $event->getText();
        //$textMessageBuilder = new LINE\LINEBot\MessageBuilder\TextMessageBuilder('測試');
        $img = file_get_contents($container->settings['APIURL']);
        $imgMessageBUilder = new LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img, $img);
        $response          = $bot->replyMessage($event->getReplyToken(), $imgMessageBUilder);
        if ($response->isSucceeded()) {
            echo 'Succeeded!';
        }
    }
    return $response;
});
$app->run();
