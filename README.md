spotify-web-api-wrapper-php
===========================

PHP wrapper for the Spotify web API

Work in progress.

# Getting started

Let say you have an php site in this folder, 

Place the Spotify.php in your vendor, and create a basic api connection header

spotify.inc.php

    <?php
    require_once '../vendor/Spotify.php';
    
    // Instansiate the SpotifyAPI class
    $spotifyApi = new Spotify('CLIENT_ID', 'CLIENT_SECRET', 'http://joyify.se/callback.php');
    ?>
    
Then create a login page, login.php

    <?php
    require_once 'spotify.inc.php';
    // We choose the permission scope to ask allowance for
    $scope = array(
        'user-read-email', 
        'playlist-modify', 
        'playlist-read-private', 
        'playlist-modify-private', 
        'user-read-private', 
        'user-read-email');
        
    // Then start the authorization flow by redirecting the user to the login page
    $result = $spotifyApi->startAuthorization($scope);
    $spotifyApi->startAuthorization();
    ?>
   
Let say you created a callback.php in the same directory as the login.php, just create a callback.php there (assume the pages we create can be reached at http://localhost/)

    <?php
    // Get the access token 
    $token = $spotifyApi->requestToken();
    
    // Store the refresh token and access token in your database
    $access_token = $token['access_token'];
    $refresh_token = $token['refresh_token'];
    // Then 'install' the token into the class instance
    $spotifyApi->authenticate($token['access_token']);
    ?>
    


