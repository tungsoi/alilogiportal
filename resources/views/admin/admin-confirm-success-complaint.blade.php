<form action="{{ route('admin.complaints.storeAdminConfirmSuccess') }}" method="POST">
    {{ csrf_field() }}
    <h3>Bạn xác nhận khuyếu nại này đã xử lý xong ?</h3>
    <p>Hệ thống sẽ chuyển trạng thái khuyếu nại này sang "Admin dã xử lý", vui lòng đợi khách hàng xác nhận rằng đã thành công.</p>
    <input type="hidden" name="id" value="{{ $id }}">
    <button type="submit" class="btn btn-success">Xác nhận</button>
</form>