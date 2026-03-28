<?php
// =========================================================
// MALA PRÁCTICA — TODO EN UN SOLO ARCHIVO "DIOS"
// ❌ Variables con nombres confusos (c, r, d, x, tmp...)
// ❌ Múltiples responsabilidades por función
// ❌ Sin clases ni separación de capas
// ❌ SQL Injection vulnerable (sin prepared statements)
// ❌ Lógica de negocio, DB y presentación mezcladas
// ❌ Código duplicado (copy-paste)
// ❌ Sin validación de entrada
// ❌ Variables globales por doquier
// ❌ N+1 queries en reportes
// =========================================================

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// MALA PRÁCTICA: Variable global con nombre de una letra
$c = mysqli_connect("localhost", "root", "", "gestion_empleados");
if (!$c) { echo json_encode(['success'=>false,'error'=>'fallo db']); exit; }
mysqli_set_charset($c, "utf8");

// MALA PRÁCTICA: Mezcla de $_GET y $_POST en una sola variable confusa
$a = $_REQUEST['accion'] ?? '';

// MALA PRÁCTICA: Un if-elseif gigante que hace TODO
// ======================================================
try {
    if ($a == 'registrar') {
        // MALA PRÁCTICA: Sin validación, sin sanitización
        $n  = $_POST['nombre'];
        $t  = $_POST['tipo'];
        $cg = $_POST['cargo'];
        $s  = $_POST['salario_base'];
        $h  = $_POST['horas_semana'];
        $fi = $_POST['fecha_ingreso'];
        $e  = $_POST['email'];

        // MALA PRÁCTICA: SQL injection vulnerable + lógica mezclada
        $q = "INSERT INTO empleados(nombre,tipo,cargo,salario_base,horas_semana,fecha_ingreso,email)
              VALUES('$n','$t','$cg','$s','$h','$fi','$e')";
        mysqli_query($c, $q);
        $nid = mysqli_insert_id($c);

        // MALA PRÁCTICA: Lógica de notificación mezclada aquí mismo
        $msg = "✅ Empleado $n registrado como $t";
        mysqli_query($c, "INSERT INTO notificaciones(empleado_id,mensaje,tipo) VALUES($nid,'$msg','registro')");

        echo json_encode(['success' => true, 'data' => $nid]);

    } elseif ($a == 'listar') {
        // MALA PRÁCTICA: SELECT * siempre, sin paginación
        $r = mysqli_query($c, "SELECT * FROM empleados WHERE activo=1");
        $d = [];
        // MALA PRÁCTICA: while con fetch dentro del controlador
        while ($f = mysqli_fetch_assoc($r)) { $d[] = $f; }
        echo json_encode(['success' => true, 'data' => $d]);

    } elseif ($a == 'calcular_salario') {
        // MALA PRÁCTICA: Sin validación del id
        $id = $_GET['id'];
        // MALA PRÁCTICA: SQL injection
        $r  = mysqli_query($c, "SELECT * FROM empleados WHERE id=$id");
        $emp = mysqli_fetch_assoc($r);

        // MALA PRÁCTICA: variables de una letra para salario, tipo, horas
        $s = $emp['salario_base'];
        $t = $emp['tipo'];
        $h = $emp['horas_semana'];

        // MALA PRÁCTICA: if-elseif para lógica que debería ser Strategy
        // MALA PRÁCTICA: magic numbers (0.15, 40, 4) sin constantes
        if ($t == 'tiempo_completo') {
            $extra = $s * 0.15;
            $fin   = $s + $extra;
            $tc    = "Tiempo Completo (base + 15% prestaciones)";
            $dv    = 15;
        } elseif ($t == 'tiempo_parcial') {
            $x     = $h / 40;
            $fin   = $s * $x;
            $extra = 0;
            $tc    = "Tiempo Parcial ({$h}hrs / 40hrs)";
            $dv    = 7;
        } else {
            // contratista: tarifa/hora * horas * 4 semanas
            $fin   = $s * $h * 4;
            $extra = 0;
            $tc    = "Contratista (tarifa/hr x hrs x 4 semanas)";
            $dv    = 0;
        }

        echo json_encode(['success' => true, 'data' => [
            'empleado'          => $emp['nombre'],
            'tipo'              => $t,
            'salario_base'      => $s,
            'prestaciones'      => $extra,
            'salario_final'     => round($fin, 2),
            'tipo_calculo'      => $tc,
            'dias_vacaciones'   => $dv
        ]]);

    } elseif ($a == 'solicitar_vacaciones') {
        // MALA PRÁCTICA: Sin validar existencia del empleado antes de insertar
        $eid = $_POST['empleado_id'];
        $fi2 = $_POST['fecha_inicio'];
        $ff  = $_POST['fecha_fin'];

        // MALA PRÁCTICA: SQL injection al obtener tipo
        $rtipo = mysqli_query($c, "SELECT * FROM empleados WHERE id=$eid");
        $etmp  = mysqli_fetch_assoc($rtipo);

        // MALA PRÁCTICA: lógica de negocio mezclada con acceso a datos
        if ($etmp['tipo'] == 'contratista') {
            echo json_encode(['success' => false, 'error' => 'Contratistas no tienen vacaciones']);
            exit;
        }

        // MALA PRÁCTICA: cálculo de días rudimentario, sin considerar fines de semana
        $d1   = new DateTime($fi2);
        $d2   = new DateTime($ff);
        $dias = (int)$d1->diff($d2)->days;

        // MALA PRÁCTICA: lógica duplicada (ya existe en calcular_salario arriba)
        $dv2 = ($etmp['tipo'] == 'tiempo_completo') ? 15 : 7;

        if ($dias > $dv2) {
            echo json_encode(['success' => false, 'error' => "Excede los $dv2 días permitidos"]);
            exit;
        }

        // MALA PRÁCTICA: SQL injection
        mysqli_query($c, "INSERT INTO vacaciones(empleado_id,fecha_inicio,fecha_fin,dias)
                          VALUES($eid,'$fi2','$ff',$dias)");

        // MALA PRÁCTICA: código de notificación copiado y pegado
        $msg2 = "🏖️ Vacaciones solicitadas por '{$etmp['nombre']}' del $fi2 al $ff";
        mysqli_query($c, "INSERT INTO notificaciones(empleado_id,mensaje,tipo) VALUES($eid,'$msg2','vacaciones')");

        echo json_encode(['success' => true, 'data' => ['dias' => $dias, 'dias_permitidos' => $dv2]]);

    } elseif ($a == 'listar_vacaciones') {
        // MALA PRÁCTICA: SELECT * con JOIN innecesariamente verboso en lógica mezclada
        $r = mysqli_query($c, "SELECT v.*, e.nombre as empleado_nombre, e.tipo as empleado_tipo
                               FROM vacaciones v JOIN empleados e ON v.empleado_id=e.id
                               ORDER BY v.created_at DESC");
        $d = [];
        while ($f = mysqli_fetch_assoc($r)) { $d[] = $f; }
        echo json_encode(['success' => true, 'data' => $d]);

    } elseif ($a == 'aprobar_vacaciones') {
        $vid = $_POST['id'];
        $est = $_POST['estado'];

        // MALA PRÁCTICA: SQL injection en UPDATE
        mysqli_query($c, "UPDATE vacaciones SET estado='$est' WHERE id=$vid");

        // MALA PRÁCTICA: query extra para notificación, código copiado de solicitar_vacaciones
        $r3  = mysqli_query($c, "SELECT v.empleado_id, e.nombre FROM vacaciones v JOIN empleados e ON v.empleado_id=e.id WHERE v.id=$vid");
        $dat = mysqli_fetch_assoc($r3);
        $icon = ($est == 'aprobada') ? '✔️' : '❌';
        $msg3 = "$icon Vacaciones ".strtoupper($est)." para '{$dat['nombre']}'";
        // MALA PRÁCTICA: SQL injection en notificación
        mysqli_query($c, "INSERT INTO notificaciones(empleado_id,mensaje,tipo) VALUES({$dat['empleado_id']},'$msg3','vacaciones')");

        echo json_encode(['success' => true, 'data' => null]);

    } elseif ($a == 'reporte') {
        // MALA PRÁCTICA: N+1 queries — una query extra por cada empleado
        $r   = mysqli_query($c, "SELECT * FROM empleados WHERE activo=1");
        $rep = ['total_empleados' => 0, 'total_nomina' => 0.0, 'por_tipo' => [], 'detalle' => []];

        while ($emp = mysqli_fetch_assoc($r)) {
            // MALA PRÁCTICA: query dentro del loop
            $rv = mysqli_query($c, "SELECT COUNT(*) as cnt FROM vacaciones WHERE empleado_id=".$emp['id']." AND estado='aprobada'");
            $vc = mysqli_fetch_assoc($rv);

            // MALA PRÁCTICA: lógica de cálculo DUPLICADA de calcular_salario
            $s2 = $emp['salario_base'];
            $t2 = $emp['tipo'];
            $h2 = $emp['horas_semana'];
            if ($t2 == 'tiempo_completo')     { $fin2 = $s2 + $s2 * 0.15; }
            elseif ($t2 == 'tiempo_parcial')  { $fin2 = $s2 * ($h2 / 40); }
            else                               { $fin2 = $s2 * $h2 * 4;   }

            $rep['total_nomina']        += $fin2;
            $rep['por_tipo'][$t2]        = ($rep['por_tipo'][$t2] ?? 0) + 1;
            $rep['total_empleados']++;
            $rep['detalle'][] = [
                'id'                => $emp['id'],
                'nombre'            => $emp['nombre'],
                'tipo'              => $t2,
                'cargo'             => $emp['cargo'],
                'salario_final'     => round($fin2, 2),
                'vacaciones_tomadas'=> (int)$vc['cnt']
            ];
        }
        $rep['total_nomina'] = round($rep['total_nomina'], 2);

        // MALA PRÁCTICA: notificación mezclada en el reporte
        mysqli_query($c, "INSERT INTO notificaciones(mensaje,tipo) VALUES('📊 Reporte generado','reporte')");

        echo json_encode(['success' => true, 'data' => $rep]);

    } elseif ($a == 'notificaciones') {
        $r = mysqli_query($c, "SELECT n.*, e.nombre as empleado_nombre
                               FROM notificaciones n LEFT JOIN empleados e ON n.empleado_id=e.id
                               ORDER BY n.created_at DESC LIMIT 30");
        $d = [];
        while ($f = mysqli_fetch_assoc($r)) { $d[] = $f; }
        echo json_encode(['success' => true, 'data' => $d]);

    } elseif ($a == 'marcar_leida') {
        $nid = $_POST['id'];
        // MALA PRÁCTICA: SQL injection
        mysqli_query($c, "UPDATE notificaciones SET leida=1 WHERE id=$nid");
        echo json_encode(['success' => true, 'data' => null]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    }
} catch (Exception $ex) {
    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
}
