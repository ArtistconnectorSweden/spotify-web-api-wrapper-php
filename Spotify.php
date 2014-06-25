<?php
/**************
 * Spotify Web-Api wrapper
 * Copyright (c) 2014 Alexander Forselius <alex@artistconnector.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *  
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Define base url
 */
define("SPOTIFY_API_ENDPOINT", "https://api.spotify.com/v1");
define("SPOTIFY_ACCOUNT_ENDPOINT", "https://accounts.spotify.com");
/**
 * Base class for Spotify
 * @author Alexander Forselius <alex@artistconnector.com>
 **/
class Spotify {
    /**   
     * Client ID for Spotify
     */
    private $clientID;
    
    public $redirectURI;
    public $refreshToken;
    public $accessToken;
    public $username;
    /**
     * Authorize the access token
     */
    public function authenticate($accessToken) {
        $this->accessToken = $accessToken;
        $a = $this->me();
        $this->username = $a['id'];
        $this->profile = $a;
        
        
    }
    
    public function refreshToken($refreshToken) {
        $code = $_GET['code']; // Get the code
        $state = isset($_GET['state']) ? $_GET['state'] : NULL;// Get the code
        $error = isset($_GET['error']) ? $_GET['error'] : NULL;
        if ($error) {
            throw new Exception (urldecode($error));
        }
        $response = NULL;
        // If no error execute this
        $auth = 'Authorization: Basic ' . base64_encode($this->clientID . ':' . $this->clientSecret);
        
        try {
            $response = $this->request('POST', SPOTIFY_ACCOUNT_ENDPOINT, '/api/token', 'text', array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken
            ), array(), $auth);
        } catch (Exception $e) {
            throw new Exception($e);   
        }
        
        return $response;
    }
    /**
     * Client secret for Spotify
     */
    private $clientSecret;
    
    public function isAuthenticated() {
        return $this->accessToken != NULL ? $this->accessToken : '';
    }
    /**
     * GET from the API
     */
    public function get($resource, $id = 0, $query = NULL) {
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/' . $resource, 'text', NULL);
        return $data;
    }
    /**
     * POST to the API
     */
    public function post($resource, $data) {
        $data = $this->request('POST', SPOTIFY_API_ENDPOINT, '/' . $resource . ($id ? '/' . $id . '' : ''), 'application/json',  $data, array());
        return $data;
    }
    /**
     * PUT to the API
     */
    public function put($resource, $id = 0, $data = NULL) {
        $data = $this->request('PUT', SPOTIFY_API_ENDPOINT, '/' . $resource . '/' . $id . '', 'application/json',  $data, array());
        return $data;
    }
    /**
     * Search
     */
    public function search($q, $type,  $limit = 10, $offset = 0) {
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/search?q=' . urlencode($q) . '&' . ($type ? 'type=' . implode(',', $type) : '') . 'limit=' . $limit . '&offset=' . $offset);
        
    }
    public function me() {
       
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/me', 'text');
       
        return $data;
    }
    /**
     * Return playlists for user.
     * Requires valid OAuth token
     * @param {String} $user_id
     */
    public function getPlaylistsForUser($user_id) {
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/users/' . $user_id . '/playlists', 'text');
        return $data;
    }
    
    
    /**
     * Gets the user profile
     */
    public function getUserProfile($user_id) {
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/users/' . $user_id, 'text');
        return $data;
    }
    
    /**
     * Creates a playlist
     * @param {String} $name The title
     * @param {Boolean} $public denotates if public
     * @param {Array} $tracks An array of spotify uris (optional)
     */
    public function createPlaylist($name, $public,  $tracks = NULL) {

        $data = array('name' => $name, 'public' => $public);
        $data = $this->request('POST', SPOTIFY_API_ENDPOINT, '/users/' . $this->username . '/playlists', 'application/json', $data);
      
        if ($tracks) {
            // If we have tracks add tracks aswell
            $uri = 'spotify:user:' . $this->username . ':playlist:' . $data['id'];
            $data = $this->addTracksToPlaylist($uri, $tracks);
        } else {
        }
        return $data;
        
        // Get the data and add songs
        
    }
    
    /**
     * Get playlist info by Spotify URI
     */
    public function getPlaylistInfo($uri) {
        $parts = explode(':', $uri);
        $user = $parts[2];
        $playlist = $parts[4];
        $uri = '/users/' . $user . '/playlists/' . $playlist;
        //var_dump($uri);
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, $uri, 'text', NULL);
        return $data;
    }
    /**
     * Get playlist info by Spotify URI
     * @param {String} $uri The Spotify URI to the playlist
     * @return Array an array of tracks
     */
    public function getTracksInPlaylist($uri, $offset = 0) {
        $parts = explode(':', $uri);
        $user = $parts[2];
        $playlist = $parts[4];
        $data = $this->request('GET', SPOTIFY_API_ENDPOINT, '/users/' . $user . '/playlists/' . $playlist . '/tracks&offset=' . $offset, 'json', NULL);
        return $data;
    }
    
    /***
     * Add tracks to a playlist
     ***/
    public function addTracksToPlaylist($uri, $tracks, $position = -1) {
        $parts = explode(':', $uri);
        $user = $parts[2];
        $playlist = $parts[4];
        $url = '/users/' . $user . '/playlists/' . $playlist . '/tracks';

        $data = $tracks;
        if ($position < 0) {
        $data = $this->request('POST', SPOTIFY_API_ENDPOINT, $url, 'application/json', $tracks);
        } else {
            $data = $this->request('POST', SPOTIFY_API_ENDPOINT, $url . '?uris=' . implode(',', $tracks) . '&position=' . $position, 'application/json');
            
        }
            
        return $data;
    }
    
    /**
     * Request against the spotify Web API
     * @param {String} $method The method to use
     * @param {String} $path The path
     * @param {String} $data (Optional) Data sent
     */
    public function request($method, $endpoint, $path, $type='text', $data = array(), $opt_headers = array(), $auth = FALSE) {
        $ch = curl_init();
        $url = $endpoint . $path;
      
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = $opt_headers;
        
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if (!$auth) {
            if ($this->accessToken)
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
           
        } else {
            $headers[] = ($auth);
        }
        if ($method && $method != 'GET') {
            
            if ($method == 'POST') {
                
                
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            if ($method == 'POST' || $method == 'PUT') {
                $post_data = $data;
                if ($type == 'text') {
                    if (is_array($post_data)) {
                        $post_data = http_build_query($post_data);
                    
                        curl_setopt($ch, CURLOPT_POST, strlen($post_data));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        
                    } else if(is_string($post_data)) {
                        
                        curl_setopt($ch, CURLOPT_POST, strlen($post_data));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                     
                    }
                }else if ($type == 'application/json') {
                    if ($post_data) {
                        
                        $post_data = json_encode($post_data);
                       
                        $headers[] = 'Accept: application/json';
                       
                        $headers[] = 'Content-type: application/json';
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                   
                    }
                }
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $response = curl_exec($ch);
        $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
        if ($result < 200 || $result > 299) {
          
            throw new Exception("Error. Code was " . curl_errno($ch) . ' ' . $result . ' ' . $response);
        }
        $data = json_decode($response, TRUE);
 
        return $data;
        
    }
    /**
     * Starts the authorization route
     * @param {Array<String>} $scope An array of scopes.
     * @return {Void} Redirects the user to the authorization flow
     */
    public function startAuthorization($scope) {
        header('location: ' . SPOTIFY_ACCOUNT_ENDPOINT . '/authorize?client_id=' . $this->clientID . '&response_type=code&redirect_uri=' .urlencode($this->redirectURI) . '&scope=' . implode('+', $scope));
       
    }
    /**
     * This method should be executed on the callback page.
     * @see {@url https://developer.spotify.com/web-api/authorization-guide/#authorization_code_flow}
     * @return {Array} $string accessToken
     */
    public function requestToken() {
        $code = $_GET['code']; // Get the code
        $state = isset($_GET['state']) ? $_GET['state'] : NULL; // Get the state
        $error = isset($_GET['error']) ? $_GET['error'] : NULL;
        if ($error) {
            throw new Exception (urldecode($error));
        }
        $response = NULL;
        // If no error execute this
        try {
            $response = $this->request('POST', SPOTIFY_ACCOUNT_ENDPOINT, '/api/token', 'text', array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectURI,
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret
            ));
            
            
        } catch (Exception $e) {
            throw new Exception($e);   
        }
        
        return $response;
        
    }
    /**
     * Creates a new instance of the Spotify Web API 
     * @param {String} $clientID The client ID
     * @param {String} $clientSecret the client secret
     * @param {String} $redirectURI the redirect_uri
     * @param {String} $accessToken The access token (optional)
     * @constructor
     * @function
     */
     function __construct($clientID, $clientSecret, $redirectURI, $accessToken = NULL) {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->redirectURI = $redirectURI;
        $this->accessToken = $accessToken;
        
    }
        
}

/**
 * Base model for endpoint
 * 
 * 
 */
class SpotifyAPIModel {
    public $api;
    public $model = '';
    public function __construct($api, $model) {
        $this->api = $api;
        $this->model = $model;
    }
}
/**
 * Resembles the Spotify User endpoint
 */
class SpotifyUser extends SpotifyAPIModel {
    public function getUser($id) {
        $user = $this->api->get('users', $id);
        return $user;
    }
    public function __construct($api) {
      parent::__construct($api, 'users');
      
    }   
    
}
