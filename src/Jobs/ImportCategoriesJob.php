<?php
declare(strict_types=1);


namespace CSCart\Icecat\Jobs;

use CSCart\Icecat\Importers\CategoriesImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportCategoriesJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private array $categories;
    private string $lang;

    /**
     * @param array  $categories
     * @param string $lang
     */
    public function __construct(array $categories, string $lang)
    {
        $this->categories = $categories;
        $this->lang = $lang;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        (new CategoriesImporter($this->categories, $this->lang))->import();
    }
}
