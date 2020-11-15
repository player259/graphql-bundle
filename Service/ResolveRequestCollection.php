<?php

declare(strict_types=1);

namespace Player259\GraphQLBundle\Service;

class ResolveRequestCollection implements \Iterator, \Countable
{
    /**
     * @var ResolveRequest[]
     */
    protected $data;

    /**
     * @var int
     */
    protected $position;

    public function __construct()
    {
        $this->data = [];
        $this->position = 0;
    }

    public function add(ResolveRequest $item): self
    {
        $this->data[] = $item;

        return $this;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): ResolveRequest
    {
        return $this->data[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    public function count()
    {
        return count($this->data);
    }

    /**
     * @return ResolveRequest[]
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
