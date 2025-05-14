<?php

$api_smsak = '8e6f7420b7ce27e8e19e7d6975064c5d8e6e7cb8';
//$url = 'https://smsak.org/api/numbersstatus/'.$api_smsak.'?code=RU';

$countries = 'https://smsak.org/api/countries/'.$api_smsak.'?code=RU';
$getNumber ='https://smsak.org/api/getnumber/'.$api_smsak.'?id='.$service.'&code='.$country_code;
$getservices = 'https://smsak.org/api/getservices/'.$api_smsak;
$getstatus = 'https://smsak.org/api/getstatus/'.$api_smsak.'?id='$id_activation;

function get_request($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_SSLVERSION,3);
    $result = curl_exec ($ch);

    print_r($result);
}

echo "Страны: ";

get_request($countries);



?>
