<?php

namespace Modules\ExampleModule\Tests\Feature;

use Modules\ExampleModule\Enums\ExampleModuleCOAConfigEnum;
use Modules\ExampleModule\Models\CoaConfig;
use Tests\TestCase;

class ExampleModuleCOAConfigTest extends TestCase
{
    public function test_example_module_coa_config_contains_the_example_data_amount_key(): void
    {
        $this->assertSame('EXAMPLE_DATA_AMOUNT', ExampleModuleCOAConfigEnum::ExampleDataAmount->value);
    }

    public function test_coa_config_uses_the_module_table_and_enum_cast(): void
    {
        $config = (new CoaConfig)->fill([
            'key' => ExampleModuleCOAConfigEnum::ExampleDataAmount->value,
            'coa_account_id' => 1,
        ]);

        $this->assertSame('examplemodule_coa_config', $config->getTable());
        $this->assertSame(ExampleModuleCOAConfigEnum::ExampleDataAmount, $config->key);
    }
}
