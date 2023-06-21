<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 21 Jun 2023 07:55:14 Malaysia Time, Pantai Lembeng, Bali, Id
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace Database\Factories\Market;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'        => fake()->lexify(),
            'units'       => fake()->numberBetween(20, 200),
            'price'       => fake()->numberBetween(100, 2000),
            'rrp'         => fake()->numberBetween(20, 100),
            'name'        => fake()->name,
            'description' => fake()->text,
        ];
    }
}
