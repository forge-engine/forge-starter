<?php

declare(strict_types=1);

namespace Forge\Core\Http;

class Response
{
    private array $cookies = [];

    public function __construct(
        private string $content,
        private int    $status = 200,
        private array  $headers = []
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("$name: $v", false);
                }
            } else {
                header("$name: $value");
            }
        }

        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie->name,
                $cookie->value,
                [
                    'expires' => $cookie->expires,
                    'path' => $cookie->path,
                    'domain' => $cookie->domain,
                    'secure' => $cookie->secure,
                    'httponly' => $cookie->httponly,
                    'samesite' => $cookie->samesite
                ]
            );
        }

        echo $this->content;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function setStatusCode(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setCookie(Cookie $cookie): self
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    public function withCookie(Cookie $cookie): self
    {
        $this->cookies[] = $cookie;
        return $this;
    }
}
