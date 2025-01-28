<?php

namespace App\Console\Commands;

use App\Services\Laws\TranslationService;
use App\Services\PDF\PdfGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TranslateLawsCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'translate:laws';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Переведем сохраненные законы на русский язык';

    private \Illuminate\Contracts\Filesystem\Filesystem $disk;

    public function __construct()
    {
        parent::__construct();
        $this->disk = Storage::disk('laws');
    }


    public function handle()
    {
        $pdfFiles = $this->getPdfFiles();

        if (empty($pdfFiles)) {
            $this->error('Нет PDF файлов для перевода!');
            return;
        }

        foreach ($pdfFiles as $pdfFile) {
            // 1. Извлекаем текст из PDF
            $this->info("Обрабатывается файл: $pdfFile");
            $text = $this->extractTextFromPdf($pdfFile);

            // 2. Перевод текста
            $translatedText = (new TranslationService())->translate($text);

            // 3. Генерация PDF
            $pdfGenerator = new PdfGenerator(Storage::disk('translate_laws'));;
            $pdfGenerator->create($translatedText, $pdfFile);

            $this->info("Файл переведен и сохранен: $pdfFile");
        }

        $this->info('Перевод завершен!');

    }

    private function getPdfFiles(): array
    {
        return $this->disk->files();
    }

    private function extractTextFromPdf(string $filePath): string
    {
        $pdfParser = new \Smalot\PdfParser\Parser();
        $pdf = $pdfParser->parseFile($this->disk->path($filePath));

        return $pdf->getText();
    }

    private function createPdfFromText(string $text, string $outputPath): void
    {
        ini_set('memory_limit', '512M'); // Для больших файлов увеличим память
        $text = mb_convert_encoding($text, 'UTF-8');

        $mpdf = new \Mpdf\Mpdf([
            'default_font' => 'DejaVuSans',
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'tempDir'      => storage_path('mpdf_cache'),
        ]);

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

        $mpdf->WriteHTML($html);

        // Сохранение PDF на диск
        $this->diskTranslate->put($outputPath, $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));

        // Очистка памяти вручную
        $mpdf = null;
        unset($mpdf);
        gc_collect_cycles(); //запускаем сборщик мусора
    }

}
