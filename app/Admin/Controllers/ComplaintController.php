<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\PurchaseOrder;
use App\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Illuminate\Http\Request;

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
        $grid->order_id('Mã đơn hàng')->display(function () {
            return $this->order->order_number ?? "";
        });
        $grid->image('Ảnh sản phẩm')->lightbox(['width' => 100, 'height' => 100]);
        $grid->item_name('Tên sản phẩm');
        $grid->item_price('Giá sản phẩm');
        $grid->content('Nội dung khuyếu nại');
        $grid->status('Trạng thái')->display(function () {
            return "<span class='label label-".Complaint::LABEL[$this->status]."'>".Complaint::STATUS[$this->status]."</span>";
        });

        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });

        return $grid;
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function showComplaint($id, Content $content)
    {
        return $content
        ->title($this->title())
        ->description($this->description['show'] ?? trans('admin.show'))
        ->row(function (Row $row) use ($id)
        {
            if (Admin::user()->can('admin-handle-complaint') && in_array(Complaint::find($id)->status, [Complaint::PROCESS_NORMAL, Complaint::PROCESS_AGENT])) {
                $row->column(12, function (Column $column) use ($id) 
                {
                    $column->append((new Box('', $this->AdminConfirmSuccess($id))));
                });
            }

            if (Admin::user()->isRole('customer_order') && Complaint::find($id)->status == Complaint::ADMIN_CONFIRM_SUCCESS) {
                $row->column(12, function (Column $column) use ($id) 
                {
                    $column->append((new Box('', $this->CustomerConfirmSuccess($id))));
                });
            }

            $row->column(12, function (Column $column) use ($id) 
            {
                $column->append((new Box('', $this->detail($id))));
            });

            $row->column(12, function (Column $column) use ($id) 
            {
                $column->append((new Box('', $this->listComment($id)->render())));
            });

            $row->column(12, function (Column $column) use ($id)
            {
                $column->append((new Box('', $this->formSubComment($id))));
            });
        });
    }

    public function AdminConfirmSuccess($id) {
        return view('admin.admin-confirm-success-complaint', compact('id'))->render();
    }

    public function CustomerConfirmSuccess($id) {
        return view('admin.customer-confirm-success-complaint', compact('id'))->render();
    }

    public function storeAdminConfirmSuccess(Request $request) {
        Complaint::find($request->id)->update([
            'status'    =>  Complaint::ADMIN_CONFIRM_SUCCESS
        ]);

        admin_toastr('Lưu thành công', 'success');
        return redirect()->back();
    }

    public function storeCustomerConfirmSuccess(Request $request) {
        Complaint::find($request->id)->update([
            'status'    =>  Complaint::DONE
        ]);

        admin_toastr('Lưu thành công', 'success');
        return redirect()->back();
    }

    public function listComment($id) {
        $grid = new Grid(new ComplaintComment());
        $grid->model()->where('complaint_id', $id);

        $grid->user_created_id('Người tạo')->display(function () {
            return User::find($this->user_created_id)->name ?? "";
        })->width(200);

        $grid->content('Nội dung')->width(800);

        $grid->created_at(trans('admin.created_at'))->display(function () {
            return date('H:i | d-m-Y', strtotime($this->created_at));
        });

        $grid->disableActions();
        $grid->disableColumnSelector();
        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->disablePagination();
        $grid->disableDefineEmptyPage();

        Admin::style('
            .grid-box {
                margin: 0px !important;
                border: none !important;
            }   
            .box-footer {
                padding: 0px !important;
            }

        ');

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
        $show->status('Trạng thái')->as(function ($content) {
            return Complaint::STATUS[$this->status];
        });
        $show->customer_id('Mã khách hàng')->as(function () {
            return $this->customer->symbol_name ?? "";
        });
        $show->order_id('Mã đơn hàng')->as(function () {
            return $this->order->order_number ?? "";
        });
        $show->image('Ảnh sản phẩm')->image("", 100, 100);
        $show->item_name('Tên sản phẩm');
        $show->item_price('Giá sản phẩm');
        $show->content('Nội dung khuyếu nại');

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

        $form->select('order_id', 'Mã đơn hàng')->options(PurchaseOrder::whereCustomerId(Admin::user()->id)->get()->pluck('order_number', 'id'));
        $form->multipleImage('image', 'Ảnh sản phẩm');
        $form->text('item_name', 'Tên sản phẩm');
        $form->text('item_price', 'Giá sản phẩm');
        $form->textarea('content', 'Nội dung khuyếu nại');
        $form->hidden('customer_id')->default(Admin::user()->id);

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        return $form;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function formSubComment($id)
    {
        $form = new Form(new ComplaintComment());

        $form->setAction(route('admin.complaints.addComment'));
        $form->text('content', 'Nội dung bình luận');
        $form->hidden('complaint_id')->default($id);
        
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableList();
        });

        Admin::style('
            .box-footer {
                padding: 0px !important;
                border: none !important;
            }
            .box {
                box-shadow: none;
            }
            .box.box-info {
                border: none;
            }
            .box-header {
                display: none !important;
            }
        ');

        return $form;
    }

    public function addComment(Request $request) {
        $data = $request->all();

        $data['user_created_id'] = Admin::user()->id;
        ComplaintComment::create($data);

        if (Admin::user()->can('admin-handle-complaint')) {
            Complaint::find($data['complaint_id'])->update([
                'status'    =>  Complaint::PROCESS_NORMAL
            ]);
        }

        admin_toastr('Lưu thành công', 'success');
        return redirect()->back();
    }
}
