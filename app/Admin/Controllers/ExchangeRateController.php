<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\ExchangeRate;

class ExchangeRateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Tỷ giá chuyển đổi';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ExchangeRate);

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->vnd('Việt Nam đồng')->editable();
        $grid->ndt('Nhân dân tệ')->editable();
        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });
        $grid->updated_at(trans('admin.updated_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->updated_at));
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ExchangeRate::findOrFail($id));

        $show->field('id', trans('admin.id'));
        $show->vnd('Việt Nam đồng');
        $show->ndt('Nhân dân tệ');
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ExchangeRate);

        $form->text('vnd', 'Việt Nam đồng')->rules('required');
        $form->text('ndt', 'Nhân dân tệ')->rules('required');
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
