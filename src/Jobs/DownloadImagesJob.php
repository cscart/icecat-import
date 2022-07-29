<?php
declare(strict_types=1);


namespace CSCart\Icecat\Jobs;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadImagesJob implements ShouldQueue
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

    /**
     * @param array  $paths
     * @param string $login
     * @param string $password
     */
    public function __construct(array $paths, string $login, string $password)
    {
        $this->paths = $paths;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $client = new Client(['base_uri' => 'http://data.icecat.biz']);

        $requestGenerator = function (array $paths) use ($client) {
            foreach ($paths as $path) {
                // The magic happens here, with yield key => value
                yield $path => function () use ($client, $path) {
                    // Our identifier does not have to be included in the request URI or headers
                    return $client->getAsync($path, [
                        'auth' => [$this->login, $this->password],
                        'sink' => storage_path('/app/images/') . basename($path)
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requestGenerator($this->paths), [
            'concurrency' => 32,
            'fulfilled' => static function (Response $response) {
            },
            'rejected' => static function (Exception $reason) {
                // This callback is delivered each failed request
                echo $reason->getMessage() . PHP_EOL;
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();
        // Force the pool of requests to complete
        $promise->wait();
    }
}
