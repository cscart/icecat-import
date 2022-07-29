<?php
declare(strict_types=1);


namespace CSCart\Icecat\Jobs;


use CSCart\Icecat\Importers\FeaturesImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportFeaturesJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private array $features;
    private string $lang;

    /**
     * @param array  $features
     * @param string $lang
     */
    public function __construct(array $features, string $lang)
    {
        $this->features = $features;
        $this->lang = $lang;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        (new FeaturesImporter($this->features, $this->lang))->import();
    }
}
