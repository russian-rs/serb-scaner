<?php

namespace App\Console\Commands\VacanciesScraping;


use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ParseInfostudCommand extends Command
{
    protected string $siteDomen = 'https://poslovi.infostud.com/';
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'parse:infostud';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Парсинг вакансий сайта https://poslovi.infostud.com/';

    /**
     * Исполнение команды.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Начинаем парсинг страницы...');

        try {
            $client = new Client();


            $response = $client->get('https://poslovi.infostud.com/oglasi-za-posao?sort=online_view_date');
            $html = $response->getBody()->getContents();

            $dom = new \DOMDocument();
            @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML

            $xpath = new \DOMXPath($dom);

            $links = [];
            for ($i=0; $i<100; $i++){

                $element = $xpath->query('//*[@id="oglas_'.$i.'"]');
                if ($element->length > 0) {

                    // Находим ссылку на вакансию
                    $aElem = $xpath->query('.//a', $element->item(0));

                    if ($aElem->length > 0) {
                        $href = $aElem->item(0)->getAttribute('href');
                        $links[] = $href;
                    } else {
                        echo "Ссылка <a> не найдена внутри #oglas_$i";
                    }
                } else {
                    echo "Элемент с id='oglas_0' не найден";
                    break;
                }

            }

            dump($links);

            $this->info('Парсинг завершён!');
        } catch (\Exception $e) {
            $this->error('Ошибка при запросе страницы: ' . $e->getMessage());
        }
    }


}
