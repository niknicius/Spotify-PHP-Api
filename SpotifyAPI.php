<?php
/**
 * Created by PhpStorm.
 * User: nikni
 * Date: 15/03/2019
 * Time: 23:04
 */

namespace SpotifyAPI;


class SpotifyAPI
{

    const API_URL = "https://api.spotify.com/v1/";
    const ACCOUNTS_URL = "https://accounts.spotify.com/";
    const REDIRECT_URL = "http://localhost/teste.php";
    const POST = "POST";
    const GET = "GET";
    const DEBUG_MODE = 1;

    private $client_id = "17de3e80c9bf4082bf7b6153f68c2554";
    private $client_secret;

    private $authorization_code;
    private $access_token;
    private $token_type;
    private $token_expiration;
    private $refresh_token;

    public function __construct()
    {
        $arquivo = fopen ('client_secret.txt', 'r');
        $linha = fgets($arquivo, 1024);
        $this->client_secret = $linha;
        fclose($arquivo);
    }

    /**
     * @param mixed $authorization_code
     */
    public function setAuthorizationCode($authorization_code)
    {
        $this->authorization_code = $authorization_code;
    }

    /**
     * @param mixed $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @param mixed $token_type
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
    }

    /**
     * @param mixed $token_expiration
     */
    public function setTokenExpiration($token_expiration)
    {
        $this->token_expiration = time() + $token_expiration;
    }

    /**
     * @param mixed $refresh_token
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
    }

    public function generateAuthUrl(){
        $scopes = "user-read-private user-read-email user-top-read user-read-currently-playing";
        $url = self::ACCOUNTS_URL . "authorize" .
            "?response_type=code" .
            "&client_id=" . $this->client_id .
            "&scope=" . urlencode($scopes).
            "&redirect_uri=" . urlencode(self::REDIRECT_URL);

        return $url;

    }

    public function getAccessToken(){
        $url = self::ACCOUNTS_URL . 'api/token' .
            "?grant_type=authorization_code" .
            "&code=" . $this->authorization_code.
            "&redirect_uri=" . self::REDIRECT_URL;

        $auth = "Authorization: Basic " . base64_encode($this->client_id . ':' . $this->client_secret);
        $headers = [
            $auth,
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $response = $this->post($url,$headers);
        $response = json_decode($response,true);
        var_dump($response);
        $this->setAccessToken($response['access_token']);
        $this->setTokenType($response['token_type']);
        $this->setTokenExpiration($response['expires_in']);
        $this->setRefreshToken($response['refresh_token']);

    }

    public function getUsersTop($type, $limit, $offset, $time_range){
        $url = self::API_URL . "me/top/";
        switch ($type){
            case 1:
                $url .= "artists";
                break;
            case 2:
                $url .= "tracks";
                break;
            default:
                $url .= "tracks";
        }

        $url .= "?limit=" . $limit .
            "&offset=" . $offset .
            "&time_range=" . $time_range;

        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        var_dump($url);
        return $this->get($url,$headers);
    }

    public function getMeProfile(){
        $url = self::API_URL . 'me';
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getUsersProfile($id){
        $url = self::API_URL . 'users/' . $id;
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }


    private function post($url,$headers){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        return curl_exec($curl);
    }

    private function get($url,$headers){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        return curl_exec($curl);
    }



}