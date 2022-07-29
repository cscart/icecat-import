<?php
declare(strict_types=1);


namespace CSCart\Icecat;

use CSCart\Core\Catalog\ProductFeature\Enum\ProductFeatureTypeEnum;
use XMLReader;
use function Safe\simplexml_load_string;

class DataParser
{
    private string $gzip_prefix;
    private string $lang;
    private int $limit;

    /**
     * @param string $lang
     * @param int    $limit
     */
    public function __construct(string $lang, int $limit)
    {
        $this->gzip_prefix = 'compress.zlib://';
        $this->lang = $lang;
        $this->limit = $limit;
    }

    /**
     * @param string $categoryLocalPath
     *
     * @return array
     */
    public function getCategories(string $categoryLocalPath): array
    {
        $reader = new XMLReader();
        $reader->open($this->gzip_prefix . $categoryLocalPath);

        $langNumber = $this->getNumericLanguageCode($this->lang);
        $categories = [];

        //phpcs:ignore
        while ($reader->read() && $reader->name !== 'Category') {};

        while ($reader->name === 'Category') {
            $category = simplexml_load_string($reader->readOuterXml());

            if (!$category->Name) {
                $reader->next('Category');
                continue;
            }

            $name = '';
            foreach ($category->Name as $name_node) {
                if ((int) $name_node['langid'] === $langNumber) {
                    $name = (string) $name_node['Value'];
                    break;
                }
            }

            /** @psalm-suppress PossiblyNullArrayOffset */
            $categories[$reader->getAttribute('ID')] = [
                'name'      => $name,
                'parent_id' => (string) $category->ParentCategory['ID'],
            ];

            $reader->next('Category');
        }

        return $categories;
    }

    /**
     * @param string $featureLocalPath
     *
     * @return array
     */
    public function getFeatures(string $featureLocalPath): array
    {
        $reader = new XMLReader();
        $reader->open($this->gzip_prefix . $featureLocalPath);

        $langNumber = $this->getNumericLanguageCode($this->lang);
        $features = [];

        // phpcs:ignore
        while ($reader->read() && $reader->name !== 'Feature') {}

        while ($reader->name === 'Feature') {
            $feature = simplexml_load_string($reader->readOuterXml());
            if (!$feature->Names) {
                $reader->next('Feature');
                continue;
            }

            $name = '';
            foreach ($feature->Names->Name as $name_node) {
                if ((int) $name_node['langid'] === $langNumber) {
                    $name = (string) $name_node[0];
                    break;
                }
            }

            $type = $this->resolveFeatureType((string) $feature['Type']);

            $variants = [];

            if ($type->isSelectable()) {
                foreach ($feature->RestrictedValues->RestrictedValue as $value_node) {
                    $variants[] = (string) $value_node;
                }
            }

            /** @psalm-suppress PossiblyNullArrayOffset */
            $features[$reader->getAttribute('ID')] = [
                'name'     => $name,
                'type'     => $type,
                'variants' => $variants
            ];

            $reader->next('Feature');
        }

        return $features;
    }

    /**
     * Gets products paths from index file
     *
     * @param string $productLocalPath
     *
     * @return array
     */
    public function getProductsAndImagesPaths(string $productLocalPath): array
    {
        $productPaths = [];
        $imagePaths = [];
        $added = 0;
        $reader = new XMLReader();
        $reader->open($this->gzip_prefix . $productLocalPath);

        // phpcs:ignore
        while ($reader->read() && $reader->name !== 'file') {}

        while ($reader->name === 'file' && $added < $this->limit) {
            $productId = $reader->getAttribute('Product_ID');
            $path = $reader->getAttribute('path');
            $imagePath = $reader->getAttribute('HighPic');

            if ($productId !== null && $path !== null) {
                $productPaths[$productId] = $path;
                $imagePaths[$productId] = $imagePath;
                $added++;
            }

            $reader->next('file');
        }
        $reader->close();

        return [
            'products_paths' => $productPaths,
            'images_paths' => $imagePaths,
        ];
    }

    /**
     * @param string $letterLangCode
     *
     * @return int
     */
    public function getNumericLanguageCode(string $letterLangCode): int
    {
        $default_lang_code = 1;  //EN
        $letterLangCode = strtoupper($letterLangCode);

        $schema = [
            'MK'    => 33,
            'KO'    => 32,
            'BG'    => 21,
            'DK'    => 7,
            'JA'    => 26,
            'FI'    => 17,
            'NL'    => 2,
            'EN'    => 1,
            'EL'    => 18,
            'AR'    => 30,
            'HU'    => 16,
            'SK'    => 44,
            'CA'    => 27,
            'UK'    => 25,
            'ES_AR' => 28,
            'LV'    => 40,
            'TR'    => 20,
            'PL'    => 14,
            'FR_BE' => 49,
            'SR'    => 24,
            'BR'    => 10,
            'VI'    => 31,
            'EN_SG' => 35,
            'PT'    => 11,
            'DE_BE' => 48,
            'DE_CH' => 42,
            'KA'    => 22,
            'ES_MX' => 46,
            'SV'    => 13,
            'RO'    => 23,
            'HR'    => 29,
            'ES'    => 6,
            'NL_BE' => 50,
            'LT'    => 39,
            'EN_ZA' => 36,
            'FR'    => 3,
            'TH'    => 51,
            'US'    => 9,
            'EN_IN' => 41,
            'ZH'    => 12,
            'ET'    => 47,
            'CZ'    => 15,
            'HE'    => 38,
            'RU'    => 8,
            'DE'    => 4,
            'SL'    => 34,
            'FA'    => 45,
            'ZH_TW' => 37,
            'ID'    => 43,
            'NO'    => 19,
            'IT'    => 5,
        ];

        return $schema[$letterLangCode] ?? $default_lang_code;
    }

    /**
     * @param string $type
     *
     * @return \CSCart\Core\Catalog\ProductFeature\Enum\ProductFeatureTypeEnum
     */
    private function resolveFeatureType(string $type): ProductFeatureTypeEnum
    {
        return match ($type) {
            'numerical'      => ProductFeatureTypeEnum::NUMBER,
            'y_n'            => ProductFeatureTypeEnum::CHECKBOX,
            'alphanumeric'   => ProductFeatureTypeEnum::TEXT,
            'text'           => ProductFeatureTypeEnum::TEXT,
            'dropdown'       => ProductFeatureTypeEnum::TEXT_SELECTBOX,
            'multi_dropdown' => ProductFeatureTypeEnum::MULTIPLE_CHECKBOX,
            default          => ProductFeatureTypeEnum::TEXT
        };
    }
}
