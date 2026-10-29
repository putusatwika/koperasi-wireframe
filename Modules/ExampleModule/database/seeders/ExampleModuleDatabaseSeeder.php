<?php

namespace Modules\ExampleModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ExampleModule\Models\ExampleData;

class ExampleModuleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exampleData = [
            [
                'name' => 'Example Data 1',
                'description' => 'Example Data contoh pertama untuk ExampleModule.',
                'status' => 'active',
            ],
            [
                'name' => 'Example Data 2',
                'description' => 'Data contoh kedua untuk ExampleModule.',
                'status' => 'active',
            ],
            [
                'name' => 'Example Data 3',
                'description' => 'Data contoh ketiga untuk ExampleModule.',
                'status' => 'inactive',
            ],
            [
                'name' => 'Example Data 4',
                'description' => 'Data contoh keempat untuk ExampleModule.',
                'status' => 'active',
            ],
            [
                'name' => 'Example Data 5',
                'description' => 'Data contoh kelima untuk ExampleModule.',
                'status' => 'active',
            ],
        ];

        foreach ($exampleData as $data) {
            ExampleData::query()->firstOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}
