<?php /** @noinspection ALL */

/**
 * Created by PhpStorm.
 * User: nikni
 * Date: 15/03/2019
 * Time: 23:04
 */

namespace SpotifyAPI;

use mysql_xdevapi\Exception;

session_start();


class SpotifyAPI
{

    const API_URL = "https://api.spotify.com/v1/";
    const ACCOUNTS_URL = "https://accounts.spotify.com/";
    const REDIRECT_URL = "http://localhost/teste.php";
    const POST = "POST";
    const GET = "GET";
    const DELETE = "DELETE";
    const PUT = "PUT";
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

        $headers = $this->getHeaders("client_secret", "Content-Type: application/x-www-form-urlencoded");

        $response = $this->post($url,$headers);
        $response = json_decode($response,true);
        $this->setAccessToken($response['access_token']);
        $this->setTokenType($response['token_type']);
        $this->setTokenExpiration($response['expires_in']);

    }

    public function refreshToken(){
        $url = self::ACCOUNTS_URL . 'api/token';
        $headers = $this->getHeaders("client_secret", "Content-Type: application/x-www-form-urlencoded");
        $options = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
        ];

        $response = $this->request($url,POST, $options, $headers);
        var_dump($response);

    }

    public function getAlbumById($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'albums/' . $id;
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url, self::GET, $options, $headers);
        return $response;
    }

    public function getAlbumsTracksById($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'albums/' . $id . '/tracks';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url, self::GET, $options, $headers);
        return $response;
    }

    public function getAlbums($albumsIdsSeparated, $options = []){
        $options = (array) $options;
        $options['ids'] = implode(',', (array) $albumsIdsSeparated);
        $url = self::API_URL . 'albums';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getArtistById($id){
        $options = (array) $options;
        $url = self::API_URL . 'artists/' . $id;
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, [],$headers);
        return $response;
    }

    public function getArtistsAlbumsById($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'artists/' . $id . '/albums';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getArtistsTopTracksById($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'artists/' . $id . '/top-tracks';
        if($options['country'] == null){
            throw new \Exception("Missing required param: country");
        }
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getArtistsRelatedArtistsById($id){
        $url = self::API_URL . 'artists/' . $id . '/related-artists';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, [],$headers);
        return $response;
    }

    public function getArtists($artistsIdsSeparated, $options = []){
        $url = self::API_URL . 'artists';
        $options = (array) $options;
        $options['ids'] = implode(',', (array) $artistsIdsSeparated);
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, [],$headers);
        return $response;
    }

    public function getBrowseCategoriesById($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'browse/categories/' . $id;
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getBrowserCategoryPlaylist($id, $options = []){
        $options = (array) $options;
        $url = self::API_URL . 'browse/categories/' . $id . '/playlists';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getBrowserCategories($options = []){
        $options = (array) $options;
        $url =  self::API_URL . 'browse/categories';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getBrowserFeaturedPlaylists($options = []){
        $options = (array) $options;
        $url =  self::API_URL . 'browse/featured-playlists';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getBrowserNewReleases($options = []){
        $options = (array) $options;
        $url =  self::API_URL . 'browse/new-releases';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
    }

    public function getBrowserRecomendations($options = []){
        $options = (array) $options;
        $url =  self::API_URL . 'browse/recommendations';
        $headers = $this->getHeaders("access_token");
        $response = $this->request($url,self::GET, $options,$headers);
        return $response;
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
        $headers = $this->getHeaders("access_token");
        var_dump($url);
        return $this->get($url,$headers);
    }

    public function getMeProfile(){
        $url = self::API_URL . 'me';
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = $this->getHeaders("access_token");
        return $this->get($url,$headers);
    }

    public function getUsersProfile($id){
        $url = self::API_URL . 'users/' . $id;
        $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
        $headers = $this->getHeaders("access_token");
        return $this->get($url,$headers);
    }

    /**
     * @param $typeAuth
     * @param null $aditionalHeaders
     * @return array|null
     */
    private function getHeaders($typeAuth, $aditionalHeaders = null){
        switch($typeAuth){

            case "client_secret":
                $auth = "Authorization: Basic " . base64_encode($this->client_id . ':' . $this->client_secret);
                $headers = [$auth];
                break;
            case "access_token":
                $auth = "Authorization: " . $this->token_type . " " . $this->access_token;
                $headers = [$auth];
                break;
            default:
                return null;
        }

        if($aditionalHeaders != null){
            if(is_array($aditionalHeaders)){
                foreach($aditionalHeaders as $value){
                    array_push($headers, $value);
                }
            }
            else{
                array_push($headers, $aditionalHeaders);
            }
        }
        return $headers;
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

    private function request($url, $method, $params, $headers){
        $curl = curl_init();

        if(is_array($params) || is_object($params)){
            $params = http_build_query($params);
        }

        switch($method){

            case self::POST:
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case self::DELETE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case self::PUT:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            default:
                if($params != null){
                    $url .= '?' . $params;
                }
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        $response = curl_exec($curl);
        if (curl_error($curl)) {
            throw new \Exception('cURL transport error: ' . curl_errno($curl) . ' ' .  curl_error($curl ));
        }


        return $response;

    }



}