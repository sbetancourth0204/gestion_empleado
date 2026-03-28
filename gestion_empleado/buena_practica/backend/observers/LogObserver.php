<?php
require_once __DIR__ . '/Observable.php';

// ✅ PATRÓN: OBSERVER — escribe en archivo de log. Compatible con PHP 7.3+

class LogObserver implements ObservadorEmpleado
{
    /** @var string */
    private $logFile;

    public function __construct($logFile = '')
    {
        $this->logFile = $logFile ? $logFile : __DIR__ . '/../../logs/app.log';
    }

    public function actualizar($evento, $datos)
    {
        $fecha = date('Y-m-d H:i:s');
        $linea = "[$fecha] EVENTO=$evento | " . json_encode($datos, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logFile, $linea, FILE_APPEND | LOCK_EX);
    }
}
