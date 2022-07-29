<?php
declare(strict_types=1);


namespace CSCart\Icecat;

use CSCart\Icecat\Jobs\DownloadImagesJob;
use CSCart\Icecat\Jobs\ImportCategoriesJob;
use CSCart\Icecat\Jobs\ImportFeaturesJob;
use CSCart\Icecat\Jobs\ImportProductsJob;
use CSCart\Icecat\Jobs\CategoriesParentsSetJob;
use Illuminate\Support\Facades\Bus;

class ImportManager
{
    private string $login;
    private string $password;
    private int $limit;
    private string $productIndexUrl;
    private string $categoryIndexUrl;
    private string $featureIndexUrl;
    private string $categoryLocalPath;
    private string $featureLocalPath;
    private string $productLocalPath;
    private string $lang;
    private int $entityCount;
    private bool $withoutImages;

    /**
     * @param string $login
     * @param string $password
     * @param int    $limit
     * @param string $lang
     * @param bool   $withoutImages
     */
    public function __construct(string $login, string $password, int $limit, string $lang, bool $withoutImages)
    {
        $this->login = $login;
        $this->password = $password;
        $this->limit = $limit;
        $this->lang = $lang;
        $this->withoutImages = $withoutImages;

        $this->productIndexUrl = config('data-importer.entities.products.url');
        $this->categoryIndexUrl =  config('data-importer.entities.categories.url');
        $this->featureIndexUrl =  config('data-importer.entities.features.url');
        $this->productLocalPath = config('data-importer.entities.products.local_path');
        $this->categoryLocalPath = config('data-importer.entities.categories.local_path');
        $this->featureLocalPath = config('data-importer.entities.features.local_path');
        $this->entityCount = (int) config('data-importer.jobs.entity_count');

        if ($this->entityCount < 1) {
            $this->entityCount = 1;
        }
    }

    /**
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function import(): void
    {
        $downloader = new Downloader($this->login, $this->password);
        $dataParser = new DataParser($this->lang, $this->limit);
        $jobsForBus = [];
        $downloader->download($this->categoryIndexUrl, $this->categoryLocalPath);
        $downloader->download($this->productIndexUrl, $this->productLocalPath);
        $downloader->download($this->featureIndexUrl, $this->featureLocalPath);

        $categories = $dataParser->getCategories($this->categoryLocalPath);
        $importCategoriesJobs = [];
        $categoriesParentsSetJob = [];

        /** @phpstan-ignore-next-line */
        foreach (array_chunk($categories, $this->entityCount, true) as $res) {
            $importCategoriesJobs[] = new ImportCategoriesJob($res, $this->lang);
            $categoriesParentsSetJob[] = new CategoriesParentsSetJob($res);
        }

        $jobsForBus[] = static function () use ($importCategoriesJobs) {
            Bus::batch($importCategoriesJobs)->onQueue('high')->dispatch();
        };

        $jobsForBus = array_merge($jobsForBus, $categoriesParentsSetJob);

        $features = $dataParser->getFeatures($this->featureLocalPath);
        $importFeaturesJobs = [];

        /** @phpstan-ignore-next-line */
        foreach (array_chunk($features, $this->entityCount, true) as $res) {
            $importFeaturesJobs[] = new ImportFeaturesJob($res, $this->lang);
        }

        $jobsForBus[] = static function () use ($importFeaturesJobs) {
            Bus::batch($importFeaturesJobs)->onQueue('high')->dispatch();
        };

        $productsAndImages = $dataParser->getProductsAndImagesPaths($this->productLocalPath);
        $importImagesJobs = [];

        if (!$this->withoutImages) {
            $imagePaths = $productsAndImages['images_paths'];
            /** @phpstan-ignore-next-line */
            foreach (array_chunk($imagePaths, $this->entityCount) as $item) {
                $importImagesJobs[] = new DownloadImagesJob($item, $this->login, $this->password);
            }
        }

        $jobsForBus[] = static function () use ($importImagesJobs) {
            Bus::batch($importImagesJobs)->onQueue('high')->dispatch();
        };

        $importProductJobs = [];
        $productPaths = $productsAndImages['products_paths'];

        /** @phpstan-ignore-next-line */
        foreach (array_chunk($productPaths, $this->entityCount) as $item) {
            $importProductJobs[] = new ImportProductsJob($item, $this->login, $this->password, $this->lang);
        }

        $jobsForBus[] = static function () use ($importProductJobs) {
            Bus::batch($importProductJobs)->onQueue('high')->dispatch();
        };

        Bus::chain($jobsForBus)->onQueue('high')->dispatch();
    }
}
