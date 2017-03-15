<?php

namespace Multidanze\Facebook;

use Phalcon\DiInterface;
//use Facebook\Facebook;

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

    protected $session;

    /**
     * Create a new service provider instance.
     *
     * @param array $config
     */
    public function __construct($config, $session = null)
    {
        if (!$config->app_id || !$config->app_secret || $config->app_id == 'app_id_here' || $config->app_secret == 'app_secret_here') {
            throw new \RuntimeException('You need an app id and secret keys. Get one from <a href="https://developers.facebook.com/">developers.facebook.com</a>');
        }

        $this->app_id = $config->app_id;
        $this->app_secret = $config->app_secret;
        $this->default_graph_version = $config->default_graph_version;
        $this->permissions = $config->permissions;
        $this->token = $config->access_token;
        $this->session = $session;

        $this->facebook = $this->connectFacebook($this->app_id, $this->app_secret, $this->default_graph_version);

    }

    public function connectFacebook($app_id, $app_secret, $default_graph_version)
    {
        $connection = new \Facebook\Facebook(
            array(
                'app_id' => $app_id,
                'app_secret' => $app_secret,
                'default_graph_version' => $default_graph_version,
                'persistent_data_handler'=> 'session'
            )
        );

        return $connection;
    }

    public function loginURL($redirect_url = '')
    {
        $helper = $this->facebook->getRedirectLoginHelper();
        $loginUrl = $helper->getLoginUrl($redirect_url, $this->permissions);
        return $loginUrl;
    }

    public function login()
    {
        $helper = $this->facebook->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (isset($accessToken)) {
            // Logged in!
            $this->session->set('fb_access_token', (string) $accessToken);
            return $accessToken;
        } else {
            return false;
        }
    }

    public function getUser()
    {
        $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->facebook->get('/me', $token);
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        return $response->getGraphUser();
    }

    public function getUserPages()
    {
        $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->facebook->get('/me/accounts', $token);
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $graphEdge = $response->getGraphEdge();//getDecodedBody();
        return $graphEdge;//->getField('accounts');
    }

    public function checkLogin()
    {
        $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->facebook->get('/me', $token);
        } catch(\Exception $e) {
            return false;
        }
        /*catch(Facebook\Exceptions\FacebookResponseException $e) {
            return false;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            return false;
        }*/

        return $response;
    }







    /*
     *
     *     Pages functions
     *
    */


    public function getPostsFromPage($page_id, $limit = 100, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$page_id.'/posts?fields=id,message,link,full_picture,created_time&limit=' . $limit, $token);
        $graphEdge = $response->getGraphEdge();
        $result = array();
        foreach($graphEdge as $graphNode) {
            $post = array();
            $post['id'] = $graphNode->getField('id');
            $post['message'] = $graphNode->getField('message');
            $post['link'] = $graphNode->getField('link');
            $post['post_link'] = 'https://www.facebook.com/' . str_replace('_', '/posts/', $graphNode->getField('id'));
            $post['picture'] = $graphNode->getField('full_picture');
            $created_time = $graphNode->getField('created_time');
            $post['created_time'] = $created_time->format('d/m/Y H:i:s');
            $result[] = $post;
        }
        return $result;
    }

    public function getPostLikes($post_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$post_id.'/likes?fields=id,name', $token);
        $graphEdge = $response->getGraphEdge();
        $result = array();
        foreach($graphEdge as $graphNode) {
            $like = array();
            $like['id'] = $graphNode->getField('id');
            $like['name'] = $graphNode->getField('name');
            $result[] = $like;
        }
        return $result;
    }

    public function getPostComments($post_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$post_id.'/comments?fields=from,message', $token);
        $graphEdge = $response->getGraphEdge();
        $result = array();
        foreach($graphEdge as $graphNode) {
            $user = $graphNode->getField('from');
            $comment = array();
            $comment['user'] = array(
                'id' => $user->getField('id'),
                'name' => $user->getField('name')
            );
            $comment['message'] = $graphNode->getField('message');
            $result[] = $comment;
        }
        return $result;
    }

    public function getPageToken($page_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$page_id.'?fields=access_token', $token);
        $graphNode = $response->getGraphNode();
        return $graphNode->getField('access_token');
    }

    /*
        Writing functions
        post($message, $link = '') -> Write a post
        NB: The publish_pages permission is required for this function
    */
    public function postToPage($page_id, $message, $link = '', $token = null)
    {
        if ($token == null) {
            $token = $this->getPageToken($page_id);
        }

        $data = [
            'link' => $link,
            'message' => $message
        ];
        $response = $this->facebook->post('/'.$page_id.'/feed', $data, $token);
        $graphNode = $response->getGraphNode();
        return $graphNode->getField('id');
    }

    /*
        User related functions
        like($userAccessToken, $postId) -> Like a post
        comment($userAccessToken, $postId, $message) -> Comment a post
        NB: The publish_actions permission of the user is required for this functions
    */
    public function userLike($post_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $this->facebook->post('/'.$post_id.'/likes', array(), $token);
    }

    public function userComment($post_id, $message, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $data = [
            'message' => $message
        ];
        $this->facebook->post('/'.$post_id.'/comments', $data, $token);
    }






    public function getAlbumsFromPage($page_id, $limit = 100, $paging = null, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }
        if ($paging['page']) {
            $paging = '&' . $paging['dir'] . '=' . $paging['page'];
        } else {
            $paging = '';
        }

        $response = $this->facebook->get('/'.$page_id.'/albums?limit=' . $limit . $paging, $token);
        $body = $response->getDecodedBody();
        $graphEdge = $response->getGraphEdge();
        $result = array();
        $result['paging'] = array('next' => $body['paging']['cursors']['after'], 'prev' => $body['paging']['cursors']['before']);
        foreach($graphEdge as $graphNode) {
            $post = array();
            $post['id'] = $graphNode->getField('id');
            $post['name'] = $graphNode->getField('name');
            $post['link'] = 'https://www.facebook.com/' . str_replace('_', '/posts/', $graphNode->getField('id'));
            $post['picture'] = $this->getCoverPhotoFromAlbum($graphNode->getField('id'));
            $post['description'] = $this->getAlbumInfo($graphNode->getField('id'));
            $created_time = $graphNode->getField('created_time');
            $post['created_time'] = $created_time->format('d/m/Y H:i:s');
            $result['albums'][] = $post;
        }
        return $result;
    }

    public function getAlbumInfo($album_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$album_id . '/?fields=description', $token);
        $result = $response->getGraphNode();

        return $result->getField('description');
    }

    public function getCoverPhotoFromAlbum($album_id, $limit = 100, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$album_id .'/?fields=picture', $token);
        $result = $response->getGraphNode();

        $result = $result->getField('picture');
        return $result->getField('url');
    }

    public function getPhotosFromAlbum($album_id, $limit = 100, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$album_id .'/photos', $token);
        $graphEdge = $response->getGraphEdge();
        $results = array();
        foreach ($graphEdge as $graphNode) {
            $photo = array();
            $photo['id'] = $graphNode->getField('id');
            $photo['url'] = $this->getUrlFromPhoto($graphNode->getField('id'));
            $photo['name'] = $this->getNameFromPhoto($graphNode->getField('id'));
            $results[] = $photo;
        }

        return $results;
    }

    public function getUrlFromPhoto($photo_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$photo_id . '/picture', $token);
        $result = $response->getHeaders();
        return $result['Location'];
    }

    public function getNameFromPhoto($photo_id, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$photo_id, $token);
        $result = $response->getGraphNode();
        return $result->getField('name');
    }

    public function getEventsFromPage($page_id, $limit = 100, $paging = null, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        if ($paging['page']) {
            $paging = '&' . $paging['dir'] . '=' . $paging['page'];
        } else {
            $paging = '';
        }

        $response = $this->facebook->get('/'.$page_id.'/events?since='. strtotime('now') .'&limit=' . $limit . $paging, $token);
        $graphEdge = $response->getGraphEdge();
        $result = array();
        foreach($graphEdge as $graphNode) {
            $event = array();
            $event['id'] = $graphNode->getField('id');
            $event['description'] = $graphNode->getField('description');
            $event['name'] = $graphNode->getField('name');
            $place = $graphNode->getField('place');
            $location = $place->getField('location');
            $event['place'] = array(
                'name' => $place->getField('name'),
                'location' => array(
                    'city' => $location->getField('city'),
                    'country' => $location->getField('country'),
                    'latitude' => $location->getField('latitude'),
                    'longitude' => $location->getField('longitude'),
                    'street' => $location->getField('street'),
                    'zip' => $location->getField('zip')
                )
            );
            $event['picture'] = $this->getCoverPhotoFromEvent($graphNode->getField('id'));
            $start_time = $graphNode->getField('start_time');
            $event['start_time'] = $start_time->format('d/m/Y H:i');
            $end_time = $graphNode->getField('end_time');
            $event['end_time'] = $end_time ? $end_time->format('d/m/Y H:i') : '';
            $result[] = $event;
        }
        return $result;
    }

    public function getCoverPhotoFromEvent($event_id, $limit = 100, $token = null)
    {
        if ($token == null) {
            $token = $this->session->has('fb_access_token') ? $this->session->get('fb_access_token') : $this->token;
        }

        $response = $this->facebook->get('/'.$event_id .'/?fields=cover', $token);
        $result = $response->getGraphNode();

        $result = $result->getField('cover');
        return $result->getField('source');
    }


}
