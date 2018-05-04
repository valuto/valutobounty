<?php

namespace App\Services\Valutowallet;

use App\Services\Valutowallet\ValutowalletClient;

class UserCheck
{
    /**
     * Valutowallet API client.
     * 
     * @var ValutowalletClient
     */
    protected $client;

    /**
     * Construct service with dependencies.
     * 
     * @param ValutowalletClient $client
     */
    public function __construct(ValutowalletClient $client)
    {
        $this->client = $client;
    }

    /**
     * Does the user exist in Valutowallet?
     * 
     * @param  string  $email the email to check.
     * @return boolean
     */
    public function email($email)
    {
        $response = $this->client->getWithAuth('user?email=' . $email);
        
        $body = json_decode($response->getBody());

        if (isset($body->error)) {
            throw new \Exception('Valutowallet API error: ' . $body->error . ' - ' . $body->message);
        }

        return $body->exists;
    }
    
    /**
     * Does the user exist in Valutowallet?
     * 
     * @param  string  $username the username to check.
     * @return boolean
     */
    public function username($username)
    {
        $response = $this->client->getWithAuth('user?username=' . $username);
        
        $body = json_decode($response->getBody());

        if (isset($body->error)) {
            throw new \Exception('Valutowallet API error: ' . $body->error . ' - ' . $body->message);
        }

        return $body->exists;
    }
}
