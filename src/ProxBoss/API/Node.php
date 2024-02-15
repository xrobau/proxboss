<?php

namespace ProxBoss\API;

use GuzzleHttp\Client;
use ProxBoss\API\Node\Qemu;

class Node extends Base
{
    protected Client $client;
    protected string $nodename;

    public function __construct(string $nodename)
    {
        $this->nodename = $nodename;
        $this->client = $this->getClient("nodes/$nodename/");
    }

    public function getNodeName()
    {
        return $this->nodename;
    }

    public function getAllQemuVms()
    {
        $res = $this->client->get("qemu");
        $j = json_decode((string) $res->getBody(), true);
        $retarr = ["byname" => [], "vmid" => []];
        foreach ($j['data'] as $v) {
            $vmid = $v['vmid'];
            $q = new Qemu("nodes/" . $this->nodename . "/qemu/$vmid/", $v, $this->nodename);
            $name = $q->getVmName();
            $retarr["byname"][$name] = $q;
            $retarr["vmid"][$vmid] = $q;
        }
        return $retarr;
    }
}
