<?php
namespace Core\GetContent;

class LadyPhotos{
    public function __construct($api_url){
        $this->api_url = $api_url;
    }
    public function _getPhoto(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $img = curl_exec($ch);
        curl_close($ch);
        return $img;
    }
}