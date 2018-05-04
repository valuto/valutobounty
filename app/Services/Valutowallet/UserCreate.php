<?php

namespace App\Services\Valutowallet;

use App\Services\Valutowallet\ValutowalletClient;

class UserCreate
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
     * Create the new user in Valutowallet.
     * 
     * @param  array   $data
     * @return boolean
     */
    public function store($data)
    {
        $response = $this->client->postWithAuth('user', $data);

        $body = json_decode($response->getBody());

        if (isset($body->error)) {
            throw new \Exception('Valutowallet API error: ' . $body->error . ' - ' . $body->message);
        }

        return $body;
    }
}
