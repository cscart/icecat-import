<?php
declare(strict_types=1);


namespace CSCart\Icecat\Console\Commands;


use CSCart\Icecat\DataParser;
use CSCart\Icecat\Downloader;
use CSCart\Icecat\Jobs\DownloadImagesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class DownloadImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cs:icecat-importer:images {--login=} {--password=} {--limit=} {--lang=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download images';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function handle(): void
    {
        $login = (string) $this->option('login');
        $password = (string) $this->option('password');
        $lang = (string) $this->option('lang');
        $limit = (int) $this->option('limit');

        $downloader = new Downloader($login, $password);
        $downloader->download(config('data-importer.entities.products.url'), config('data-importer.entities.products.local_path'));

        $dataParser = new DataParser($lang, $limit);

        $productsAndImages = $dataParser->getProductsAndImagesPaths(config('data-importer.entities.products.local_path'));
        $importImagesJobs = [];
        $imagePaths = $productsAndImages['images_paths'];

        foreach (array_chunk($imagePaths, 50) as $item) {
            $importImagesJobs[] = new DownloadImagesJob($item, $login, $password);
        }

        Bus::batch($importImagesJobs)->onQueue('high')->dispatch();
    }
}
