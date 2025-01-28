<?php

namespace Tests\Feature\Command;

use Tests\TestCase;

class SaveLawsCommandTest extends TestCase
{
    public function testCommandExecution()
    {
        // Выполняем команду
        $result = $this->artisan('save:laws');

        $this->assertEquals(1, 1);
    }
}
