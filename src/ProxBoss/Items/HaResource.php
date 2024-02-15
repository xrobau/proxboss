<?php

namespace ProxBoss\Items;

use ProxBoss\API\Node\Qemu;

class HaResource
{
    public static function fromApi(array $data): self
    {
        return new self($data);
    }

    private $groupname;
    private $vmid;
    private $data;
    private $qemu = false;

    private function __construct(array $data)
    {
        $this->groupname = $data['group'];
        $this->vmid = explode(':', $data['sid'])[1];
    }

    public function withQemu(Qemu $q): self
    {
        $this->qemu = $q;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getGroupName(): string
    {
        return $this->groupname;
    }

    public function getVmId(): string
    {
        return $this->vmid;
    }

    public function getQemu(): Qemu
    {
        if ($this->qemu) {
            return $this->qemu;
        }
        throw new \Exception("No qemu");
    }
}
