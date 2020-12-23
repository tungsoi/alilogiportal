<?php

namespace App\Admin\Actions\Extensions;

use App\Models\PurchaseOrder;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Facades\Excel;

class OffersExporter extends AbstractExporter
{
    public function export()
    {
        Excel::create('THONG_KE_DAM_PHAN', function($excel) {

            $excel->sheet('Sheet1', function(LaravelExcelWorksheet $sheet) {

                $this->chunk(function ($records) use ($sheet) {
                    return [];
                    $flag = 1;
                    $rows = $records->map(function ($item) use ($flag) {
                        try {
                            $ship = $this->totalShip($item);
                            $reality = $item->sumQtyRealityMoney();
    
                            if ($item->final_payment != "" && $item->final_payment > 0) {
                                $discount = $reality + $ship - $item->final_payment;
                            }
                            else {
                                $discount = 0;
                            }
                            $res = [
                                $flag,
                                $item->order_number,
                                $item->customer->symbol_name,
                                PurchaseOrder::STATUS[$item->status],
                                $item->order_at ?? "",
                                $item->supporterOrder->name ?? "",
                                $reality,
                                $ship,
                                $item->final_payment,
                                $discount,
                                $discount * $item->current_rate
                            ];
    
                            $flag++;
    
                            return $res;
                        }
                        catch (\Exception $e) {
                            dd($e->getMessage());
                            dd($item);
                        }    
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
            'Mã đơn hàng', 
            'Mã khách hàng',
            'Trạng thái',
            'Ngày đặt',
            'Nhân viên Order',
            'Tiền thực đặt (Tệ)',
            'Tổng phí VCNĐ (Tệ)',
            'Tiền thanh toán (Tệ)',
            'Chiết khấu (Tệ)',
            'Chiết khấu (VND)'
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