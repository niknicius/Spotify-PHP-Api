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
    header("Location: http://localhost/teste.php?pg=ph");

}
else if(isset($_GET['pg'])){
    header('Content-Type: application/json');
    echo $api->getAlbums("41MnTivkwTO3UUJ8DrqEJJ,6JWc4iAiJ9FjyK0B59ABb4,6UXCm6bOO4gFlDQZV5yL37");

}
else{
    header("Location: ". $api->generateAuthUrl());
}


