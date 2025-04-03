<?php

declare(strict_types=1);

namespace Forge\Traits;

use Forge\Core\DI\Container;
use Forge\Core\Http\ApiResponse;
use Forge\Core\Http\Response;
use Forge\Core\View\View;
use ReflectionClass;

trait ControllerHelper
{
    /**
     * Helper method to return a JSON response
     *
     * @param array $data
     * @param int $statusCode
     *
     * @return Response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): Response
    {
        $jsonData = json_encode($data);
        return (new Response($jsonData, $statusCode))->setHeader('Content-Type', 'application/json');
    }

    /**
     * Render a view file.
     *
     * @param string $view The view file path (relative to views directory).
     * @param array<string, mixed> $data Data to pass to the view.
     * @return Response Returns the rendered view content.
     */
    protected function view(string $view, array $data = []): Response
    {
        $module = $this->detectModule();
        $viewPath = $module
            ? BASE_PATH . "/modules/{$module}/src/views"
            : BASE_PATH . "/app/views";

        return (new View(Container::getInstance(), $viewPath))->render($view, $data);
    }

    private function detectModule(): ?string
    {
        $namespaceParts = explode("\\", (new ReflectionClass($this))->getNamespaceName());
        return ($namespaceParts[1] ?? null) === "Modules" ? $namespaceParts[2] : null;
    }

    protected function apiResponse(
        mixed $data,
        int $statusCode = 200,
        array $headers = []
    ): ApiResponse {
        return new ApiResponse($data, $statusCode, $headers);
    }

    protected function apiError(
        string $message,
        int $statusCode = 400,
        array $errors = [],
        string $code = 'ERROR_CODE'
    ): ApiResponse {
        return new ApiResponse(
            null,
            $statusCode,
            [],
            [
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'errors' => $errors
                ]
            ]
        );
    }

    protected function csvResponse(array $data, string $filename = 'export.csv'): Response
    {
        $csv = $this->arraryToCsv($data);
        return (new Response($csv))
        ->setHeader('Content-Type', 'text/csv')
        ->setHeader('Content-Dispostion', "attachment; filename=\"$filename\"");
    }

    private function arraryToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        return stream_get_contents($output);
    }
}
