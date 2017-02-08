<?php

namespace Multidanze\Facebook;

use Phalcon\DiInterface;
use Facebook\Facebook;

/**
 * Class Facebook
 *
 * @package Multidanze\Facebook
 */
class Facebook
{
    /**
     * @var facebook
     */
    protected $facebook;

    /**
     * @var app_id
     */
    protected $app_id;

    /**
     * @var app_secret
     */
    protected $app_secret;

    /**
     * @var default_graph_version
     */
    protected $default_graph_version;

    /**
     * @array permissions
     */
    protected $permissions;

    /**
     * @var token
     */
    protected $token;

    /**
     * Create a new service provider instance.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        if (!$config->app_id || !$config->app_secret || $config->add_id == 'app_id_here' || $config->app_secret == 'app_secret_here') {
            throw new \RuntimeException('You need an app id and secret keys. Get one from <a href="https://developers.facebook.com/">developers.facebook.com</a>');
        }

        $this->app_id = $config->app_id;
        $this->app_secret = $config->app_secret;
        $this->default_graph_version = $config->default_graph_version;
        $this->permissions = $config->permissions;
        $this->token = $config->access_token;

        $this->facebook = $this->connectFacebook($this->app_id, $this->app_secret, $this->default_graph_version);

    }

    public function connectFacebook($app_id, $app_secret, $default_graph_version)
    {
        $connection = new Facebook(
            array(
                'app_id' => $app_id,
                'app_secret' => $app_secret,
                'default_graph_version' => $default_graph_version,
                'persistent_data_handler'=> 'session'
            )
        );

        return $connection;
    }

    public function checkLogin()
    {
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->facebook->get('/me', $this->token);
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            return false;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            return false;
        }

        return true;
    }

}
