<?php

namespace App\Admin\Actions\Extensions;

use App\Models\PurchaseOrder;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Facades\Excel;

class OrdersExporter extends AbstractExporter
{
    public function export()
    {
        Excel::create('DS_Don_hang_Order', function($excel) {

            $excel->sheet('Sheet1', function(LaravelExcelWorksheet $sheet) {

                $this->chunk(function ($records) use ($sheet) {

                    $data = [];
                    $data[] = $this->header();

                    $flag = 1;
                    $rows = $records->map(function ($item) use ($flag, $data) {

                        $res = [
                            $flag,
                            $item->order_number,
                            $item->current_rate,
                            $item->created_at,
                            $item->customer->name,
                            PurchaseOrder::STATUS[$item->status],
                            $item->supporter->name ?? "",
                            $item->supporterOrder->name ?? "",
                            $item->supporterWarehouse->name ?? "",
                            $item->totalItemReality(),
                            $item->sumQtyRealityMoney(),
                            $item->purchase_order_service_fee,
                            $this->totalShip($item),
                            $this->totalWeight($item),
                            $item->price_weight,
                            $item->warehouse->name ?? "",
                            $item->deposited,
                            $item->deposited != "" ? $item->deposited_at : "",
                            $item->totalBill(),
                            $item->admin_note,
                            $item->internal_note
                        ];

                        $flag++;

                        return $res;
                    });

                    $sheet->rows($rows->first());

                });

            });

        })->export('xls');
    }

    public function header()
    {
        return [
            'STT', 
            'Mã đơn hàng', 
            'Tỷ giá', 
            'Ngày tạo', 
            'Mã khách hàng',
            'Trạng thái',
            'Nhân viên kinh doanh',
            'Nhân viên đặt hàng',
            'Nhân viên kho',
            'Số sản phẩm',
            'Tổng giá sản phẩm (Tệ)',
            'Phí dịch vụ (Tệ)',
            'Tổng phí VCND (Tệ)',
            'Tổng KG',
            'Giá KG (VND)',
            'Kho',
            'Đã cọc (VND)',
            'Ngày cọc',
            'Tổng giá cuối (Tệ)',
            'Admin ghi ghú',
            'Nội bộ ghi chú'
        ];
    }

    public function totalShip($order)
    {
        # code...
        if ($order->items) {
            $total = 0;
            foreach ($order->items as $item) {
                $total += $item->purchase_cn_transport_fee;
            }

            return $total;
        }

        return 0;

    }

    public function totalWeight($order)
    {
        # code...
        if ($order->items) {
            $total = 0;
            foreach ($order->items as $item) {
                $total += $item->weight;
            }

            return $total;
        }

        return 0;

    }
}