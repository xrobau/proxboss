<?php

namespace ProxBoss\Items;

class Group
{
    public static function fromApi(array $data): self
    {
        return new self($data);
    }

    private $name;
    private $data;
    private $nodearr = [];

    private function __construct(array $data)
    {
        $this->name = $data['group'];
        $tmparr = explode(',', $data['nodes']);
        foreach ($tmparr as $npri) {
            list($name, $pri) = explode(':', $npri);
            if ($pri < 1) {
                throw new \Exception("WTF is the go with $npri");
            }
            if (empty($this->nodearr[$pri])) {
                $this->nodearr[$pri] = [$name];
            } else {
                $this->nodearr[$pri][] = $name;
            }
        }
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

    public function getPrimaryNodes(): array
    {
        return [];
    }
}
