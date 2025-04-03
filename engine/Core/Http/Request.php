<?php

declare(strict_types=1);

namespace Forge\Core\Http;

use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Flash;
use Forge\Exceptions\ValidationException;
use Forge\Core\Http\UploadedFile;

final class Request
{
    private array $headers;
    private string $uri;

    public function __construct(
        public array  $queryParams,
        public array  $postData,
        public array  $serverParams,
        public string $requestMethod,
        public array  $cookies,
        private ?string $query = null
    ) {
        $this->headers = $this->parseHeadersFromServerParams($serverParams);
        $this->cookies = $cookies;
        $this->query = $queryParams['query'] ?? null;
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $postData = $_POST;
        $cookies = self::parseCookies($_COOKIE);

        if ($method === "POST") {
            if (isset($_POST["_method"])) {
                $spoofedMethod = strtoupper($_POST["_method"]);
                if (in_array($spoofedMethod, ["PUT", "PATCH", "DELETE"])) {
                    $method = $spoofedMethod;
                }
            }

            if (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] === "application/json") {
                $rawBody = file_get_contents("php://input");
                $jsonData = json_decode($rawBody, true);
                if (is_array($jsonData)) {
                    $postData = array_merge($postData, $jsonData);
                }
            }
        }

        return new self($_GET, $postData, $_SERVER, $method, $cookies);
    }

    private static function parseCookies(array $cookieData): array
    {
        $cookies = [];
        foreach ($cookieData as $name => $value) {
            $cookies[$name] = new Cookie($name, $value);
        }
        return $cookies;
    }
    /**
     * @return array<<missing>,string>
     */
    private static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    public function getUri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Checks if a request header exists.
     * Header name is case-insensitive.
     */
    public function hasHeader(string $name): bool
    {
        $normalizedName = strtolower($name); // Normalize to lowercase for case-insensitive check
        return isset($this->headers[$normalizedName]);
    }

    public function getClientIp(): string
    {
        $ip = $this->serverParams['REMOTE_ADDR'] ?? '0.0.0.0';

        if (isset($this->serverParams['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $this->serverParams['HTTP_X_FORWARDED_FOR']);
            $ip = trim(end($forwardedIps));
        } elseif (isset($this->serverParams['HTTP_X_REAL_IP'])) {
            $ip = $this->serverParams['HTTP_X_REAL_IP'];
        } elseif (isset($this->serverParams['HTTP_CLIENT_IP'])) {
            $ip = $this->serverParams['HTTP_CLIENT_IP'];
        }

        return $this->validateIp($ip) ? $ip : '0.0.0.0';
    }

    private function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Gets a request header value.
     * Header name is case-insensitive.
     *
     * @param string $name Header name
     * @param string|null $default Default value to return if header is not found
     * @return string|null Header value or $default if not found
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalizedName = strtolower($name); // Normalize to lowercase
        return $this->headers[$normalizedName] ?? $default;
    }

    /**
     * Gets all request headers as an associative array.
     * Header names are normalized to lowercase.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Parses headers from the $_SERVER array.
     * Normalizes header names to lowercase and removes 'HTTP_' prefix.
     *
     * @param array $serverParams The $_SERVER array
     * @return array<string, string>
     */
    private function parseHeadersFromServerParams(array $serverParams): array
    {
        $headers = [];
        foreach ($serverParams as $key => $value) {
            if (str_starts_with($key, "HTTP_")) {
                $name = strtolower(str_replace("HTTP_", "", $key));
                $name = str_replace("_", "-", $name);
                $headers[$name] = $value;
            } elseif ($key === "CONTENT_TYPE") {
                $headers["content-type"] = $value;
            } elseif ($key === "CONTENT_LENGTH") {
                $headers["content-length"] = $value;
            }
        }
        return $headers;
    }

    public function validate(array $rules, array $customMessages = []): void
    {
        $errors = [];

        foreach ($rules as $field => $ruleset) {
            $value = $this->postData[$field] ?? null;

            foreach ($ruleset as $rule) {
                [$ruleName, $param] = explode(":", $rule . ":");

                if ($ruleName === "required" && empty($value)) {
                    $errors[$field][] = $this->formatMessage($customMessages, "required", $field);
                }

                if ($ruleName === "min" && strlen($value) < (int)$param) {
                    $errors[$field][] = $this->formatMessage($customMessages, "min", $field, $param);
                }

                if ($ruleName === "email" && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = $this->formatMessage($customMessages, "email", $field);
                }

                if ($ruleName === "match" && $value !== ($this->postData[$param] ?? "")) {
                    $errors[$field][] = $this->formatMessage($customMessages, "match", $field);
                }

                if ($ruleName === "unique") {
                    [$table, $column] = explode(",", $param);
                    /*** @var QueryBuilder $query */
                    $query = Container::getInstance()->get(QueryBuilder::class);
                    $exists = $query->setTable($table)
                    ->where($column, "=", $value)
                    ->first();
                    if ($exists) {
                        $errors[$field][] = $this->formatMessage($customMessages, "unique", $field);
                    }
                }
            }
        }

        if (!empty($errors)) {
            Flash::set("error", $errors);
            throw new ValidationException("Invalid validation");
        }
    }

    private function formatMessage(array $messages, string $rule, string $field, string $value = ""): string
    {
        $message = $messages[$rule] ?? "The {$field} field is invali.";
        return str_replace([":field", ":value"], [$field, $value], $message);
    }

    public function getUrl(): string
    {
        $scheme = isset($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $this->serverParams['HTTP_HOST'] ?? 'localhost';
        $uri = $this->getUri();
        return "$scheme://$host$uri";
    }

    /**
     * Get the query parameters from the request.
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Get a specific query parameter from the request.
     *
     * @param string $key The name of the query parameter.
     * @param string|null $default The default value to return if the parameter is not present.
     * @return string|null The value of the query parameter or the default value.
     */
    public function query(string $key, ?string $default = null): ?string
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function getFile(string $key): ?UploadedFile
    {
        if ($this->hasFile($key)) {
            return new UploadedFile($_FILES[$key]);
        }
        return null;
    }
}
