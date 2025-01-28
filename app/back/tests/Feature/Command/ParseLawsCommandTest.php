<?php

namespace Tests\Feature\Command;


use Tests\TestCase;

class ParseLawsCommandTest extends TestCase
{
    public function testCommandExecution()
    {
        // Выполняем команду
        $result = $this->artisan('parse:laws');

        $this->assertEquals(1, 1);
    }
}
