<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Complaint;
use App\User;

class ComplaintController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Khuyếu nại đơn hàng';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Complaint);

        $grid->filter(function($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->like('title', trans('admin.title'));
        });

        $grid->rows(function (Grid\Row $row) {
            $row->column('number', ($row->number+1));
        });
        $grid->column('number', 'STT');
        $grid->order_number('Mã đơn hàng');
        $grid->cn_code('Mã vận đơn');
        $grid->column('image', 'Ảnh sản phẩm')->lightbox(['width' => 100, 'height' => 100]);
        $grid->reason('Lý do')->editable();
        $grid->solution('Phương án xử lý')->editable();
        $grid->sale_staff_id('Nhân viên kinh doanh')->display(function () {
            return $this->saleStaff->name ?? "";
        });
        $grid->order_staff_id('Nhân viên đặt hàng')->display(function () {
            return $this->orderStaff->name ?? "";
        });

        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
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
        $show = new Show(Complaint::findOrFail($id));

        $show->field('id', trans('admin.id'));
        $show->title(trans('admin.title'));
        $show->order(trans('admin.order'));
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
        $form = new Form(new Complaint);

        $form->text('order_number', 'Mã đơn hàng');
        $form->text('cn_code', 'Mã vận đơn');
        $form->image('image','Ảnh sản phẩm')->thumbnail('small', $width = 150, $height = 150);
        $form->text('reason', 'Lý do');
        $form->text('solution', 'Phương án xử lý');
        $form->select('sale_staff_id', 'Nhân viên kinh doanh')->options(User::whereIsCustomer(0)->pluck('name', 'id'));
        $form->select('order_staff_id', 'Nhân viên đặt hàng')->options(User::whereIsCustomer(0)->pluck('name', 'id'));
        

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
