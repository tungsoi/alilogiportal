<table style="font-size: 16px;" class="table table-bordered">
    <thead>
        <tr style="background: #615ca8; color: white;">
            <th>Mã đơn hàng</th>
            <th>Trạng thái</th>
            <th>Tỷ giá</th>

            @if (isset($role) && $role == 'admin')
            <th>Ngày tạo</th>
            @endif
        </tr>
        <tr>
            <td style="width: 20%"><b>{{ $order->order_number.' / '.$order->customer->symbol_name }}</b></td>
            <td style="width: 20%">{!! $status !!}</td>
            
            @if (isset($role) && $role == 'admin')
                <td style="width: 20%">{{ $order->current_rate }}</td>
                <td style="width: 40%">{{ date('H:i | d-m-Y', strtotime($order->created_at)) }}</td>
            @else
                <td style="width: 60%">{{ $order->current_rate }}</td>
            @endif
        </tr>
    </thead>
</table>

<br> <br>
@php
    if (isset($role) && $role == 'admin')
    {
        $style = "font-size: 14px";
    } 
    else {
        $style = "font-size: 16px";
    }
@endphp
<table style="{{ $style }}" class="table table-bordered">
    <tbody>   
        <tr>
            <td style="width: 40%">Tổng số lượng</td>
            <td style="width: 10%">{{ $qty }}</td>
            
            @if (isset($role) && $role == 'admin')
                <td style="width: 20%"></td>
                <td style="width: 15%">Kho</td>
                <td style="width: 15%">{{ $order->warehouse->name ?? "" }}</td>
            @else
                <td style="width: 50%"></td>
            @endif
        </tr>  
        <tr>
            <td>Tổng thực đặt</td>
            <td>{{ $qty_reality }}</td>
            @if (isset($role) && $role == 'admin')
                <td></td>
                <td>Nhân viên Sale</td>
                <td>{{ $order->supporter->name ?? "" }}</td>
            @else
                <td></td>
            @endif
        </tr>  
        <tr>
            <td>Tổng tiền thực đặt</td>
            <td>{{ number_format($total_price_reality, 2) . " Tệ" }}</td>
            
            @if (isset($role) && $role == 'admin')
                <td>{{ number_format($total_price_reality * $current_rate) . " VND" }}</td>
                <td>Nhân viên Order</td>
                <td>{{ $order->supporterOrder->name ?? "..." }}</td>
            @else
                <td>{{ number_format($total_price_reality * $current_rate) . " VND" }}</td>
            @endif
        </tr>  
        <tr>
            <td>Tổng phí dịch vụ</td>
            <td>{{ number_format($order->purchase_order_service_fee, 2) . " Tệ "}}</td>

            @if (isset($role) && $role == 'admin')
                <td>{{ number_format($order->purchase_order_service_fee * $current_rate) . " VND"  }}</td>
                <td>Nhân viên Kho</td>
                <td>{{ $order->supporterWarehouse->name ?? "..." }}</td>
            @else
                <td>{{ number_format($order->purchase_order_service_fee * $current_rate) . " VND"  }}</td>
            @endif
        </tr>  
        <tr>
            <td>Tổng phí ship nội địa Trung Quốc</td>
            <td>{{ number_format($purchase_cn_transport_fee, 2) . " Tệ" }}</td>
            <td>{{ number_format($purchase_cn_transport_fee * $current_rate) . " VND"  }}</td>
            @if (isset($role) && $role == 'admin')
                <td>Ngày đặt hàng</td>
                <td>{{ $order->order_at ?? '...' }}</td>
            @endif
        </tr>  
        <tr>
            <td>Tổng giá trị đơn hàng = Tổng tiền thực đặt + ship nội địa + dịch vụ</td>
            <td>{{ number_format($total_bill, 2) . " Tệ" }}</td>
            <td>{{ number_format($total_bill * $current_rate) . " VND" }}</td>

            @if (isset($role) && $role == 'admin')
                <td>Ngày chốt đơn</td>
                <td>{{ $order->success_at ?? "..." }}</td>
            @endif
        </tr>
        <tr style="background: green; color: white;">
            <td>Số tiền cần cọc</td>
            <td></td>
            <td>{{ number_format($order->deposit_default) . " VND" }}</td>
        </tr>
        <tr>
            <td>Số tiền đã cọc</td>
            <td></td>
            <td>{{ number_format($order->deposited) . " VND"}}</td>
        </tr>
        <tr style="background: coral; color: white;">
            <td>Số tiền còn thiếu</td>
            <td></td>
            <td>{{ number_format( ($total_bill * $current_rate) - $order->deposited) . " VND" }}</td>
        </tr>   
    </tbody>    
</table>