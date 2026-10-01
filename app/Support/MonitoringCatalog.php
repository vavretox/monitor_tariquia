<?php

namespace App\Support;

final class MonitoringCatalog
{
    public const DEMAND_PRIORITIES = ['baja', 'media', 'alta', 'critica'];

    public const DEMAND_STATES = ['identificada', 'priorizada', 'en_gestion', 'en_ejecucion', 'resuelta', 'postergada'];

    public const ACTION_STATES = ['pendiente', 'en_ejecucion', 'completada', 'bloqueada'];

    private function __construct() {}
}
