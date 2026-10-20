<?php
return [
 'copy_status' => ['available'=>'Có sẵn','reserved'=>'Đang giữ chỗ','borrowed'=>'Đang được mượn','damaged'=>'Hư hỏng','maintenance'=>'Đang bảo dưỡng','lost'=>'Đã mất','retired'=>'Ngừng lưu hành'],
 'user_status' => ['active'=>'Hoạt động','locked'=>'Bị khóa','deleted'=>'Đã vô hiệu hóa'],
 'role' => ['admin'=>'Quản trị viên','user'=>'Độc giả'],
 'payment_type' => ['rental_fee'=>'Phí thuê sách','card_fee'=>'Phí thẻ thành viên','late_fine'=>'Phạt trả trễ','damage_fee'=>'Bồi thường hư hỏng/mất sách'],
 'payment_method' => ['cash'=>'Tiền mặt','transfer'=>'Chuyển khoản','deposit'=>'Khấu trừ từ tiền cọc'],
 'payment_status' => ['paid'=>'Đã thu','cancelled'=>'Đã hủy'],
 'deposit_type' => ['hold'=>'Thu và giữ tiền cọc','refund'=>'Hoàn lại tiền cọc','deduct'=>'Khấu trừ tiền cọc','adjust_up'=>'Điều chỉnh tăng số dư cọc','adjust_down'=>'Điều chỉnh giảm số dư cọc'],
 'borrow_status' => ['pending'=>'Chờ nhận sách','borrowing'=>'Đang mượn','overdue'=>'Quá hạn','returned'=>'Đã trả','cancelled'=>'Đã hủy'],
 'card_status' => ['active'=>'Đang hiệu lực','suspended'=>'Tạm khóa','cancelled'=>'Đã hủy','expired'=>'Hết hạn'],
 'condition' => ['good'=>'Nguyên vẹn','damaged'=>'Hư hỏng','lost'=>'Mất sách'],
];
