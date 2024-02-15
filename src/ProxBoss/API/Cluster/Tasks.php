<?php

namespace ProxBoss\API\Cluster;

use GuzzleHttp\Client;
use ProxBoss\API\Base;

class Tasks extends Base
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->getClient("cluster/");
    }

    /** @return Task[] */
    public function getAllTasks()
    {
        $retarr = [];
        $res = $this->client->get("tasks");
        $data = json_decode($res->getBody(), true);
        $tasks = $data['data'] ?? [];
        foreach ($tasks as $d) {
            $retarr[] = new Task($d);
        }
        return $retarr;
    }

    /** @return Task|false */
    public function getTaskById(string $upid)
    {
        $all = $this->getAllTasks();
        foreach ($all as $t) {
            if ($t->getUpId() == $upid) {
                return $t;
            }
        }
        return false;
    }

    /** @return Task[] */
    public function getTasksForVm(string $vmid, bool $onlyrunning = false)
    {
        $alltasks = $this->getAllTasks();
        $retarr = [];
        foreach ($alltasks as $t) {
            if ($t->getVmId() == $vmid) {
                if (!$onlyrunning || $t->isRunning()) {
                    $retarr[] = $t;
                }
            }
        }
        return $retarr;
    }

    /** @return Task[] */
    public function getRunningTasks($tasktype = false)
    {
        $alltasks = $this->getAllTasks();
        $retarr = [];
        foreach ($alltasks as $t) {
            if ($t->isRunning()) {
                if (!$tasktype || $t->getType() === $tasktype) {
                    $retarr[] = $t;
                }
            }
        }
        return $retarr;
    }
}
