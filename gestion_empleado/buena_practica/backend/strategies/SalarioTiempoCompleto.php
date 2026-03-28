<?php
require_once __DIR__ . '/SalarioStrategy.php';

// ✅ STRATEGY: Tiempo Completo — base + 15% prestaciones

class SalarioTiempoCompleto implements SalarioStrategy
{
    const PORCENTAJE_PRESTACIONES = 0.15;
    const DIAS_VACACIONES         = 15;

    public function calcular($salarioBase, $horasSemana)
    {
        $prestaciones = round($salarioBase * self::PORCENTAJE_PRESTACIONES, 2);
        $salarioFinal = round($salarioBase + $prestaciones, 2);

        return array(
            'salario_base'    => $salarioBase,
            'prestaciones'    => $prestaciones,
            'salario_final'   => $salarioFinal,
            'tipo_calculo'    => $this->getNombre(),
            'dias_vacaciones' => self::DIAS_VACACIONES,
            'detalle'         => "Base $" . $salarioBase . " + 15% prestaciones $" . $prestaciones,
        );
    }

    public function diasVacacionesPermitidos() { return self::DIAS_VACACIONES; }
    public function getNombre() { return 'Tiempo Completo (base + 15% prestaciones)'; }
}
