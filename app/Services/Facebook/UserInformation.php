<?php

namespace App\Services\Facebook;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

class UserInformation
{
    /**
     * Get Facebook user.
     * 
     * @param  string $accessToken
     * @return \Facebook\GraphNodes\GraphUser
     */
    public function get($accessToken, $arguments = [])
    {
        $fb = new Facebook([
            'app_id'                => config('facebook.app_id'),
            'app_secret'            => config('facebook.secret'),
            'default_graph_version' => 'v2.12',
            //'default_access_token' => '{access-token}', // optional
        ]);

        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $fb->get('/me?' . http_build_query($arguments), $accessToken);
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            return 'Graph returned an error: ' . $e->getMessage();
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            return 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        return $response->getGraphUser();
    }
}
