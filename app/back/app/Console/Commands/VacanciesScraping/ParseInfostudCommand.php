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

            $urlListVacancies = 'https://poslovi.infostud.com/oglasi-za-posao?sort=online_view_date&page=';

            $pageCount = $this->getPageCount($urlListVacancies);

            $links = [];
            $dom = new \DOMDocument();
            $numberVacancyOnList = 0;

            for ($k=1; $k <= $pageCount; $k++)
            {
                $this->info($urlListVacancies . $k);

                $response = $client->get($urlListVacancies . $k);
                $html = $response->getBody()->getContents();

                @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML

                $xpath = new \DOMXPath($dom);


                while ($numberVacancyOnList < 100000){
                    $element = $xpath->query('//*[@id="oglas_'.$numberVacancyOnList.'"]');
                    if ($element->length > 0) {

                        // Находим ссылку на вакансию
                        $aElem = $xpath->query('.//a', $element->item(0));

                        if ($aElem->length > 0) {
                            $href = $aElem->item(0)->getAttribute('href');
                            $links[] = $href;
                        } else {
                            $this->info( "Ссылка <a> не найдена внутри #oglas_$numberVacancyOnList");
                        }

                        $numberVacancyOnList++;
                    } else {
                        $this->info( "Элемент с id='oglas_$numberVacancyOnList' не найден");
                        break;
                    }

                }
            }

            dump(count($links));

            $this->info('Парсинг завершён!');
        } catch (\Exception $e) {
            $this->error('Ошибка при запросе страницы: ' . $e->getMessage());
        }
    }

    private function getPageCount(string $urlListVacancies)
    {
        try {
            $client = new Client();
            $response = $client->get($urlListVacancies);
            $dom = new \DOMDocument();
            $html = $response->getBody()->getContents();

            @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML

            $xpath = new \DOMXPath($dom);
            $ulElement = $xpath->query('//*[@class="uk-margin-medium-top"]//ul')->item(0);

            if ($ulElement) {
                // Находим все элементы <li> внутри <ul>
                $liElements = $xpath->query('.//li', $ulElement);

                // Проверяем, что есть хотя бы два элемента <li>
                if ($liElements->length >= 2) {
                    // Берем предпоследний элемент <li>
                    $preLastLi = $liElements->item($liElements->length - 2);

                    // Находим элемент <a> внутри предпоследнего <li>
                    $aElement = $xpath->query('.//a', $preLastLi)->item(0);

                    if ($aElement) {
                        // Извлекаем текстовое значение элемента <a>
                        $textValue = $aElement->textContent;
                        return $textValue;
                    } else {
                        $this->info( "Элемент <a> не найден.");
                    }
                } else {
                    $this->info( "Недостаточно элементов <li>.");
                }
            } else {
                $this->info( "Элемент <ul> не найден.");
            }


        }catch (\Exception $e){
            $this->error('Ошибка при запросе страницы: ' . $e->getMessage());
        }

        return 100;

    }


}
