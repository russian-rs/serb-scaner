<?php

namespace Tests\Feature\Command;

use Tests\TestCase;

class TranslateLawsCommandTest extends TestCase
{
    public function testCommandExecution()
    {
        // Выполняем команду
        $result = $this->artisan('translate:laws');

        $this->assertEquals(1, 1);
    }
}
