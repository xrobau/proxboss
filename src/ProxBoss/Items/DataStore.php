<?php

namespace ProxBoss\Items;

class DataStore
{
    public static function fromApi(array $data): self
    {
        return new self($data);
    }

    private $name;
    private $data;

    private function __construct(array $data)
    {
        $this->name = $data['storage'];
        $this->data = $data;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function isNfs(): bool
    {
        return $this->getType() === "nfs";
    }

    public function getNfsServer(): string
    {
        if (!$this->isNfs()) {
            throw new \Exception("This is not NFS");
        }
        return $this->data['server'];
    }
}
