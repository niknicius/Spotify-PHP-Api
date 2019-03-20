<?php /** @noinspection ALL */

/**
 * Created by PhpStorm.
 * User: nikni
 * Date: 15/03/2019
 * Time: 23:04
 */

namespace SpotifyAPI;

session_start();


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

        if(!isset($_SESSION['access_token'])){
            echo 2;
            $this->getAccessToken();
        }
        else {
            $this->access_token = $_SESSION['access_token'];
            $this->authorization_code = $_SESSION['authorization_code'];
            $this->token_type = $_SESSION['token_type'];
            $this->token_expiration = $_SESSION['token_type'];
            $this->refresh_token = $_SESSION['refresh_token'];
        }


    }

    /**
     * @param mixed $authorization_code
     */
    public function setAuthorizationCode($authorization_code)
    {
        if(!isset($_SESSION['authorization_code'])) {
            $this->authorization_code = $authorization_code;
            $_SESSION['authorization_code'] = $authorization_code;
            $this->getAccessToken();
        }
    }

    public function restarSession(){
        session_destroy();
    }

    /**
     * @param mixed $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        $_SESSION['access_token'] = $access_token;
    }

    /**
     * @param mixed $token_type
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
        $_SESSION['token_type'] = $token_type;
    }

    /**
     * @param mixed $token_expiration
     */
    public function setTokenExpiration($token_expiration)
    {
        $this->token_expiration = time() + $token_expiration;
        $_SESSION['token_expiration'] = $token_expiration;
    }

    /**
     * @param mixed $refresh_token
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
        $_SESSION['refresh_token'] = $refresh_token;
    }

    public function generateAuthUrl(){
        $scopes = "user-read-recently-played user-library-modify playlist-read-private user-read-email 
        playlist-modify-public playlist-modify-private user-library-read playlist-read-collaborative 
        user-read-birthdate user-read-playback-state user-read-private user-modify-playback-state user-follow-read 
        user-top-read user-read-currently-playing user-follow-modify";
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

    public function getAlbumById($id, $market = null){
        $url = self::API_URL . 'albums/' . $id;
        if($market != null){
            $url .= "?market=$market";
        }
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getAlbumsTracksById($id, $limit = null, $offset = null, $market = null){
        $url = self::API_URL . 'albums/' . $id . '/tracks';

        if($market != null){
            $url .= "?limit=$limit&offset=$offset&market=$market";
        }
        else if ($offset != null){
            $url .= "?limit=$limit&offset=$offset";
        }
        else if($limit != null){
            $url .= "?limit=$limit";
        }

        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getAlbums($albumsIdsSeparated, $market = null){

        if(is_array($albumsIdsSeparated)) {
            $albumsIds = implode(",", $albumsIdsSeparated);
        }else{
            $albumsIds = $albumsIdsSeparated;
        }

        $url = self::API_URL . 'albums?ids=' . $albumsIds;

        if($market != null){
            $url .= $albumsIdsSeparated;
        }

        var_dump($url);

        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getArtistById($id){
        $url = self::API_URL . 'artists/' . $id;
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getArtistsAlbumsById($id){
        $url = self::API_URL . 'artists/' . $id . '/albums';
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getArtistsTopTracksById($id,$country){
        $url = self::API_URL . 'artists/' . $id . '/top-tracks?country=' . $country;
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getArtistsRelatedArtistsById($id){
        $url = self::API_URL . 'artists/' . $id . '/related-artists';
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getArtists(){
        $url = self::API_URL . 'artists';
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
    }

    public function getBrowseCategoriesById($id){
        $url = self::API_URL . 'browse/categories/' . $id;
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = [
            $auth
        ];
        return $this->get($url,$headers);
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


    /**
     * @param $url
     * @param $headers
     * @return json
     */
    private function post($url, $headers) : string{
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        return curl_exec($curl);
    }

    /**
     * @param $url
     * @param $headers
     * @return json
     */
    private function get($url, $headers) : string{
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        return curl_exec($curl);
    }

    /**
     * @param $url
     * @param $headers
     * @return json
     */
    private function delete($url, $headers) : string{
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        return curl_exec($curl);
    }

    /**
     * @param $url
     * @param $headers
     * @return json
     */
    private function put($url, $headers) : string{
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

        return curl_exec($curl);
    }



}