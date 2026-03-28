<?php
// ✅ PATRÓN: STRATEGY — Interface compatible con PHP 7.3+

interface SalarioStrategy
{
    /**
     * @param float $salarioBase
     * @param int   $horasSemana
     * @return array
     */
    public function calcular($salarioBase, $horasSemana);

    /** @return int */
    public function diasVacacionesPermitidos();

    /** @return string */
    public function getNombre();
}
