<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../factories/EmpleadoFactory.php';
require_once __DIR__ . '/../strategies/SalarioStrategy.php';
require_once __DIR__ . '/../strategies/SalarioTiempoCompleto.php';
require_once __DIR__ . '/../strategies/SalarioTiempoParcial.php';
require_once __DIR__ . '/../strategies/SalarioContratista.php';
require_once __DIR__ . '/../observers/Observable.php';
require_once __DIR__ . '/../observers/NotificacionObserver.php';
require_once __DIR__ . '/../observers/LogObserver.php';

// ✅ PATRÓN: REPOSITORY — compatible con PHP 7.3+
// Centraliza el acceso a datos: usa Singleton, Factory, Strategy y Observer.

class EmpleadoRepository
{
    use Observable;

    /** @var mysqli */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConexion();

        // ✅ Registro de observadores
        $this->suscribir(new NotificacionObserver());
        $this->suscribir(new LogObserver());
    }

    // ─── EMPLEADOS ───────────────────────────────────────────

    public function guardar(Empleado $empleado)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO empleados (nombre, tipo, cargo, salario_base, horas_semana, fecha_ingreso, email)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssdiss",
            $empleado->nombre,
            $empleado->tipo,
            $empleado->cargo,
            $empleado->salario_base,
            $empleado->horas_semana,
            $empleado->fecha_ingreso,
            $empleado->email
        );
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();

        $this->notificar('empleado_registrado', array(
            'empleado_id' => $id,
            'nombre'      => $empleado->nombre,
            'tipo'        => $empleado->tipo,
        ));

        return $id;
    }

    public function listarTodos()
    {
        $result    = $this->db->query("SELECT * FROM empleados WHERE activo = 1 ORDER BY nombre ASC");
        $empleados = array();
        while ($fila = $result->fetch_assoc()) {
            $empleados[] = $fila;
        }
        return $empleados;
    }

    public function buscarPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM empleados WHERE id = ? AND activo = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $emp = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $emp ? $emp : null;
    }

    // ─── SALARIO — Strategy ───────────────────────────────────

    public function calcularSalario($id)
    {
        $emp = $this->buscarPorId($id);
        if (!$emp) {
            throw new RuntimeException("Empleado con id=$id no encontrado.");
        }

        $strategy = $this->obtenerEstrategia($emp['tipo']);   // ✅ Strategy
        $calculo  = $strategy->calcular((float)$emp['salario_base'], (int)$emp['horas_semana']);

        return array_merge(
            array('empleado' => $emp['nombre'], 'tipo' => $emp['tipo']),
            $calculo
        );
    }

    // ─── VACACIONES ───────────────────────────────────────────

    public function solicitarVacaciones($empleadoId, $fechaInicio, $fechaFin)
    {
        $emp = $this->buscarPorId($empleadoId);
        if (!$emp) {
            throw new RuntimeException("Empleado no encontrado.");
        }

        $strategy       = $this->obtenerEstrategia($emp['tipo']);
        $diasPermitidos = $strategy->diasVacacionesPermitidos();

        if ($diasPermitidos === 0) {
            throw new RuntimeException("Los contratistas no tienen derecho a vacaciones pagadas.");
        }

        $d1   = new DateTime($fechaInicio);
        $d2   = new DateTime($fechaFin);
        $dias = (int)$d1->diff($d2)->days;

        if ($dias <= 0) {
            throw new InvalidArgumentException("La fecha de fin debe ser posterior a la de inicio.");
        }
        if ($dias > $diasPermitidos) {
            throw new RuntimeException("Solicita $dias dias pero solo tiene derecho a $diasPermitidos.");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO vacaciones (empleado_id, fecha_inicio, fecha_fin, dias) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("issi", $empleadoId, $fechaInicio, $fechaFin, $dias);
        $stmt->execute();
        $stmt->close();

        $this->notificar('vacacion_solicitada', array(
            'empleado_id'  => $empleadoId,
            'nombre'       => $emp['nombre'],
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ));

        return array('dias' => $dias, 'dias_permitidos' => $diasPermitidos);
    }

    public function listarVacaciones()
    {
        $result = $this->db->query(
            "SELECT v.*, e.nombre AS empleado_nombre, e.tipo AS empleado_tipo
             FROM vacaciones v
             JOIN empleados e ON v.empleado_id = e.id
             ORDER BY v.created_at DESC"
        );
        $lista = array();
        while ($fila = $result->fetch_assoc()) {
            $lista[] = $fila;
        }
        return $lista;
    }

    public function actualizarEstadoVacacion($vacacionId, $estado)
    {
        $estadosValidos = array('aprobada', 'rechazada', 'pendiente');
        if (!in_array($estado, $estadosValidos, true)) {
            throw new InvalidArgumentException("Estado no valido: $estado");
        }

        $stmt = $this->db->prepare("UPDATE vacaciones SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $vacacionId);
        $stmt->execute();
        $stmt->close();

        $r    = $this->db->query(
            "SELECT v.empleado_id, e.nombre
             FROM vacaciones v JOIN empleados e ON v.empleado_id = e.id
             WHERE v.id = " . (int)$vacacionId
        );
        $data = $r->fetch_assoc();

        $evento = ($estado === 'aprobada') ? 'vacacion_aprobada' : 'vacacion_rechazada';
        $this->notificar($evento, array(
            'empleado_id' => $data['empleado_id'],
            'nombre'      => $data['nombre'],
        ));
    }

    // ─── REPORTE — una sola query para vacaciones (no N+1) ───

    public function generarReporte()
    {
        $empleados = $this->listarTodos();

        // Una sola query para todos los conteos
        $vacResult = $this->db->query(
            "SELECT empleado_id, COUNT(*) AS total
             FROM vacaciones WHERE estado = 'aprobada'
             GROUP BY empleado_id"
        );
        $vacMap = array();
        while ($row = $vacResult->fetch_assoc()) {
            $vacMap[(int)$row['empleado_id']] = (int)$row['total'];
        }

        $reporte = array(
            'total_empleados' => 0,
            'total_nomina'    => 0.0,
            'por_tipo'        => array(),
            'detalle'         => array(),
        );

        foreach ($empleados as $emp) {
            $strategy     = $this->obtenerEstrategia($emp['tipo']);
            $calculo      = $strategy->calcular((float)$emp['salario_base'], (int)$emp['horas_semana']);
            $salarioFinal = $calculo['salario_final'];

            $reporte['total_nomina'] += $salarioFinal;
            $tipoKey = $emp['tipo'];
            $reporte['por_tipo'][$tipoKey] = (isset($reporte['por_tipo'][$tipoKey]) ? $reporte['por_tipo'][$tipoKey] : 0) + 1;
            $reporte['total_empleados']++;

            $reporte['detalle'][] = array(
                'id'                 => $emp['id'],
                'nombre'             => $emp['nombre'],
                'tipo'               => $emp['tipo'],
                'cargo'              => $emp['cargo'],
                'salario_final'      => $salarioFinal,
                'vacaciones_tomadas' => isset($vacMap[(int)$emp['id']]) ? $vacMap[(int)$emp['id']] : 0,
                'tipo_calculo'       => $calculo['tipo_calculo'],
            );
        }
        $reporte['total_nomina'] = round($reporte['total_nomina'], 2);

        $this->notificar('reporte_generado', array('tipo' => 'Nomina Completa'));

        return $reporte;
    }

    // ─── NOTIFICACIONES ──────────────────────────────────────

    public function listarNotificaciones()
    {
        $result = $this->db->query(
            "SELECT n.*, e.nombre AS empleado_nombre
             FROM notificaciones n
             LEFT JOIN empleados e ON n.empleado_id = e.id
             ORDER BY n.created_at DESC
             LIMIT 30"
        );
        $lista = array();
        while ($fila = $result->fetch_assoc()) {
            $lista[] = $fila;
        }
        return $lista;
    }

    public function marcarNotificacionLeida($id)
    {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // ─── PRIVATE: Fábrica de estrategias ─────────────────────

    /**
     * @return SalarioStrategy
     */
    private function obtenerEstrategia($tipo)
    {
        // ✅ PATRÓN: STRATEGY — switch compatible con PHP 7.3+
        switch ($tipo) {
            case 'tiempo_completo': return new SalarioTiempoCompleto();
            case 'tiempo_parcial':  return new SalarioTiempoParcial();
            case 'contratista':     return new SalarioContratista();
            default:
                throw new InvalidArgumentException("Tipo de empleado invalido: $tipo");
        }
    }
}
