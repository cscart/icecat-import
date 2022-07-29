<?php
declare(strict_types=1);


namespace CSCart\Icecat\Jobs;


use CSCart\Icecat\Importers\ProductsImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductsJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private array $paths;
    private string $login;
    private string $password;
    private string $lang;

    /**
     * @param array  $paths
     * @param string $login
     * @param string $password
     * @param string $lang
     */
    public function __construct(array $paths, string $login, string $password, string $lang)
    {
        $this->paths = $paths;
        $this->login = $login;
        $this->password = $password;
        $this->lang = $lang;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        (new ProductsImporter($this->paths, $this->login, $this->password, $this->lang))->import();
    }
}
