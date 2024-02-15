<?php

namespace ProxBoss\API\Cluster\HA;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ProxBoss\API\Base;
use ProxBoss\Items\HaResource;

class Resources extends Base
{
    protected Client $client;
    private $cache = [];
    private $qemu = [];

    public function __construct()
    {
        $this->client = $this->getClient("cluster/ha/resources");
    }

    public function usingQemu(array $vms): self
    {
        $this->cache = [];
        $this->qemu = $vms;
        return $this;
    }

    /**
     * @return HaResource[]
     * @throws GuzzleException 
     */
    public function getAll(): array
    {
        if (empty($this->cache)) {
            $this->cache = [];
            $res = $this->client->get("");
            $data = json_decode($res->getBody(), true);
            foreach ($data['data'] as $d) {
                $g = HaResource::fromApi($d);
                if (!empty($this->qemu[$g->getVmId()])) {
                    $g->withQemu($this->qemu[$g->getVmId()]);
                }
                $this->cache[] = $g;
            }
        }
        return $this->cache;
    }

    public function getAllInGroup(string $group): array
    {
        $retarr = [];
        $all = $this->getAll();
        foreach ($all as $r) {
            if ($r->getGroupName() == $group) {
                $retarr[] = $r;
            }
        }
        return $retarr;
    }

    public static function getResourceUsage(array $res)
    {
        $count = 0;
        $mem = 0;
        $cores = 0;
        foreach ($res as $har) {
            /** @var HaResource $har */
            $count++;
            $mem = $mem + $har->getQemu()->getMem();
            $cores = $cores + $har->getQemu()->getCpuCount();
        }
        return ["count" => $count, "mem" => $mem, "cores" => $cores];
    }
}
