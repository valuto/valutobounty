<?php

namespace App\Services\Valutowallet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Cache, Config;

class ValutowalletClient
{
    /**
     * HTTP client.
     * 
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * Base URL.
     * 
     * @var string
     */
    protected $baseUrl;

    /**
     * Client ID
     * 
     * @var string
     */
    protected $clientId;

    /**
     * Client secret.
     * 
     * @var string
     */
    protected $clientSecret;

    /**
     * Retry
     * 
     * @var boolean
     */
    const RETRY = TRUE;

    /**
     * First try
     * 
     * @var boolean
     */
    const FIRSTTRY = FALSE;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->baseUrl      = Config::get('valutowallet.baseurl');
        $this->clientId     = Config::get('valutowallet.client_id');
        $this->clientSecret = Config::get('valutowallet.client_secret');

        // Create a client with a base URI
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
    }

    /**
     * GET request.
     * 
     * @param  string $url
     * @param  array  $headers
     * @return 
     */
    public function get($url, $headers = [])
    {
        $response = $this->client->request('GET', $url, [
            'headers' => $headers,
            'verify' => Config::get('valutowallet.verifypeer'),
        ]);

        return $response;
    }

    /**
     * POST request.
     * 
     * @param  string $url
     * @param  array  $fields
     * @param  array  $headers
     * @return 
     */
    public function post($url, $fields, $headers = [])
    {
        $response = $this->client->request('POST', $url, [
            'headers' => $headers,
            'form_params' => $fields,
            'verify' => Config::get('valutowallet.verifypeer'),
        ]);

        return $response;
    }

    /**
     * GET request with authorization.
     * 
     * @param  string   $url
     * @param  RETRY|FIRSTTRY  $attempt  is this the first try or a 
     *                                   retried attempt of the request?
     * @return Response
     */
    public function getWithAuth($url, $attempt = self::FIRSTTRY)
    {
        try {
            $response = $this->get($url, $this->getDefaultHeaders());
        } catch (ClientException $e) {

            $statuscode = $e->getResponse()->getStatusCode();

            // Retrieve new access token and try again.
            if ($statuscode === 401 && $attempt === self::FIRSTTRY) {
                $this->newAccessToken();
                return $this->getWithAuth($url, self::RETRY);
            } else {
                return $e->getResponse();
            }

        }

        return $response;
    }

    /**
     * POST request with authorization.
     * 
     * @param  string   $url
     * @param  array  $fields
     * @param  RETRY|FIRSTTRY  $attempt  is this the first try or a 
     *                                   retried attempt of the request?
     * @return Response
     */
    public function postWithAuth($url, $fields, $attempt = self::FIRSTTRY)
    {
        try {
            $response = $this->post($url, $fields, $this->getDefaultHeaders());
        } catch (ClientException $e) {

            $statuscode = $e->getResponse()->getStatusCode();

            // Retrieve new access token and try again.
            if ($statuscode === 401 && $attempt === self::FIRSTTRY) {
                $this->newAccessToken();
                return $this->postWithAuth($url, $fields, self::RETRY);
            } else {
                return $e->getResponse();
            }

        }

        return $response;
    }

    /**
     * Get access token (from memory or retrieve new if expired).
     * 
     * @return string the access token.
     */
    protected function getAccessToken()
    {
        // No access token found in memory.
        if ( ! Cache::has('valutowallet.access_token')) {
            return $this->newAccessToken();
        }

        // The access token from memory is expired or will expire soon.
        if (time()-200 > Cache::get('valutowallet.access_token_expiration')) {
            return $this->newAccessToken();
        }

        // Return access token from memory.
        return Cache::get('valutowallet.access_token');
    }

    /**
     * Retrieve new access token from Valutowallet API.
     * 
     * @return string the access token.
     */
    protected function newAccessToken()
    {
        $response = $this->post('access-token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'usercheck usercreate',
        ]);

        $body = json_decode($response->getBody());

        if (isset($body->error)) {
            throw new \Exception('Valutowallet API error: ' . $body->error . ' - ' . $body->message);
        }

        $this->saveAccessToken($body->access_token, (time() + (int)$body->expires_in));

        return $body->access_token;
    }

    /**
     * Save access token to memory.
     * 
     * @param string $token      the access token to save in memory
     * @param int    $expiration expiration time for the access token.
     */
    protected function saveAccessToken($token, $expiration)
    {
        Cache::forever('valutowallet.access_token', $token);
        Cache::forever('valutowallet.access_token_expiration', $expiration);
    }

    /**
     * Get headers array.
     * 
     * @return array
     */
    public function getDefaultHeaders()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];

        return $headers;
    }
}
