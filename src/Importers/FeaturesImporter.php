<?php
declare(strict_types=1);


namespace CSCart\Icecat\Importers;


use CSCart\Core\Catalog\ProductFeature\Model\Input\ProductFeatureInput;
use CSCart\Core\Catalog\ProductFeature\Model\Input\ProductFeatureTranslationInput;
use CSCart\Core\Catalog\ProductFeature\Model\Input\ProductFeatureVariantInput;
use CSCart\Core\Catalog\ProductFeature\Model\Input\ProductFeatureVariantTranslationInput;
use CSCart\Core\Catalog\ProductFeature\Model\ProductFeature;
use CSCart\Core\App\Context\SystemContext;
use CSCart\Framework\Database\Eloquent\Operation\UpdateOperation;
use CSCart\Framework\Database\Eloquent\Repository;
use Exception;

class FeaturesImporter
{
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
    public function import(): void
    {
        $repository = app(Repository::class);
        $context = SystemContext::create();

        foreach ($this->features as $id => $feature) {
            try {
                $featureModel = new ProductFeature();
                $featureModel->id = $id;
                $featureModel->save();

                $featureData = new ProductFeatureInput();
                $featureData->type = $feature['type'];
                $featureData->translation = new ProductFeatureTranslationInput();
                $featureData->translation->lang_code = $this->lang;
                $featureData->translation->name = $feature['name'];
                $featureData->translation->description = $feature['name'];

                foreach ($feature['variants'] as $variant) {
                    $variantData = new ProductFeatureVariantInput();
                    $variantData->translation = new ProductFeatureVariantTranslationInput();
                    $variantData->translation->name = $variant;
                    $featureData->variants->create->push($variantData);
                }

                $repository->update(new UpdateOperation($context, $featureData, $featureModel));
            } catch (Exception $exception) {
                echo $exception->getMessage() . PHP_EOL;
            }
        }
    }
}
