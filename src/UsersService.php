<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\StreamWrapper;
use JsonMachine\Items;

/**
 * A simple UsersService querying MockAPI.
 */
class UsersService
{

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://640b49a381d8a32198dff1a4.mockapi.io']);
    }

    public function listLarge()
    {
        return $this->client->getAsync("/users")->then(function ($response) {
            /**
             * Safe way to query a large amount of JSON using 'json-machine' by streaming the data.
             */
            $stream = StreamWrapper::getResource($response->getBody());
            return Items::fromStream($stream);
        });
    }

    public function list(int $page = 1, int $limit = 10)
    {
        return $this->client->getAsync("/users?page=$page&limit=$limit")->then(function ($response) {
            return json_decode($response->getBody());
        });
    }

    public function update(int $id, $data)
    {
        return $this->client->putAsync("/users/$id")->then(function ($response) {
            return json_decode($response->getBody());
        });
    }
}
