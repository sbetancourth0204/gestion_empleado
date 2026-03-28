<?php
// ✅ Modelo de dominio puro — compatible con PHP 7.3+
// Sin typed properties (requieren PHP 7.4) — usamos docblocks

class Empleado
{
    /** @var int */
    public $id = 0;
    /** @var string */
    public $nombre;
    /** @var string tiempo_completo | tiempo_parcial | contratista */
    public $tipo;
    /** @var string */
    public $cargo;
    /** @var float */
    public $salario_base;
    /** @var int */
    public $horas_semana;
    /** @var string */
    public $fecha_ingreso;
    /** @var string */
    public $email;
    /** @var bool */
    public $activo = true;

    public function __construct(
        $nombre,
        $tipo,
        $cargo,
        $salario_base,
        $horas_semana,
        $fecha_ingreso,
        $email
    ) {
        $this->nombre        = $nombre;
        $this->tipo          = $tipo;
        $this->cargo         = $cargo;
        $this->salario_base  = (float)$salario_base;
        $this->horas_semana  = (int)$horas_semana;
        $this->fecha_ingreso = $fecha_ingreso;
        $this->email         = $email;
    }

    public function toArray()
    {
        return array(
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'tipo'          => $this->tipo,
            'cargo'         => $this->cargo,
            'salario_base'  => $this->salario_base,
            'horas_semana'  => $this->horas_semana,
            'fecha_ingreso' => $this->fecha_ingreso,
            'email'         => $this->email,
        );
    }
}
