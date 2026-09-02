<?php
namespace App\Core;

use App\Helpers\Flash;

class Controller
{
    /**
     * Render a view file with optional data and layout
     * 
     * @param string $viewPath e.g. 'pos/index'
     * @param array $data
     * @param string|null $layout e.g. 'layouts/master'
     */
    protected function view(string $viewPath, array $data = [], ?string $layout = null): void
    {
        extract($data);
        $cleanPath = str_replace('.', '/', $viewPath);
        $fullPath = dirname(__DIR__, 2) . '/views/' . $cleanPath . '.php';

        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        }

        if (!file_exists($fullPath)) {
            http_response_code(500);
            echo "View file not found: views/{$viewPath}.php";
            exit;
        }

        if ($layout !== null) {
            // Buffer the view content
            ob_start();
            require $fullPath;
            $content = ob_get_clean();

            $layoutPath = dirname(__DIR__, 2) . '/views/' . $layout . '.php';
            if (file_exists($layoutPath)) {
                require $layoutPath;
            } else {
                echo $content;
            }
        } else {
            require $fullPath;
        }
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL
     * 
     * @param string $path
     */
    protected function redirect(string $path): void
    {
        Router::redirect($path);
    }

    /**
     * Redirect back to HTTP_REFERER or fallback URL
     * 
     * @param string $fallback
     */
    protected function redirectBack(string $fallback = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirectUrl = (string)$this->input('redirect_url', '');

        if (!empty($redirectUrl)) {
            $this->redirect($redirectUrl);
            return;
        }

        if (!empty($referer)) {
            $parsed = parse_url($referer);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if (empty($parsed['host']) || $parsed['host'] === $currentHost) {
                $target = ($parsed['path'] ?? '/') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
                $this->redirect($target);
                return;
            }
        }

        $this->redirect($fallback);
    }

    /**
     * Set Flash Messages
     */
    protected function flashSuccess(string $message, ?string $title = null): void
    {
        Flash::success($message, $title);
    }

    protected function flashError(string $message, ?string $title = null): void
    {
        Flash::error($message, $title);
    }

    protected function flashWarning(string $message, ?string $title = null): void
    {
        Flash::warning($message, $title);
    }

    protected function flashInfo(string $message, ?string $title = null): void
    {
        Flash::info($message, $title);
    }

    /**
     * Get input from POST or GET
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Validasi Token CSRF
     */
    protected function validateCsrf(): bool
    {
        if (!\App\Helpers\CSRF::validate()) {
            $this->flashError('Sesi formulir kadaluarsa (CSRF Mismatch). Silakan ulangi aksi Anda.');
            return false;
        }
        return true;
    }
}
