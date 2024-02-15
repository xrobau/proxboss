<?php

namespace ProxBoss\Items;

class PendingChanges
{
    public static function fromApi(array $data): self
    {
        return new self($data);
    }

    private $data;
    private $changes = [];
    private $pendingchanges = false;

    private function __construct(array $data)
    {
        $this->data = $data;
        foreach ($data as $setting) {
            if (!empty($setting['pending'])) {
                $this->changes[$setting['key']] = $setting;
                $this->pendingchanges = true;
            }
        }
    }

    public function areChangesPending(): bool
    {
        return $this->pendingchanges;
    }

    public function getPendingChanges(): array
    {
        return $this->changes;
    }
}
