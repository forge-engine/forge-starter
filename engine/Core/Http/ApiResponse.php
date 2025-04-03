<?php

declare(strict_types=1);

namespace Forge\Core\Http;

final class ApiResponse extends Response
{
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = [],
        private array $meta = []
    ) {
        parent::__construct(
            json_encode(['data' => $data, 'meta' => $this->meta]),
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers)
        );
    }

    public function create(mixed $data, int $status = 200): self
    {
        return new self($data, $status);
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        $this->setContent(json_encode(['data' => json_decode($this->getContent(), true)['data'], 'meta' => $this->meta]));

        return $this;
    }
}
