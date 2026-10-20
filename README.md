# Say Sách — Quản lý thư viện

Ứng dụng Laravel quản lý danh mục, độc giả, bản sao sách, đặt giữ, bàn giao, trả sách, thẻ thành viên và thanh toán.

Hướng dẫn cập nhật và cài mới: **HUONG_DAN_CAP_NHAT_20_10.txt**. Thông tin các ấn bản: **CATALOG_SOURCES.md**.

## Cập nhật project hiện có

Giữ nguyên `.env` và ảnh tải lên trong `storage/app/public`, chép mã nguồn mới rồi chạy:

```bash
php artisan optimize:clear
php artisan library:sync-catalog
php artisan storage:link
```

Nhấn Ctrl + F5 để nạp CSS mới. Lệnh đồng bộ giữ nguyên các bản sao sách và phiếu mượn. Những sách mới thêm không tự phát sinh tồn kho; nhập bản sao thực tế trong trang quản lý sách.

## Kiểm thử

```bash
php vendor/bin/phpunit
```

Bản này đạt 39 bài kiểm thử, 286 assertions trên PHP 8.3 / SQLite. Chưa xác nhận hiển thị bằng trình duyệt Windows/Laragon.

## Bản sửa V2

Font tiêu đề tiếng Việt được nhúng trong `public/fonts`. Chọn số ngày tại chi tiết sách sẽ cập nhật tiền thuê, tiền cọc và tổng thanh toán từ máy chủ. Trang độc giả có thẻ thành viên riêng; đặt trùng sách hiện thông báo trên trang. Lịch sử và đối soát hiển thị rõ tiền cọc đã hoàn lại, khấu trừ và còn giữ.

Nếu đã dùng bản trước, giữ `.env` và `storage/app/public`, cập nhật mã nguồn rồi chạy `php artisan optimize:clear`; không cần thay đổi cấu trúc database.
