<?php

declare(strict_types=1);


namespace App\Handlers;

class TextHandler
{

    public static function cleanText(string $text): string {
        // Удаляем множественные пробелы, переводы строк и табуляции
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }


}
