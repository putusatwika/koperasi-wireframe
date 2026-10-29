<?php

namespace Modules\ExampleModule\Tests\Feature;

use Modules\ExampleModule\Models\ExampleData;
use Tests\TestCase;

class ModuleTableNamingTest extends TestCase
{
    public function test_example_module_models_use_the_module_table_prefix(): void
    {
        $this->assertSame('examplemodule_example_data', (new ExampleData)->getTable());
    }

    public function test_example_data_supports_a_decimal_amount(): void
    {
        $exampleData = (new ExampleData)->fill([
            'amount' => '1250.5',
        ]);

        $this->assertTrue(in_array('amount', $exampleData->getFillable(), true));
        $this->assertSame('1250.50', (string) $exampleData->amount);
    }
}
