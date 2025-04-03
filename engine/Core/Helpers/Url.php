<?php

declare(strict_types=1);

namespace Forge\Core\Helpers;

final class Url
{
    private const UPLOAD_PATH = "file/uploads";

    public static function generateLinks(string $baseUrl, int $page, int $perPage, int $totalPages): array
    {
        $links = [];
        $links['self'] = "$baseUrl?page=$page&per_page=$perPage";

        if ($page > 1) {
            $links['first'] = "$baseUrl?page=1&per_page=$perPage";
            $links['prev'] = "$baseUrl?page=" . ($page - 1) . "&per_page=$perPage";
        }

        if ($page < $totalPages) {
            $links['next'] = "$baseUrl?page=" . ($page + 1) . "&per_page=$perPage";
            $links['last'] = "$baseUrl?page=$totalPages&per_page=$perPage";
        }

        return $links;
    }

    public static function getUrl(): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER["HTTP_HOST"] . '/' . self::UPLOAD_PATH;
        return "$scheme://$host";
    }
}
