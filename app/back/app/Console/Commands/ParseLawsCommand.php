<?php

namespace App\Console\Commands;

use App\Models\Law;
use App\Services\Laws\LawInformation;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ParseLawsCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'parse:laws';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Парсинг названий законов с сайта парламента';

    /**
     * Исполнение команды.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Начинаем парсинг страницы...');

        try {
            $linkNodes = $this->getLinkNodes();
            // Проверяем, если ссылки имеются
            if ($linkNodes->length === 0) {
                $this->error('Не удалось найти ссылки. Проверьте HTML-структуру страницы.');
                return;
            }

            $lawsInfo = $this->getLawsInfo($linkNodes);
            if (empty($lawsInfo)) {
                $this->error('Ссылки не найдены.');
                return;
            }

            // Сохраним в базе ссылки если они не сохранены еще
            $this->saveLinks($lawsInfo);

            // Выводим список найденных ссылок
            $this->info('Найденные ссылки:');
            foreach ($lawsInfo as $lawInfo) {
                $this->line('- ' . $lawInfo->getLink());
            }

            $this->info('Парсинг завершён!');
        } catch (\Exception $e) {
            $this->error('Ошибка при запросе страницы: ' . $e->getMessage());
        }
    }

    /**
     * @param LawInformation[] $lawsInfo
     * @return void
     */
    private function saveLinks(array $lawsInfo)
    {
        foreach ($lawsInfo as $lawInfo) {
            $law = Law::where('link', $lawInfo->getLink())->first();
            if (!$law) {
                $law = new Law;
                $law->link = $lawInfo->getLink();
                $law->name = $lawInfo->getName();
                $law->slug = $lawInfo->getSlug();
                $law->save();
            }
        }

        return;
    }

    /**
     * @param $linkNodes
     * @return LawInformation[]|array
     */
    private function getLawsInfo($linkNodes): array
    {
        $links = [];
        foreach ($linkNodes as $node) {
            $href = $node->getAttribute('href');
            $linkName = trim($node->nodeValue);

            // Проверяем на относительный путь /upload/archive/files/cir/pdf/zakoni
            if (strpos($href, '/upload/archive/files/cir/pdf/zakoni') === 0
                && substr($href, -4) === '.pdf'
                && $linkName !== 'PDF' // исключаем ссылку с именем PDF
            ) {
                $links[] = new LawInformation($linkName, $href, $this->getSlugByLink($href));
            }
        }

        return $links;
    }

    private function getSlugByLink(?string $link)
    {
        if (!$link) {
            return null;
        }

        // Используем регулярное выражение для извлечения текста между последним "/" и ".pdf"
        if (preg_match('/\/([^\/]+)\.pdf$/', $link, $matches)) {
            return str_replace(' ', '_', $matches[1]);
        }

        return null;
    }

    private function getLinkNodes()
    {
        $client = new Client();
        $response = $client->get('http://www.parlament.gov.rs/акти/донети-закони/донети-закони.45.html');
        $html = $response->getBody()->getContents();

        $dom = new \DOMDocument();
        @$dom->loadHTML($html); // Используем @, чтобы скрыть предупреждения о некорректном HTML

        $xpath = new \DOMXPath($dom);

        // Ищем все ссылки на странице
        $linkNodes = $xpath->query('//a[@href]');

        return $linkNodes;
    }
}
