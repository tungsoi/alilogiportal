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

                    $flag = 1;
                    $rows = $records->map(function ($item) use ($flag) {

                        $res = [
                            $flag,
                            $item->order_number,
                            $item->current_rate,
                            $item->created_at,
                            $item->customer->symbol_name,
                            PurchaseOrder::STATUS[$item->status],
                            $item->supporter->name ?? "",
                            $item->supporterOrder->name ?? "",
                            $item->supporterWarehouse->name ?? "",
                            $item->totalItemReality(),
                            $item->sumQtyRealityMoney(),
                            $item->purchase_order_service_fee,
                            $this->totalShip($item),
                            $this->totalWeight($item),
                            $item->price_weight != "" ? $item->price_weight : 0,
                            $item->warehouse->name ?? "",
                            $item->deposited != "" ? $item->deposited : 0,
                            $item->deposited != "" ? $item->deposited_at : "",
                            $item->order_at,
                            $item->success_at,
                            $item->totalBill(),
                            $item->admin_note,
                            $item->internal_note
                        ];

                        $flag++;

                        return $res;
                    });
                    $rows->prepend($this->header());

                    $sheet->rows($rows);

                });

            });

        })->export('xls');
    }

    public function header()
    {
        return [
            'STT', 
            'M?? ????n h??ng', 
            'T??? gi??', 
            'Ng??y t???o', 
            'M?? kh??ch h??ng',
            'Tr???ng th??i',
            'Nh??n vi??n kinh doanh',
            'Nh??n vi??n ?????t h??ng',
            'Nh??n vi??n kho',
            'S??? s???n ph???m',
            'T???ng gi?? s???n ph???m (T???)',
            'Ph?? d???ch v??? (T???)',
            'T???ng ph?? VCND (T???)',
            'T???ng KG',
            'Gi?? KG (VND)',
            'Kho',
            '???? c???c (VND)',
            'Ng??y c???c',
            'Ng??y ?????t h??ng',
            'Ng??y th??nh c??ng',
            'T???ng gi?? cu???i (T???)',
            'Admin ghi gh??',
            'N???i b??? ghi ch??'
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