<?php

declare(strict_types=1);


namespace App\DTO;

use Illuminate\Support\Carbon;

class VacancyDTO
{

    public function __construct(
        public readonly string $title,
        public readonly string $link,
        public readonly string $description,
        public readonly ?string $salary,
        public readonly string $location,
        public readonly ?Carbon $publication_time,
    )
    {}

}
