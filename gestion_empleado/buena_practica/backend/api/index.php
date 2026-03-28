<?php
// ✅ Punto de entrada limpio — compatible con PHP 7.3+

// Capturar errores PHP como JSON (no como HTML)
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array(
        'success' => false,
        'error'   => "PHP Error [$errno]: $errstr en $errfile linea $errline"
    ));
    exit(1);
});

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../repositories/EmpleadoRepository.php';
require_once __DIR__ . '/../factories/EmpleadoFactory.php';

$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';
$repo   = new EmpleadoRepository();

try {
    switch ($accion) {
        case 'registrar':
            $tipo     = isset($_POST['tipo']) ? $_POST['tipo'] : '';
            $empleado = EmpleadoFactory::crear($tipo, $_POST);
            $resultado = $repo->guardar($empleado);
            break;
        case 'listar':
            $resultado = $repo->listarTodos();
            break;
        case 'calcular_salario':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $resultado = $repo->calcularSalario($id);
            break;
        case 'solicitar_vacaciones':
            $empId = isset($_POST['empleado_id'])  ? (int)$_POST['empleado_id'] : 0;
            $fi    = isset($_POST['fecha_inicio'])  ? $_POST['fecha_inicio']     : '';
            $ff    = isset($_POST['fecha_fin'])     ? $_POST['fecha_fin']        : '';
            $resultado = $repo->solicitarVacaciones($empId, $fi, $ff);
            break;
        case 'listar_vacaciones':
            $resultado = $repo->listarVacaciones();
            break;
        case 'aprobar_vacaciones':
            $vid    = isset($_POST['id'])     ? (int)$_POST['id']     : 0;
            $estado = isset($_POST['estado']) ? $_POST['estado']       : '';
            $repo->actualizarEstadoVacacion($vid, $estado);
            $resultado = null;
            break;
        case 'reporte':
            $resultado = $repo->generarReporte();
            break;
        case 'notificaciones':
            $resultado = $repo->listarNotificaciones();
            break;
        case 'marcar_leida':
            $nid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $repo->marcarNotificacionLeida($nid);
            $resultado = null;
            break;
        default:
            throw new InvalidArgumentException("Accion desconocida: '$accion'");
    }

    echo json_encode(array('success' => true, 'data' => $resultado), JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Error interno: ' . $e->getMessage()));
}
