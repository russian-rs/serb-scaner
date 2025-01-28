<?php

namespace App\Services\Laws;

use Google\Cloud\Translate\V2\TranslateClient;

class TranslationService
{

    private TranslateClient $translateClient;
    private string $targetLang;

    public function __construct()
    {
        // Настройка целевого и исходного языка
        $this->targetLang = 'ru';

        // Инициализация клиента Google Translate API
        $this->translateClient = new TranslateClient([
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);
    }


    public function translate(string $text, string $targetLang = null): string
    {
        $maxLimit = 25000;

        // Разбиваем текст на порции
        $textParts = $this->splitTextIntoParts($text, $maxLimit);

        $translatedParts = [];
        foreach ($textParts as $part) {
            try {
                $response = $this->translateClient->translate($part, [
                    'target' => $targetLang ?? $this->targetLang,
                ]);
                $translatedParts[] = $response['text'] ?? throw new \Exception('API не вернул перевод.');
            } catch (\Throwable $e) {
                throw new \Exception('Ошибка при переводе текста: ' . $e->getMessage());
            }
        }

        return implode(PHP_EOL, $translatedParts); // Объединяем переведённые части
    }

    /**
     * В api google стоит лимит -- разобьем текст на части
     */
    private function splitTextIntoParts(string $text, int $maxLimit): array
    {
        // Разбиваем текст по предложениям или абзацам
        $parts = preg_split('/(\.|\n|\r)+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $result = [];
        $currentPart = '';

        foreach ($parts as $part) {
            if (mb_strlen($currentPart . $part) < $maxLimit) {
                $currentPart .= $part;
            } else {
                $result[] = $currentPart;
                $currentPart = $part;
            }
        }

        if (!empty($currentPart)) {
            $result[] = $currentPart;
        }

        return $result;
    }

}
