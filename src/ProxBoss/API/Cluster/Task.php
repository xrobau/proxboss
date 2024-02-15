<?php

namespace ProxBoss\API\Cluster;

use ProxBoss\API\Node;
use ProxBoss\Items\NodeTask;

class Task
{
    public array $t;
    private $node = false;

    public function __construct(array $task)
    {
        $this->t = $task;
    }

    public function getUpId(): string
    {
        return $this->t['upid'];
    }

    public function getNodeName(): string
    {
        return $this->t['node'];
    }

    public function getNode(): Node
    {
        if (!$this->node) {
            $this->node = new Node($this->getNodeName());
        }
        return $this->node;
    }

    public function getNodeTask(): NodeTask
    {
        return new NodeTask($this->getNode(), $this->getUpId());
    }

    public function getVmId(): string
    {
        return $this->t['id'];
    }

    public function isRunning(): bool
    {
        return (empty($this->t['endtime']));
    }

    public function getType(): string
    {
        return $this->t['type'];
    }

    public function getRunLength(): int
    {
        $end = $this->t['endtime'] ?? time();
        return (int) ($end - $this->t['starttime']);
    }
}
