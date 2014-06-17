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
    public $accessToken;
    
    /**
     * Client secret for Spotify
     */
    private $clientSecret;
    
    /**
     * Request against the spotify Web API
     * @param {String} $method The method to use
     * @param {String} $path The path
     * @param {String} $data (Optional) Data sent
     */
    public function request($method, $endpoint, $path, $type='text', $data = array(), $opt_headers = array()) {
        $ch = curl_init();
        $url = $endpoint . $path;
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = $opt_headers;
        $headers[] = 'Accept: application/json';
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($method && $method != 'GET') {
            
            if ($method == $POST) {
                
                
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            if ($method == 'POST' || $method == 'PUT') {
                $post_data = $data;
                if ($type == 'text') {
                    $post_data = http_build_query($post_data);
                    curl_setopt($ch, CURLOPT_POST, count($post_data));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                }else if ($type == 'application/json') {
                     $post_data = json_encode($post_data);
                    $headers[] = 'Content-type: application/json';
                    $headers[] = 'Content-Length: ' . count($post_data);
                }
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result != 200) {
            throw new Exception("Error. Code was " .$response);
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
        header('location: ' . SPOTIFY_ACCOUNT_ENDPOINT . '/authorize?client_id=' . $this->clientID . '&response_type=code&redirect_uri=' .urlencode($this->redirectURI) . '&scope=' . implode(',', $scope));
       
    }
    /**
     * This method should be executed on the callback page.
     * @see {@url https://developer.spotify.com/web-api/authorization-guide/#authorization_code_flow}
     * @return {Array} $string accessToken
     */
    public function requestToken() {
        $code = $_GET['code']; // Get the code
        $state = $_GET['state']; // Get the state
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