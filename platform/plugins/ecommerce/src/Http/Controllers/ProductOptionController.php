<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Forms\GlobalOptionForm;
use Botble\Ecommerce\Http\Requests\GlobalOptionRequest;
use Botble\Ecommerce\Repositories\Interfaces\GlobalOptionInterface;
use Botble\Ecommerce\Tables\GlobalOptionTable;
use Exception;
use Illuminate\Http\Request;

class ProductOptionController extends BaseController
{
    protected GlobalOptionInterface $globalOptionRepository;

    public function __construct(GlobalOptionInterface $globalOptionRepository)
    {
        $this->globalOptionRepository = $globalOptionRepository;
    }

    public function index(GlobalOptionTable $table)
    {
        page_title()->setTitle(trans('plugins/ecommerce::product-option.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        page_title()->setTitle(trans('plugins/ecommerce::product-option.create'));

        return $formBuilder->create(GlobalOptionForm::class)->renderForm();
    }

    public function store(GlobalOptionRequest $request, BaseHttpResponse $response)
    {
        $option = $this->globalOptionRepository->createOrUpdate($request->input());

        event(new CreatedContentEvent(GLOBAL_OPTION_MODULE_SCREEN_NAME, $request, $option));

        return $response
            ->setPreviousUrl(route('global-option.index'))
            ->setNextUrl(route('global-option.edit', $option->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int $id, FormBuilder $formBuilder, Request $request)
    {
        $option = $this->globalOptionRepository->findOrFail($id, ['values']);

        event(new BeforeEditContentEvent($request, $option));

        page_title()->setTitle(trans('plugins/ecommerce::product-option.edit', ['name' => $option->name]));

        return $formBuilder->create(GlobalOptionForm::class, ['model' => $option])->renderForm();
    }

    public function destroy(Request $request, int $id, BaseHttpResponse $response)
    {
        try {
            $option = $this->globalOptionRepository->findOrFail($id);

            $this->globalOptionRepository->delete($option);

            event(new DeletedContentEvent(GLOBAL_OPTION_MODULE_SCREEN_NAME, $request, $option));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function update(int $id, GlobalOptionRequest $request, BaseHttpResponse $response)
    {
        $option = $this->globalOptionRepository->findOrFail($id);

        $this->globalOptionRepository->createOrUpdate($request->input(), ['id' => $id]);

        event(new UpdatedContentEvent(GLOBAL_OPTION_MODULE_SCREEN_NAME, $request, $option));

        return $response
            ->setPreviousUrl(route('global-option.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function deletes(Request $request, BaseHttpResponse $response)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $option = $this->globalOptionRepository->findOrFail($id);
            $this->globalOptionRepository->delete($option);
            event(new DeletedContentEvent(GLOBAL_OPTION_MODULE_SCREEN_NAME, $request, $option));
        }

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    }

    public function ajaxInfo(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        $optionsValues = $this->globalOptionRepository->findOrFail($request->input('id'), ['values']);

        return $response->setData($optionsValues);
    }
}
