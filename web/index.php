<?php
require '../vendor/autoload.php';

use Core\GetContent;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use Slim\App;

$app = new Slim\App([
    'settings' => [
        'displayErrorDetails' => false,
        'Line'                => [
            'AccessToken' => getenv('LINE_CHANNEL_ACCESSTOKEN'),
            'SecretToken' => getenv('LINE_CHANNEL_SECRET'),
        ],
        'APIURL'              => getenv('APIURL'),
    ],
]);

$app->post('/', function ($request, $response, $args) use ($app) {
    $container  = $app->getContainer();
    $httpClient = new LINE\LINEBot\HTTPClient\CurlHTTPClient($container->settings['Line']['AccessToken']);
    $bot        = new LINE\LINEBot($httpClient, ['channelSecret' => $container->settings['Line']['SecretToken']]);
    $LadyPhotos = new Core\GetContent\LadyPhotos($container->settings['APIURL']);

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
    //同時最多只處理2筆event，避免洗訊息
    if(count($events) > 2){
        $event_count = 2;
    }else{
        $event_count = 1;
    }
    for ($i = 0; $i < $event_count; $i++) {
        $event = $events[$i];
        switch (get_class($event)) {
            //收到文字訊息
            case 'LINE\LINEBot\Event\MessageEvent\TextMessage':
                $replyText = strtolower(trim($event->getText()));
                switch ($replyText) {
                    case 'menu':
                        //傳遞按鈕
                        $actions = array(
                            new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("來張表特美女圖", "lady_photo"),
                        );
                        $button                 = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("老司機選單", "輸入menu可在叫我一次", "https://i.imgur.com/pjs79aM.jpg", $actions);
                        $templateMessageBuilder = new LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("老司機選單", $button);
                        $response               = $bot->replyMessage($event->getReplyToken(), $templateMessageBuilder);
                        break;
                    case '5566':
                        $img               = $LadyPhotos->_getPhoto();
                        $imgMessageBUilder = new LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img, $img);
                        $response          = $bot->replyMessage($event->getReplyToken(), $imgMessageBUilder);
                        break;
                }
                break;
            //收到Postback
            case 'LINE\LINEBot\Event\PostbackEvent':
                $postback = $event->getPostbackData();
                switch ($postback) {
                    case 'lady_photo':
                        $img               = $LadyPhotos->_getPhoto();
                        $imgMessageBUilder = new LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img, $img);
                        $response          = $bot->replyMessage($event->getReplyToken(), $imgMessageBUilder);
                        break;
                }
                break;
        }
    }
    return $response;
});
$app->run();
