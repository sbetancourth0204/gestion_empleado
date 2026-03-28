<?php
// ✅ PATRÓN: SINGLETON — compatible con PHP 7.3+
// Una única instancia de conexión a la base de datos en todo el ciclo de vida.

class Database
{
    /** @var Database|null */
    private static $instancia = null;

    /** @var mysqli */
    private $conexion;

    private function __construct()
    {
        $this->conexion = new mysqli("localhost", "root", "", "gestion_empleados");

        if ($this->conexion->connect_error) {
            throw new RuntimeException(
                "Error de conexion a la BD: " . $this->conexion->connect_error
            );
        }
        $this->conexion->set_charset("utf8mb4");
    }

    /** @return Database */
    public static function getInstance()
    {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }
        return self::$instancia;
    }

    /** @return mysqli */
    public function getConexion()
    {
        return $this->conexion;
    }

    private function __clone() {}
}
