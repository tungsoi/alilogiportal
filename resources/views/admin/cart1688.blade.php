<div class="box box-info">
    <div class="box-header with-border">
       <h3 class="box-title">Tạo mới đon hàng 1688</h3>
       <div class="box-tools">
          <div class="btn-group pull-right" style="margin-right: 5px">
             <a href="http://127.0.0.1:8003/admin/carts" class="btn btn-sm btn-default" title="Danh sách"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;Danh sách</span></a>
          </div>
       </div>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form action="{{ route('admin.carts.storeAdd1688') }}" method="post" class="form-horizontal model-form-5fb4b7979388b" accept-charset="UTF-8" enctype="multipart/form-data" pjax-container="">
       {{ csrf_field() }}
        <div class="box-body">
          <div class="fields-group">
             <div class="col-md-12">
                 <table class="table table-bordered">
                     <thead>
                         <th>Tên shop</th>
                         <th>Tên sản phẩm</th>
                         <th>Link sản phẩm</th>
                         <th>Ảnh sản phẩm</th>
                         <th>Size sản phẩm</th>
                         <th>Màu sắc sản phẩm</th>
                         <th>Số lượng</th>
                         <th>Giá sản phẩm (Tệ)</th>
                         <th>Ghi chú của bạn</th>
                     </thead>
                     @if ($items->count() > 0)
                        @foreach ($items as $key => $item)
                            <tr>
                                <td>
                                    <input value="{{ $item->shop_name }}" type="text" name="shop_name[{{ $item->id }}]" value="" class="form-control shop_name" placeholder="Nhập vào Tên shop">   
                                </td>
                                <td>
                                    <input value="{{ $item->product_name }}" type="text" name="product_name[{{ $item->id }}]" value="" class="form-control product_name" placeholder="Nhập vào Tên sản phẩm">
                                </td>
                                <td>
                                    <input value="{{ $item->product_link }}" type="text" name="product_link[{{ $item->id }}]" value="" class="form-control product_link" placeholder="Nhập vào Link sản phẩm">
                                </td>
                                <td>
                                    <img src="{{ $item->product_image }}" alt="" style="width: 100px; border: 1px solid gray">
                                </td>
                                <td>
                                    <input value="{{ $item->product_size }}" type="text" name="product_size[{{ $item->id }}]" value="" class="form-control product_size" placeholder="Nhập vào Size sản phẩm">
                                </td>
                                <td>
                                    <input value="{{ $item->product_color }}" type="text" name="product_color[{{ $item->id }}]" value="" class="form-control product_color" placeholder="Nhập vào Màu sắc sản phẩm">
                                </td>
                                <td>
                                    <input value="{{ $item->qty }}" type="text" name="qty[{{ $item->id }}]" value="" class="form-control qty" placeholder="Nhập vào Size">
                                </td>
                                <td>
                                    <input value="{{ $item->price }}" style="width: 120px; text-align: right;" type="text" name="price[{{ $item->id }}]" value="" class="form-control price" placeholder="Nhập vào Giá sản phẩm (Tệ)">
                                </td>
                                <td>
                                    <input value="{{ $item->customer_note }}" name="customer_note[{{ $item->id }}]" class="form-control customer_note" rows="5" placeholder="Nhập vào Ghi chú của bạn">
                                </td>
                                <input type="hidden" name="id[{{ $item->id }}]">
                            </tr>
                        @endforeach
                    @else
                            <tr>
                                <td colspan="9">Không có sản phẩm nào</td>
                            </tr>
                    @endif
                 </table>
             </div>
          </div>
       </div>
       <!-- /.box-body -->
       <div class="box-footer">
          <input type="hidden" name="_token" value="X5bkjPnMrgjAFMYrBCwFuToUkQKGnnurqLmRpNH4">
          <div class="col-md-2">
          </div>
          <div class="col-md-8">
             <div class="btn-group pull-left">
                <button type="submit" class="btn btn-primary">Thực hiện</button>
             </div>
             <div class="btn-group pull-right">
                <button type="reset" class="btn btn-warning">Đặt lại</button>
             </div>
          </div>
       </div>
    </form>
 </div>