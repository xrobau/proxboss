<?php

namespace ProxBoss\API;

class Nodes extends Base
{

    protected $allnodes = [];

    /**
     * All the nodes
     *
     * @param boolean $refresh
     * @return Nodes[]
     */
    public function getAllNodes($refresh = false)
    {
        if ($refresh) {
            $this->allnodes = [];
        }
        if (!$this->allnodes) {
            $res = $this->getClient()->get('nodes');
            $data = json_decode($res->getBody(), true);
            foreach ($data['data'] as $r) {
                $nodename = $r['node'];
                $n = new Node($nodename);
                $this->allnodes[$nodename] = $n;
            }
        }
        return $this->allnodes;
    }
}
