<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class Application
{
    private static self $instance;
    private Container   $container;
    private Router      $router;

    private function __construct(private readonly string $basePath)
    {
        $this->container = new Container();
        $this->router    = new Router();

        $this->loadEnvironment();
        $this->registerCoreBindings();
        $this->registerErrorHandler();
    }

    public static function create(string $basePath): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request, $this->container);
    }

    public function run(): void
    {
        $response = $this->handle(Request::fromGlobals());
        $response->send();
    }

    // ── Bootstrapping ─────────────────────────────────────────────────────────

    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->basePath);
        $dotenv->load();
        $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_KEY']);
    }

    private function registerCoreBindings(): void
    {
        $this->container->singleton(
            \App\Config\Database::class,
            static fn() => new \App\Config\Database(),
        );
    }

    private function registerErrorHandler(): void
    {
        $isProduction = strtolower($_ENV['APP_ENV'] ?? 'production') === 'production';

        set_exception_handler(static function (\Throwable $e) use ($isProduction): void {
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            $body = $isProduction
                ? ['error' => 'An unexpected error occurred.']
                : ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];

            Response::serverError($body['error'])->send();
        });
    }
}
