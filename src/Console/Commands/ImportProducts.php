<?php
declare(strict_types=1);


namespace CSCart\Icecat\Console\Commands;

use CSCart\Icecat\ImportManager;
use Illuminate\Console\Command;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cs:icecat-importer:products {--login=} {--password=} {--limit=} {--lang=} {--without-images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): void
    {
        $withoutImages = (bool) $this->option('without-images');

        $importer = new ImportManager(
            (string) $this->option('login'),
            (string) $this->option('password'),
            (int) $this->option('limit'),
            (string) $this->option('lang'),
            $withoutImages
        );

        $importer->import();
    }
}
