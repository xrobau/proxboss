<?php

namespace ProxBoss\API\Cluster\HA;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ProxBoss\API\Base;
use ProxBoss\Items\Group;

class Groups extends Base
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->getClient("cluster/ha/groups");
    }

    /**
     * @return Group[]
     * @throws GuzzleException 
     */
    public function getAll(): array
    {
        $retarr = [];
        $res = $this->client->get("");
        $data = json_decode($res->getBody(), true);
        foreach ($data['data'] as $d) {
            $g = Group::fromApi($d);
            $retarr[$g->getName()] = $g;
        }
        return $retarr;
    }
}
