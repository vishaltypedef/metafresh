<?php

namespace Botble\Blog\Repositories\Eloquent;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Blog\Repositories\Interfaces\CategoryInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Eloquent;

class CategoryRepository extends RepositoriesAbstract implements CategoryInterface
{
    public function getDataSiteMap()
    {
        $data = $this->model
            ->with('slugable')
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->select(['id', 'name', 'updated_at'])
            ->orderBy('created_at', 'desc');

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    public function getFeaturedCategories($limit, array $with = [])
    {
        $data = $this->model
            ->with(array_merge(['slugable'], $with))
            ->where([
                'status' => BaseStatusEnum::PUBLISHED,
                'is_featured' => 1,
            ])
            ->select([
                'id',
                'name',
                'description',
                'icon',
            ])
            ->orderBy('order')
            ->limit($limit);

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    public function getAllCategories(array $condition = [], array $with = [])
    {
        $data = $this->model->with('slugable');
        if (! empty($condition)) {
            $data = $data->where($condition);
        }

        $data = $data
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->orderBy('order', 'DESC')
            ->orderBy('created_at', 'DESC');

        if ($with) {
            $data = $data->with($with);
        }

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    public function getCategoryById($id)
    {
        $data = $this->model->with('slugable')->where([
            'id' => $id,
            'status' => BaseStatusEnum::PUBLISHED,
        ]);

        return $this->applyBeforeExecuteQuery($data, true)->first();
    }

    public function getCategories(array $select, array $orderBy, array $conditions = ['status' => BaseStatusEnum::PUBLISHED])
    {
        $data = $this->model
            ->with('slugable')
            ->select($select);

        if ($conditions) {
            $data = $data->where($conditions);
        }

        foreach ($orderBy as $by => $direction) {
            $data = $data->orderBy($by, $direction);
        }

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    public function getAllRelatedChildrenIds($id)
    {
        if ($id instanceof Eloquent) {
            $model = $id;
        } else {
            $model = $this->getFirstBy(['id' => $id]);
        }

        if (! $model) {
            return null;
        }

        $result = [];

        $children = $model->children()->select('id')->get();

        foreach ($children as $child) {
            $result[] = $child->id;
            $result = array_merge($this->getAllRelatedChildrenIds($child), $result);
        }

        $this->resetModel();

        return array_unique($result);
    }

    public function getAllCategoriesWithChildren(array $condition = [], array $with = [], array $select = ['*'])
    {
        $data = $this->model
            ->where($condition)
            ->with($with)
            ->select($select);

        return $this->applyBeforeExecuteQuery($data)->get();
    }

    public function getFilters($filters)
    {
        $this->model = $this->originalModel;

        $orderBy = $filters['order_by'] ?? 'created_at';

        $order = $filters['order'] ?? 'desc';

        $this->model = $this->model->where('status', BaseStatusEnum::PUBLISHED)->orderBy($orderBy, $order);

        return $this->applyBeforeExecuteQuery($this->model)->paginate((int)$filters['per_page']);
    }

    public function getPopularCategories(int $limit, array $with = ['slugable'], array $withCount = ['posts'])
    {
        $data = $this->model
            ->with($with)
            ->withCount($withCount)
            ->orderBy('posts_count', 'desc')
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->limit($limit);

        return $this->applyBeforeExecuteQuery($data)->get();
    }
}
