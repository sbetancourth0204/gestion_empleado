<?php
// ✅ PATRÓN: OBSERVER — compatible con PHP 7.3+

interface ObservadorEmpleado
{
    /**
     * @param string $evento  Nombre del evento
     * @param array  $datos   Payload del evento
     */
    public function actualizar($evento, $datos);
}

// ✅ Trait Observable — se mezcla en clases que emiten eventos.
trait Observable
{
    /** @var ObservadorEmpleado[] */
    private $observadores = array();   // ← sin typed property, compatible PHP 7.3+

    public function suscribir(ObservadorEmpleado $obs)
    {
        $this->observadores[] = $obs;
    }

    protected function notificar($evento, $datos)
    {
        foreach ($this->observadores as $obs) {
            $obs->actualizar($evento, $datos);
        }
    }
}
