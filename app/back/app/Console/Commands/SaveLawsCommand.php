<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Services\Laws\LawInformation;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SaveLawsCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'save:laws';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Сохраняем законы локально';

    private string $baseUri = 'http://www.parlament.gov.rs';


    public function handle()
    {
        $this->info('Начинаем сохранение файлов...');

        // Получаем законы, которые еще не скачаны
        /** @var Law[] $laws */
        $laws = $this->getLaws();

        if ($laws->isEmpty()) {
            $this->info('Все законы уже сохранены.');
            return;
        }

        $client = new Client();

        foreach ($laws as $law) {
            $this->info("Скачиваем: {$law->slug}");

            try {
                // Отправляем запрос на скачивание PDF
                $response = $client->get($this->baseUri . $law->link);

                // Проверяем успешность статуса ответа
                if ($response->getStatusCode() === 200) {
                    // Определяем размер файла
                    $fileSize = $response->getBody()->getSize();
                    $fileName = $law->slug . '.pdf';

                    // Сохраняем файл локально
                    Storage::disk('laws')->put($fileName, $response->getBody());

                    // Обновляем запись в базе данных
                    $law->update([
                        'is_downloaded' => true,
                        'size'          => $fileSize, // Сохраняем размер файла
                    ]);

                    $this->info("Файл сохранён: {$fileName}");
                } else {
                    $this->error("Не удалось скачать: {$law->name}");
                }
            } catch (\Exception $e) {
                $this->error("Ошибка при скачивании {$law->link}: " . $e->getMessage());
            }
        }

        $this->info('Завершено.');
    }

    private function getLaws()
    {
        return Law::where('is_downloaded', false)->get();
    }
}
