<?php
declare(strict_types=1);


namespace CSCart\Icecat;

use GuzzleHttp\Client;
use function Safe\fopen;

class Downloader
{
    private string $login;
    private string $password;

    /**
     * @param string $login
     * @param string $password
     */
    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * @param string $url
     * @param string $localPath
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(string $url, string $localPath): void
    {
        $resource = fopen($localPath, 'w');

        $client = new Client();
        $client->get($url, [
            'auth' => [
                $this->login,
                $this->password
            ],
            'sink' => $resource
        ]);
    }
}
