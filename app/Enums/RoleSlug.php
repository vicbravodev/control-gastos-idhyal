<?php

namespace App\Enums;

/**
 * Organizational role keys from functional spec §1.3.
 */
enum RoleSlug: string
{
    case SuperAdmin = 'super_admin';

    case SecretarioGeneral = 'secretario_general';

    case Contabilidad = 'contabilidad';

    case CoordRegional = 'coord_regional';

    case CoordEstatal = 'coord_estatal';

    case Asesor = 'asesor';
}
