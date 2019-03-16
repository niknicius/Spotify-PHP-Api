<?php
/**
 * Created by PhpStorm.
 * User: nikni
 * Date: 15/03/2019
 * Time: 23:18
 */


include "SpotifyAPI.php";

$api = new \SpotifyAPI\SpotifyAPI();

if(isset($_GET['code'])){
    $code = $_GET['code'];
    $api->setAuthorizationCode($code);
    $api->getAccessToken();
    var_dump($api->getUsersProfile("12187184583"));
}
else{
    header("Location: ". $api->generateAuthUrl());
}


