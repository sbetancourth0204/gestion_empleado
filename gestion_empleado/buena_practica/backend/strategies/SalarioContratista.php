<?php
require_once __DIR__ . '/SalarioStrategy.php';

// ✅ STRATEGY: Contratista — tarifa/hr × horas × 4 semanas

class SalarioContratista implements SalarioStrategy
{
    const SEMANAS_MES     = 4;
    const DIAS_VACACIONES = 0;

    public function calcular($salarioBase, $horasSemana)
    {
        $totalHorasMes = $horasSemana * self::SEMANAS_MES;
        $salarioFinal  = round($salarioBase * $totalHorasMes, 2);

        return array(
            'tarifa_hora'     => $salarioBase,
            'horas_semana'    => $horasSemana,
            'horas_mes'       => $totalHorasMes,
            'salario_final'   => $salarioFinal,
            'tipo_calculo'    => $this->getNombre(),
            'dias_vacaciones' => self::DIAS_VACACIONES,
            'detalle'         => "$" . $salarioBase . "/hr x {$horasSemana}h x 4 semanas = $" . $salarioFinal,
        );
    }

    public function diasVacacionesPermitidos() { return self::DIAS_VACACIONES; }
    public function getNombre() { return 'Contratista (tarifa/hr x horas x 4 semanas)'; }
}
