<?php
require_once __DIR__ . '/SalarioStrategy.php';

// ✅ STRATEGY: Tiempo Parcial — base × (horas / 40)

class SalarioTiempoParcial implements SalarioStrategy
{
    const HORAS_FULL_TIME = 40;
    const DIAS_VACACIONES = 7;

    public function calcular($salarioBase, $horasSemana)
    {
        $proporcion   = round($horasSemana / self::HORAS_FULL_TIME, 4);
        $salarioFinal = round($salarioBase * $proporcion, 2);
        $porcentaje   = round($proporcion * 100, 1);

        return array(
            'salario_base'    => $salarioBase,
            'horas_semana'    => $horasSemana,
            'proporcion'      => $proporcion,
            'salario_final'   => $salarioFinal,
            'tipo_calculo'    => $this->getNombre(),
            'dias_vacaciones' => self::DIAS_VACACIONES,
            'detalle'         => "Base $" . $salarioBase . " x {$porcentaje}% ({$horasSemana}h / 40h)",
        );
    }

    public function diasVacacionesPermitidos() { return self::DIAS_VACACIONES; }
    public function getNombre() { return 'Tiempo Parcial (base x proporcion de horas)'; }
}
