<?php
declare(strict_types=1);


namespace CSCart\Icecat\Importers;


use CSCart\Core\Catalog\Product\Enum\ProductStatusEnum;
use CSCart\Core\Catalog\Product\Model\Input\ProductFeatureValueInput;
use CSCart\Core\Catalog\Product\Model\Input\ProductInput;
use CSCart\Core\Catalog\Product\Model\Input\ProductOfferInput;
use CSCart\Core\Catalog\Product\Model\Input\ProductTranslationInput;
use CSCart\Core\Catalog\ProductFeature\Model\ProductFeature;
use CSCart\Core\Media\Model\Input\ImageInput;
use CSCart\Core\Media\Model\Input\ImageTranslationInput;
use CSCart\Core\App\Context\SystemContext;
use CSCart\Core\Seller\Model\Seller;
use CSCart\Framework\Database\Eloquent\Input\BelongsToManyRelationInput;
use CSCart\Framework\Database\Eloquent\Operation\CreateOperation;
use CSCart\Framework\Database\Eloquent\Repository;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use function Safe\simplexml_load_string;

/**
 * phpcs:disable Generic.Files.LineLength.TooLong, SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 */
class ProductsImporter
{
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
    public function import(): void
    {
        $client = new Client(['base_uri' => config('data-importer.base_url')]);
        $repository = app(Repository::class);
        $context = SystemContext::create();
        /** @var \CSCart\Core\Seller\Model\Seller $seller */
        $seller = Seller::find(1);
        $lang = $this->lang;

        $requestGenerator = function (array &$paths) use ($client) {
            foreach ($paths as $id => $path) {
                // The magic happens here, with yield key => value
                yield $path => function () use ($client, $path) {
                    // Our identifier does not have to be included in the request URI or headers
                    return $client->getAsync($path, [
                        'auth' => [$this->login, $this->password],
                    ]);
                };
                unset($paths[$id]);
            }
        };

        $pool = new Pool($client, $requestGenerator($this->paths), [
            'concurrency' => 32,
            'fulfilled' => static function (Response $response) use ($repository, $context, $lang, $seller) {
                try {
                    $body = $response->getBody();
                    $size = (int) $body->getSize();
                    $d = $body->read($size);

                    $productData = simplexml_load_string($d);
                    $productData = $productData->Product;

                    if (!$productData) {
                        return;
                    }

                    $offerInput = new ProductOfferInput();
                    $offerInput->price = (float) random_int(10, 1000);

                    $imageInput = new ImageInput();
                    $imageInput->upload = new UploadedFile(storage_path('app/images/') . basename((string) $productData['HighPic']), 'image.jpg', 'image/jpg', null, true);
                    $imageInput->translation = new ImageTranslationInput();
                    $imageInput->translation->lang_code = $lang;
                    $imageInput->translation->alt = 'Main image alt';

                    $productInput = new ProductInput();
                    $productInput->id = (int) $productData['ID'];
                    $productInput->code = (string) $productData['Prod_id'];
                    $productInput->status = ProductStatusEnum::ACTIVE;

                    $productInput->translation = new ProductTranslationInput();
                    $productInput->translation->lang_code = $lang;
                    $productInput->translation->name = html_entity_decode((string) $productData['Title']);
                    $productInput->translation->description = mb_substr(trim((string) $productData->ProductDescription['LongDesc']), 0, 254);
                    $productInput->offer = $offerInput;
                    $productInput->categories = new BelongsToManyRelationInput();
                    $productInput->categories->addAttachId((int) $productData->Category['ID']);
                    $productInput->images->create->push($imageInput);
                    $productInput->seller_id = $seller->id;

                    $productInput->feature_values = new Collection();

                    foreach ($productData->ProductFeature as $feature) {
                        $featureId = (int) $feature->Feature['ID'];
                        $value = mb_substr((string) $feature->LocalValue['Value'], 0, 255);
                        /** @var \CSCart\Core\Catalog\ProductFeature\Model\ProductFeature|null $featureModel */
                        $featureModel = ProductFeature::find($featureId);

                        if ($featureModel) {
                            $valueInput = new ProductFeatureValueInput();
                            $valueInput->feature_id = $featureId;
                            $valueInput->value = $value;
                            $productInput->feature_values->push($valueInput);
                        }
                    }

                    $repository->create(new CreateOperation($context, $productInput));
                    Storage::delete('/images/' . basename((string) $productData['HighPic']));
                } catch (Exception $exception) {
                    echo $exception->getMessage() . PHP_EOL;
                }
            },
            'rejected' => static function (Exception $reason) {
                // This callback is delivered each failed request
                echo $reason->getMessage() . PHP_EOL;
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }
}
