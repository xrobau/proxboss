<?php

namespace ProxBoss\Items;

class NetworkInterface
{
    public static function fromQemu(string $name, string $params)
    {
        $n = new self($name);
        return  $n->usingParams($params);
    }

    private $intname;
    private $params;

    private function __construct(string $name)
    {
        $this->intname = $name;
        return $this;
    }

    private function usingParams($params)
    {
        $vals = explode(',', $params);
        foreach ($vals as $s) {
            list($k, $v) = explode("=", $s, 2);
            $this->params[$k] = $v;
        }
        return $this;
    }

    public function getMac(): string
    {
        return strtolower(str_replace([':'], '', $this->params['virtio']));
    }

    public function getIntName(): string
    {
        return $this->intname;
    }

    public function getBridgeName(): string
    {
        return $this->params['bridge'] ?? "ERROR";
    }

    public function usesBridge(string $bridgename): bool
    {
        return $this->getBridgeName() == $bridgename;
    }
}
