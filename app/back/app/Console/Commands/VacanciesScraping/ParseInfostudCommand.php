<?php

namespace App\Console\Commands\VacanciesScraping;


use App\DTO\VacancyDTO;
use App\Handlers\TextHandler;
use App\Models\Vacancy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ParseInfostudCommand extends Command
{
    protected string $siteDomen = 'https://poslovi.infostud.com';
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

        $links = $this->getAllLinks();
        $vacanciesInfo = [];

        foreach ($links as $link)
        {
            try {
                $vacanciesInfo[] = $this->parsePage($link);
            }
            catch (\Exception $e){
                $this->error("Ошибка парсинга страницы $link : $e");
            }
        }

        $this->saveVacancies($vacanciesInfo);

        $this->info('Парсинг завершён!');

    }

    private function getAllLinks()
    {
        $links = [];
        $urlListVacancies = 'https://poslovi.infostud.com/oglasi-za-posao?sort=online_view_date&page=';

        try {

//            $pageCount = $this->getLastPaginationNumber($urlListVacancies);
            $pageCount = 1;

            $client = new Client();

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
//                        $this->info( "Элемент с id='oglas_$numberVacancyOnList' не найден");
                        break;
                    }

                }
            }

            $this->info(count($links));

        } catch (\Exception $e) {
            $this->error('Ошибка при запросе страницы: ' . $e->getMessage());
        }

        return $links;
    }


    private function getLastPaginationNumber(string $urlListVacancies)
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


    private function saveVacancies(array $vacanciesInfo)
    {
        //todo process saving to db
    }


    /**
     * @throws GuzzleException
     */
    private function parsePage(string $link): VacancyDTO
    {
        $link = $this->siteDomen . $link;
        $client = new Client();
        $dom = new \DOMDocument();
        $response = $client->get($link);
        $html = $response->getBody()->getContents();
        @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML
        $xpath = new \DOMXPath($dom);

        // удалить все ненужные теги из дома чтобы не засорять текст
        foreach (['script', 'style', 'noscript'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $node->parentNode->removeChild($node);
            }
        }


        $titleNode = $xpath->query('//*[contains(@class, "job__title")]');
        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';
        $title = TextHandler::cleanText($title);

        $descriptionNode = $xpath->query('//*[contains(@id, "__fastedit_html_oglas")]');
        $description = $descriptionNode->length > 0 ? trim($descriptionNode->item(0)->textContent) : '';
        $description = TextHandler::cleanText($description);

        $locationNode = $xpath->query('//*[contains(@class, "job__location")]');
        $location = $locationNode->length > 0 ? trim($locationNode->item(0)->textContent) : '';
        $location = TextHandler::cleanText($location);

        $vacancy = new VacancyDTO(
            title: $title,
            link: $link,
            description: $description,
            salary: $salary ?? null,
            location: $location,
            publication_time: $publication_time ?? null,
        );


        return $vacancy;
    }


}
