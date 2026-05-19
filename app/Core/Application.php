<?php

namespace App\Core;

class Application
{
    private static ?self $instance = null;
    private Router $router;
    private array $config;
    private array $dbConfig;

    private function __construct(array $config, array $dbConfig)
    {
        $this->config = $config;
        $this->dbConfig = $dbConfig;
        $this->router = new Router();
    }

    public static function boot(array $config, array $dbConfig): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $dbConfig);
            Session::start($config['session'] ?? []);
            Database::boot($dbConfig);
            self::$instance->registerRoutes();
        }

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application not booted.');
        }

        return self::$instance;
    }

    public function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function runApi(): void
    {
        $uri = $_GET['route'] ?? $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/api';
        if (!str_starts_with($uri, '/api')) {
            $uri = '/api' . (str_starts_with($uri, '/') ? '' : '/') . ltrim($uri, '/');
        }
        $base = rtrim($this->config['base_path'] ?? '', '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        $this->router->dispatch(Request::method(), $uri);
    }

    private function registerRoutes(): void
    {
        $r = $this->router;

        // Tasks API
        $r->get('/api/tasks', fn () => (new \App\Controllers\Api\TaskController())->index());
        $r->post('/api/tasks', fn () => (new \App\Controllers\Api\TaskController())->store());
        $r->post('/api/tasks/complete', fn () => (new \App\Controllers\Api\TaskController())->complete());
        $r->post('/api/tasks/delete', fn () => (new \App\Controllers\Api\TaskController())->destroy());
        $r->post('/api/tasks/restore', fn () => (new \App\Controllers\Api\TaskController())->restore());

        // Lists API
        $r->post('/api/lists', fn () => (new \App\Controllers\Api\ListController())->store());
        $r->post('/api/lists/update', fn () => (new \App\Controllers\Api\ListController())->update());
        $r->post('/api/lists/delete', fn () => (new \App\Controllers\Api\ListController())->destroy());

        // Dashboard & notifications
        $r->get('/api/dashboard/stats', fn () => (new \App\Controllers\Api\DashboardController())->stats());
        $r->get('/api/notifications', fn () => (new \App\Controllers\Api\NotificationController())->index());
        $r->post('/api/notifications/read', fn () => (new \App\Controllers\Api\NotificationController())->markRead());

        // Activity log
        $r->get('/api/activity', fn () => (new \App\Controllers\Api\ActivityController())->index());

        // Attachments
        $r->post('/api/attachments', fn () => (new \App\Controllers\Api\AttachmentController())->upload());

        // Collaboration polling
        $r->get('/api/collaboration/poll', fn () => (new \App\Controllers\Api\CollaborationController())->poll());

        // Public collaborative tasks
        $r->get('/api/public/tasks', fn () => (new \App\Controllers\Api\PublicTaskController())->index());
        $r->post('/api/public/tasks', fn () => (new \App\Controllers\Api\PublicTaskController())->store());
        $r->post('/api/public/tasks/join', fn () => (new \App\Controllers\Api\PublicTaskController())->join());
        $r->get('/api/public/tasks/view', fn () => (new \App\Controllers\Api\PublicTaskController())->view());
    }
}
