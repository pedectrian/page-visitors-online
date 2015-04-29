<?php
/**
 * Created by PhpStorm.
 * User: pedectrian
 * Date: 30.04.15
 * Time: 2:08
 */
class GAService
{


    public function __construct( Google_Client $client ){
        $this->client = $client;
        $this->init();
    }

    private function init(){
//        $this->client->setClientId( CLIENT_ID );
//        $this->client->setClientSecret(CLIENT_SECRET);
//        $this->client->setDeveloperKey(API_KEY);
//        $this->client->setRedirectUri('http://theins.ru/oauth2callback');
//        $this->client->setScopes(array('https://www.googleapis.com/auth/analytics'));
    }

    public function isLoggedIn(){
        if (isset($_SESSION['token'])) {
            $this->client->setAccessToken($_SESSION['token']);
            return true;
        }

        return $this->client->getAccessToken();
    }

    public function login( $code ){     $this->client->authenticate($code);
        $token = $this->client->getAccessToken();
        $_SESSION['token'] = $token;

        return $token;
    }

    public function getLoginUrl(){
        $authUrl = $this->client->createAuthUrl();
        return $authUrl;
    }
}