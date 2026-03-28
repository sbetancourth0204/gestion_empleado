<?php
require_once __DIR__ . '/../models/Empleado.php';

// ✅ PATRÓN: FACTORY — compatible con PHP 7.3+
// Encapsula la creación de objetos Empleado con validaciones y defaults por tipo.

class EmpleadoFactory
{
    private static $HORAS_FIJAS = array(
        'tiempo_completo' => 40,
        'contratista'     => 40,
    );

    /**
     * @param  string $tipo
     * @param  array  $datos
     * @return Empleado
     */
    public static function crear($tipo, $datos)
    {
        self::validarTipo($tipo);
        self::validarDatos($datos);

        switch ($tipo) {
            case 'tiempo_completo':
            case 'contratista':
                $horas = self::$HORAS_FIJAS[$tipo];
                break;
            case 'tiempo_parcial':
                $horas = isset($datos['horas_semana']) ? (int)$datos['horas_semana'] : 20;
                break;
            default:
                $horas = 40;
        }

        return new Empleado(
            trim($datos['nombre']),
            $tipo,
            trim($datos['cargo']),
            (float)$datos['salario_base'],
            $horas,
            $datos['fecha_ingreso'],
            isset($datos['email']) ? trim($datos['email']) : ''
        );
    }

    private static function validarTipo($tipo)
    {
        $permitidos = array('tiempo_completo', 'tiempo_parcial', 'contratista');
        if (!in_array($tipo, $permitidos, true)) {
            throw new InvalidArgumentException(
                "Tipo '$tipo' no valido. Use: " . implode(', ', $permitidos)
            );
        }
    }

    private static function validarDatos($datos)
    {
        $requeridos = array('nombre', 'cargo', 'salario_base', 'fecha_ingreso');
        foreach ($requeridos as $campo) {
            if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
                throw new InvalidArgumentException("Campo requerido faltante: $campo");
            }
        }
        if ((float)$datos['salario_base'] <= 0) {
            throw new InvalidArgumentException("El salario_base debe ser mayor a 0.");
        }
    }
}
