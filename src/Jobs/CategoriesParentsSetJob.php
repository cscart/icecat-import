<?php
declare(strict_types=1);


namespace CSCart\Icecat\Jobs;

use CSCart\Core\Catalog\Category\Model\Category;
use CSCart\Core\Catalog\Category\Model\Input\CategoryInput;
use CSCart\Core\App\Context\SystemContext;
use CSCart\Framework\Database\Eloquent\Input\BelongsToRelationInput;
use CSCart\Framework\Database\Eloquent\Operation\UpdateOperation;
use CSCart\Framework\Database\Eloquent\Repository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CategoriesParentsSetJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private array $categories;

    /**
     * @param array $categories
     */
    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $repository = app(Repository::class);
        $context = SystemContext::create();

        foreach ($this->categories as $id => $categoryData) {
            try {
                $parentId = $categoryData['parent_id'];
                /** @var \CSCart\Core\Catalog\Category\Model\Category $parentModel */
                $parentModel = Category::find($parentId);

                $inputData = new CategoryInput();
                $inputData->parent = new BelongsToRelationInput();
                $inputData->parent->setAssociateModel($parentModel);

                $categoryModel = Category::find($id);
                $repository->update(new UpdateOperation($context, $inputData, $categoryModel));
            } catch (Throwable $exception) {
                echo 'ERROR' . $exception->getMessage() . PHP_EOL;
            }
        }
    }
}
