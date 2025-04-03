<?php

declare(strict_types=1);

namespace Forge\Core\View;

use Forge\Core\Bootstrap\Bootstrap;
use Forge\Core\DI\Container;
use Forge\Core\Http\Response;

final class View
{
    private static ?array $layout = null;
    private static array $sections = [];
    private static string $currentSection = "";
    private static array $cache = [];

    public function __construct(
        private Container $container,
        private string    $viewPath = BASE_PATH . "/app/views",
        private string    $componentPath = BASE_PATH . "/app/views/components",
        private string    $cachePath = BASE_PATH . "/storage/framework/views",
        private ?string   $module = null
    ) {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function render(string $view, array $data = []): Response
    {
        $viewContent = $this->compileView($view, $data);

        if (self::$layout) {
            $layoutName = self::$layout['name'];
            $useModulePath = self::$layout['useModulePath'];
            $layoutData = array_merge($data, ["content" => $viewContent]);
            $viewContent = $this->compileLayout($layoutName, $useModulePath, $layoutData);
        }
        self::$layout = null;
        return new Response($viewContent);
    }

    private function compileView(string $view, array $data): string
    {
        $viewFile = "{$this->viewPath}/{$view}.php";
        $cacheFile = "{$this->cachePath}/" . md5($view) . ".php";

        if ($this->shouldCompile($viewFile, $cacheFile)) {
            $content = $this->compile(file_get_contents($viewFile));
            file_put_contents($cacheFile, $content);
        }

        extract($data);
        ob_start();
        include $cacheFile;
        $content = ob_get_clean();
        return $this->escape($content);
    }

    private function compileLayout(string $layoutName, bool $useModulePath, array $data): string
    {
        if ($useModulePath) {
            return $this->compileView("layouts/{$layoutName}", $data);
        } else {
            $appView = new View(
                $this->container,
                BASE_PATH . "/app/views",
                BASE_PATH . "/app/views/components",
                $this->cachePath,
                null // No module for app views
            );
            return $appView->compileView("layouts/{$layoutName}", $data);
        }
    }

    private function escape(string $content): string
    {
        return preg_replace_callback('/<\?=\s*(.+?)\s*\?>/', function ($matches) {
            $expression = $matches[1];
            if (str_contains($expression, 'raw(')) {
                return $matches[0];
            }
            return "<?= htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8') ?>";
        }, $content);
    }

    public function renderComponent(string $viewPath, array $data = []): string
    {
        return $this->compileComponent($viewPath, $data);
    }

    private function compileComponent(string $view, array $data): string
    {
        $viewFile = "{$this->componentPath}/{$view}.php";
        $cacheFile = "{$this->cachePath}/" . md5($view) . ".php";

        if ($this->shouldCompile($viewFile, $cacheFile)) {
            $content = $this->compile(file_get_contents($viewFile));
            file_put_contents($cacheFile, $content);
        }

        extract($data);

        include $cacheFile;
        $content = ob_get_clean();
        return $this->escape($content);
    }

    private function shouldCompile(string $viewFile, string $cacheFile): bool
    {
        if (!Bootstrap::shouldCacheViews()) {
            return true;
        }
        return !file_exists($cacheFile) ||
            filemtime($viewFile) > filemtime($cacheFile);
    }

    private function compile(string $content): string
    {
        return $content;
    }

    public static function layout(string $layout, bool $useModulePath = false): void
    {
        self::$layout = [
            'name' => $layout,
            'useModulePath' => $useModulePath
        ];
    }

    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = "";
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? "";
    }

    /**
     * Render a component.
     *
     * @param string $name
     * @param array $props
     * @return string
     */
    public static function component(string $name, array $props = []): string
    {
        return Component::render($name, $props);
    }
}
