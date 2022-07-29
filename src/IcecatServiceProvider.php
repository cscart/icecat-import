<?php
declare(strict_types=1);


namespace CSCart\Icecat;

use CSCart\Icecat\Console\Commands\DownloadImages;
use CSCart\Icecat\Console\Commands\ImportProducts;
use Illuminate\Support\ServiceProvider;

class IcecatServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(ImportProducts::class, DownloadImages::class);
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/data-importer.php', 'data-importer');
    }
}
