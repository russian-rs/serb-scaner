<?php

namespace App\Console\Commands\VacanciesScraping;


use App\DTO\VacancyDTO;
use App\Enum\VacanciesSource;
use App\Handlers\TextHandler;
use App\Models\Vacancy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

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
        $this->info('Начинаем парсинг...');

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

            $pageCount = $this->getLastPaginationNumber($urlListVacancies);
            $client = new Client();
            $numberVacancyOnList = 0;

            for ($k=1; $k <= $pageCount; $k++)
            {
                Log::debug($urlListVacancies . $k);
                $response = $client->get($urlListVacancies . $k);
                $html = $response->getBody()->getContents();
                $dom = new \DOMDocument();
                @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML
                $xpath = new \DOMXPath($dom);

                while ($numberVacancyOnList < 100000){
                    $element = $xpath->query('//*[@id="oglas_'.$numberVacancyOnList.'"]');
                    if ($element->length > 0) {
                        // Находим ссылку на вакансию
                        $aElem = $xpath->query('.//a', $element->item(0));

                        if ($aElem->length > 0) {
                            $href = $aElem->item(0)->getAttribute('href');
                            dump($href);
                            $links[] = $href;
                        } else {
                            Log::warning( "Ссылка <a> не найдена внутри #oglas_$numberVacancyOnList");
                        }

                        $numberVacancyOnList++;
                    } else {
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Ошибка при запросе страницы: ' . $e->getMessage());
        }

        return $links;
    }


    private function getLastPaginationNumber(string $urlListVacancies): int|string
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
                        Log::info( "Элемент <a> не найден.");
                    }
                } else {
                    Log::info( "Недостаточно элементов <li>.");
                }
            } else {
                Log::info( "Элемент <ul> не найден.");
            }

        }catch (\Exception $e){
            Log::error('Ошибка при запросе страницы: ' . $e->getMessage());
        }

        return 100;

    }


    private function saveVacancies(array $vacancies)
    {
        DB::transaction(function () use ($vacancies) {
            // Удаляем старые вакансии
            Vacancy::where('source', VacanciesSource::InfoStud->name)->delete();

            // Формируем массив для вставки
            $data = array_map(fn(VacancyDTO $dto) => [
                'title' => $dto->title,
                'link' => $dto->link,
                'description' => $dto->description,
                'salary' => $dto->salary,
                'location' => $dto->location,
                'source' => $dto->source,
                'publication_time' => $dto->publication_time,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], $vacancies);

            // Вставляем новые данные (bulk insert)
            Vacancy::insert($data);
        });

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

        //название вакансии
        $titleNode = $xpath->query('//*[contains(@class, "job__title")]');
        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';
        $title = TextHandler::cleanText($title);

        //описание вакансии
        $descriptionNode = $xpath->query('//*[contains(@id, "__fastedit_html_oglas")]');
        $description = $descriptionNode->length > 0 ? trim($descriptionNode->item(0)->textContent) : '';
        $description = TextHandler::cleanText($description);

        //месторасположения вакансии
        $locationNode = $xpath->query('//*[contains(@class, "job__location")]');
        $location = $locationNode->length > 0 ? trim($locationNode->item(0)->textContent) : '';
        $location = TextHandler::cleanText($location);

        //время публикации
        $publication_time = null;
        $headerNode = $xpath->query("//div[contains(@class, 'ogl-header')]")->item(0);
        if ($headerNode) {
            // Получаем весь текст внутри ogl-header
            $text = $headerNode->textContent;

            // Ищем дату в формате DD.MM.YYYY
            if (preg_match('/\b\d{2}\.\d{2}\.\d{4}\b/', $text, $matches)) {
                $publication_time = $matches[0];
                try {
                    $publication_time = Carbon::createFromFormat('d.m.Y', $publication_time)->startOfDay();
                }catch (Exception $e){}
            }
        }

        $vacancy = new VacancyDTO(
            title: $title,
            link: $link,
            description: $description,
            salary: $salary ?? null,
            location: $location,
            source: VacanciesSource::InfoStud->name,
            publication_time: $publication_time,
        );

        return $vacancy;
    }


}
