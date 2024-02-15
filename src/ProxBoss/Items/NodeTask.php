<?php

namespace ProxBoss\Items;

use GuzzleHttp\Client;
use ProxBoss\API\Node;

class NodeTask
{
    private Node $node;
    private Client $client;
    private string $upid;

    public function __construct(Node $node, string $upid)
    {
        $this->node = $node;
        $this->upid = $upid;
        $this->client = $this->node->getClient("nodes/" . $this->node->getNodeName() . "/tasks/");
    }

    public function getLog()
    {
        $retarr = [];
        $res = $this->client->get(urlencode($this->upid) . "/log?limit=999999");
        $j = json_decode((string) $res->getBody(), true);
        foreach ($j['data'] as $row) {
            $retarr[$row['n']] = $row['t'];
        }
        return $retarr;
    }

    public function getLastLogLine(): string
    {
        $all = $this->getLog();
        return array_pop($all);
    }
}
