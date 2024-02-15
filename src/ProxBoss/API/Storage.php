<?php

namespace ProxBoss\API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ProxBoss\Items\DataStore;

class Storage extends Base
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->getClient("storage");
    }

    /**
     * @return DataStore[]
     * @throws GuzzleException 
     */
    public function getAll(): array
    {
        $res = $this->client->get("");
        $data = json_decode($res->getBody(), true);
        $retarr = [];
        foreach ($data['data'] as $arr) {
            $retarr[] = DataStore::fromApi($arr);
        }
        return $retarr;
    }
}
