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

class ParseJoobleCommand extends Command
{
    protected string $siteDomen = 'https://rs.jooble.org/';
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'parse:jooble';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Парсинг вакансий сайта https://rs.jooble.org/';

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

    private function getAllLinks(): array
    {
        $links = [];
        $urlListVacancies = 'https://rs.jooble.org/SearchResult?p=5000';
        $client = new Client();

        try {
            $response = $client->get($urlListVacancies);
            $html = $response->getBody()->getContents();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML
            $xpath = new \DOMXPath($dom);

            $links = $this->extractAllLinks($xpath);

        }catch (\Exception $e) {
            Log::error('Ошибка при запросе страницы: ' . $e->getMessage());
        }

        return $links;
    }


    private function saveVacancies(array $vacancies)
    {
        DB::transaction(function () use ($vacancies) {
            // Удаляем старые вакансии
            Vacancy::where('source', VacanciesSource::Jooble->name)->delete();

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


    private function parsePage(string $link): VacancyDTO
    {}

    private function extractAllLinks(\DOMXPath $xpath): array
    {}


}
