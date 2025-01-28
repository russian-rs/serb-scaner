<?php

namespace App\Services\PDF;

use Mpdf\Mpdf;

class PdfGenerator
{
    private \Illuminate\Contracts\Filesystem\Filesystem $disk;
    private string                                      $tempDir;

    public function __construct(\Illuminate\Contracts\Filesystem\Filesystem $disk)
    {
        ini_set('memory_limit', '512M'); // Увеличение лимита памяти для больших файлов
        $this->disk = $disk;
        $this->tempDir = storage_path('mpdf_cache'); // Временная папка для экономии памяти

        // Инициализация mPDF
        $this->mpdf = new Mpdf([
            'default_font' => 'DejaVuSans',
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'tempDir'      => $this->tempDir, // Устанавливаем кеш mPDF
        ]);
    }

    public function create(string $text, string $outputPath): void
    {
        // Устанавливаем кодировку для pdf
        $text = mb_convert_encoding($text, 'UTF-8');

        // Формируем HTML для pdf
        $this->formatHtml($text);

        // Сохраняем pdf на диск
        $this->disk->put($outputPath, $this->mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));

        // Очистка памяти
        $this->clearMemory();

        return;
    }

    private function clearMemory()
    {
        $this->mpdf = null;
        unset($this->mpdf);
        gc_collect_cycles();

        return;
    }

    private function formatHtml(string $text)
    {
        $html = '<html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            font-family: "DejaVuSans", sans-serif;
                        }
                    </style>
                </head>
                <body>
                    <pre>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</pre>
                </body>
            </html>';

        $this->mpdf->WriteHTML($html);

        return;
    }
}
