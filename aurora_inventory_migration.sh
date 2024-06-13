#!/bin/bash
# Author: Raul Perusquia <raul@inikoo.com>
# Created: Thu, 13 Jun 2024 06:51:22 Central European Summer Time, Kuala Lumpur, Malaysia
# Copyright (c) 2024, Raul A Perusquia Flores
#

DB_PORT=5432
DB_COLLATE=C.UTF-8
PHP=php

DB_PORT="${1:-$DB_PORT}"
DB_COLLATE="${2:-$DB_COLLATE}"
PHP="${3:-$PHP}"

DB_SUFFIX=_base

echo -e "🧼 Migrating inventory models"

${PHP} artisan fetch:stock-families -d "${DB_SUFFIX}"
${PHP} artisan fetch:stocks -d "${DB_SUFFIX}"

