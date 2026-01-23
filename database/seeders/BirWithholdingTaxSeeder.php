<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BirWithholdingTax;

class BirWithholdingTaxSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'compensation_from' => 0,
                'compensation_to' => 250000,
                'fixed_tax' => 0,
                'excess_over' => 0,
                'percent_over' => 0,
            ],
            [
                'compensation_from' => 250000.01,
                'compensation_to' => 400000,
                'fixed_tax' => 0,
                'excess_over' => 250000,
                'percent_over' => 15,
            ],
            [
                'compensation_from' => 400000.01,
                'compensation_to' => 800000,
                'fixed_tax' => 22500,
                'excess_over' => 400000,
                'percent_over' => 20,
            ],
            [
                'compensation_from' => 800000.01,
                'compensation_to' => 2000000,
                'fixed_tax' => 102500,
                'excess_over' => 800000,
                'percent_over' => 25,
            ],
            [
                'compensation_from' => 2000000.01,
                'compensation_to' => 8000000,
                'fixed_tax' => 402500,
                'excess_over' => 2000000,
                'percent_over' => 30,
            ],
            [
                'compensation_from' => 8000000.01,
                'compensation_to' => null,
                'fixed_tax' => 2202500,
                'excess_over' => 8000000,
                'percent_over' => 35,
            ],
        ];

        foreach ($data as $row) {
            BirWithholdingTax::create(array_merge($row, ['effective_year' => 2025]));
        }
    }
}
