<?php

class Logger {
    private static ?Logger $instance = null;
    private string $appLogPath;
    private string $errorLogPath;

    private function __construct() {
        $root = realpath(__DIR__ . '/../../');
        if ($root === false) {
            $root = __DIR__ . '/../../';
        }

        $logDir = $root . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->appLogPath = $logDir . '/app.log';
        $this->errorLogPath = $logDir . '/error.log';
        $this->registerHandlers();
    }

    private function registerHandlers(): void {
        set_exception_handler([self::class, 'exceptionHandler']);
        set_error_handler([self::class, 'errorHandler']);
        register_shutdown_function([self::class, 'shutdownHandler']);
    }

    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    public static function info(int $userId, string $action, string $details): void {
        $message = sprintf(
            '%s | USER_ID=%d | ACTION=%s | DETAILS=%s',
            date('Y-m-d H:i:s'),
            $userId,
            $action,
            $details
        );
        self::writeLog(self::getInstance()->appLogPath, $message);
    }

    public static function error(string $message, ?Throwable $exception = null): void {
        $line = sprintf('%s | ERROR | %s', date('Y-m-d H:i:s'), $message);

        if ($exception !== null) {
            $line .= sprintf(
                ' | Exception: %s in %s:%d | Trace: %s',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                str_replace(PHP_EOL, ' ', $exception->getTraceAsString())
            );
        }

        self::writeLog(self::getInstance()->errorLogPath, $line);
    }

    public static function exceptionHandler(Throwable $exception): void {
        self::error('Uncaught exception', $exception);
        if (!headers_sent()) {
            http_response_code(500);
        }
    }

    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
        $message = sprintf('PHP error [%d]: %s in %s:%d', $errno, $errstr, $errfile, $errline);
        self::error($message, null);
        return true;
    }

    public static function shutdownHandler(): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            $message = sprintf('Shutdown fatal error [%d]: %s in %s:%d', $error['type'], $error['message'], $error['file'], $error['line']);
            self::error($message, null);
        }
    }

    private static function writeLog(string $filePath, string $text): void {
        $line = $text . PHP_EOL;
        $previousErrorHandler = set_error_handler(function () {
            return true;
        });
        @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
        if ($previousErrorHandler !== null) {
            set_error_handler($previousErrorHandler);
        } else {
            restore_error_handler();
        }
    }
}

Logger::getInstance();