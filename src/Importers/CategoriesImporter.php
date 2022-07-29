<?php
declare(strict_types=1);


namespace CSCart\Icecat\Importers;

use CSCart\Core\Catalog\Category\Model\Category;
use CSCart\Core\Catalog\Category\Model\Input\CategoryInput;
use CSCart\Core\Catalog\Category\Model\Input\CategoryTranslationInput;
use CSCart\Core\App\Context\SystemContext;
use CSCart\Framework\Database\Eloquent\Operation\UpdateOperation;
use CSCart\Framework\Database\Eloquent\Repository;
use CSCart\Framework\Enum\ObjectStatusEnum;
use Exception;

class CategoriesImporter
{
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
    public function import(): void
    {
        $repository = app(Repository::class);
        $context = SystemContext::create();

        foreach ($this->categories as $id => $category) {
            try {
                $categoryModel = new Category();
                $categoryModel->id = $id;
                $categoryModel->status = ObjectStatusEnum::ACTIVE;
                $categoryModel->path = $id;
                $categoryModel->save();

                $categoryData = new CategoryInput();
                $categoryData->translation = new CategoryTranslationInput();
                $categoryData->translation->lang_code = $this->lang;
                $categoryData->translation->name = $category['name'];
                $categoryData->translation->description = $category['name'];

                $repository->update(new UpdateOperation($context, $categoryData, $categoryModel));
            } catch (Exception $exception) {
                echo $exception->getMessage() . PHP_EOL;
            }
        }
    }
}
