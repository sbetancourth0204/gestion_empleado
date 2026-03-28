<?php
require_once __DIR__ . '/Observable.php';
require_once __DIR__ . '/../config/Database.php';

// ✅ PATRÓN: OBSERVER — persiste notificaciones en BD. Compatible con PHP 7.3+

class NotificacionObserver implements ObservadorEmpleado
{
    /** @var mysqli */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConexion();
    }

    public function actualizar($evento, $datos)
    {
        $mensaje    = $this->construirMensaje($evento, $datos);
        $empleadoId = isset($datos['empleado_id']) ? (int)$datos['empleado_id'] : null;
        $tipo       = $evento;

        $stmt = $this->db->prepare(
            "INSERT INTO notificaciones (empleado_id, mensaje, tipo) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $empleadoId, $mensaje, $tipo);
        $stmt->execute();
        $stmt->close();
    }

    private function construirMensaje($evento, $datos)
    {
        switch ($evento) {
            case 'empleado_registrado':
                return "Empleado '{$datos['nombre']}' registrado como {$datos['tipo']}";
            case 'vacacion_solicitada':
                return "Vacaciones solicitadas por '{$datos['nombre']}' del {$datos['fecha_inicio']} al {$datos['fecha_fin']}";
            case 'vacacion_aprobada':
                return "Vacaciones APROBADAS para '{$datos['nombre']}'";
            case 'vacacion_rechazada':
                return "Vacaciones RECHAZADAS para '{$datos['nombre']}'";
            case 'reporte_generado':
                return "Reporte generado: {$datos['tipo']}";
            default:
                return "Evento registrado: $evento";
        }
    }
}
