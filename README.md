# SAY SÁCH — WEBSITE QUẢN LÝ THƯ VIỆN

**Say Sách** là website quản lý thư viện được xây dựng trên **Laravel 12**, sử dụng **PHP, Blade, Bootstrap và MySQL**. Hệ thống hỗ trợ quản lý danh mục sách, bản sao sách, tài khoản độc giả, đặt giữ và bàn giao sách, trả sách, thẻ thành viên, xác minh học sinh/sinh viên, thanh toán, tiền cọc và thống kê hoạt động.

Dự án được tổ chức theo mô hình ứng dụng web Laravel, không sử dụng React, Vue hay kiến trúc Single Page Application (SPA).

## 1. Công nghệ sử dụng

- **Backend:** PHP 8.2 trở lên, Laravel 12.
- **Giao diện:** Blade Template Engine, Bootstrap, CSS và JavaScript.
- **Cơ sở dữ liệu:** MySQL (cấu hình mẫu sử dụng MySQL trên Laragon).
- **Xác thực và bảo mật:** Laravel Session Authentication, Middleware, Form Request Validation, phân quyền theo vai trò và trạng thái tài khoản.
- **Quản lý dữ liệu:** Eloquent ORM, migration, seeder, database transaction và khóa bản ghi khi xử lý nghiệp vụ cần bảo đảm nhất quán.
- **Báo cáo:** Xuất dữ liệu thống kê với gói `maatwebsite/excel`.
- **Tiện ích:** Composer, Artisan CLI, Laravel Scheduler và thư viện tạo mã vạch `picqer/php-barcode-generator`.

## 2. Chức năng chính

### 2.1. Khách truy cập

- Xem danh mục sách và trang thông tin chi tiết của sách.
- Tìm kiếm, lọc sách theo các tiêu chí được cung cấp trên giao diện.
- Xem ảnh bìa, mô tả và tình trạng sách.
- Đăng ký tài khoản độc giả và đăng nhập để sử dụng các chức năng cần xác thực.
- Không được đặt giữ sách khi chưa đăng nhập.

### 2.2. Độc giả

- Đăng ký tài khoản với họ tên, email, số điện thoại bắt buộc và địa chỉ theo danh sách phường được hỗ trợ; hệ thống kiểm tra dữ liệu không hợp lệ hoặc trùng lặp.
- Đăng nhập, đăng xuất; tài khoản bị khóa hoặc vô hiệu hóa không được tiếp tục sử dụng các chức năng được bảo vệ.
- Xem chi tiết sách, chọn số ngày thuê hợp lệ và nhận báo giá từ máy chủ: tiền thuê, ưu đãi, tiền cọc và thời hạn dự kiến.
- Đặt giữ bản sao sách còn sẵn; không cho đặt trùng cùng đầu sách nếu còn phiếu chờ nhận, đang mượn hoặc quá hạn.
- Hủy phiếu đặt giữ khi đủ điều kiện; theo dõi lịch sử mượn, trạng thái, ngày hẹn trả và các khoản thanh toán/tiền cọc có liên quan.
- Xem thẻ thành viên của mình, gửi yêu cầu đăng ký hoặc gia hạn và theo dõi kết quả xử lý.
- Gửi yêu cầu xác minh tư cách **học sinh hoặc sinh viên** cùng tệp minh chứng hợp lệ; quyền lợi chỉ được áp dụng sau khi xác minh được chấp thuận và còn hiệu lực.

### 2.3. Quản trị viên

- Xem dashboard thống kê tình hình sách, hoạt động mượn/trả, doanh thu và các chỉ tiêu liên quan; xuất báo cáo thống kê.
- Quản lý sách, ảnh bìa, tác giả, thể loại và bản sao sách; quản lý mã bản sao, vị trí kệ và tình trạng bản sao.
- Quản lý tài khoản độc giả, chỉnh sửa thông tin và khóa/mở khóa tài khoản theo quyền được cấp.
- Quản lý các phiếu đặt giữ, kiểm tra mã bản sao và xác nhận bàn giao sách tại quầy.
- Tiếp nhận trả sách; kiểm tra tình trạng sách, tính phí trễ hạn, phí hư hỏng/mất sách, khấu trừ và hoàn tiền cọc theo nghiệp vụ.
- Quản lý thẻ thành viên, xử lý yêu cầu đăng ký/gia hạn và ghi nhận thanh toán.
- Xem, đối soát và xử lý các khoản thanh toán cũng như giao dịch điều chỉnh tiền cọc.
- Xem xét và duyệt/từ chối hồ sơ xác minh học sinh/sinh viên; quản lý minh chứng theo quyền truy cập.

### 2.4. Chức năng tự động và quy tắc nghiệp vụ

- Scheduler cập nhật phiếu `borrowing` quá hạn thành `overdue` mỗi ngày.
- Scheduler kiểm tra và hủy các phiếu giữ chỗ `pending` đã hết thời hạn theo chính sách (mặc định **24 giờ**).
- Tính giá thuê dựa trên số ngày thuê, thẻ thành viên hợp lệ, xác minh học sinh/sinh viên và chính sách thuê tại thời điểm xử lý.
- Tiền cọc tiêu chuẩn hiện được tính theo **100% giá bìa**; thẻ thành viên miễn tiền thuê trong thời gian thẻ hợp lệ nhưng **không miễn tiền cọc**.
- Đối soát tiền cọc theo các giao dịch đã thu, đã điều chỉnh, đã khấu trừ và đã hoàn; tránh tính hoàn vượt số dư thực tế.
- Sử dụng transaction và các cơ chế kiểm tra trạng thái để hạn chế đặt trùng, bàn giao trùng hoặc xử lý trả sách lặp.

## 3. Cấu hình nghiệp vụ mặc định

Seeder khởi tạo một chính sách thuê tại bảng `rental_policies` với các giá trị dưới đây. Các mức này là **cấu hình mẫu của bản mã nguồn**, không phải mức cố định cho mọi lần vận hành.

| Thông số | Giá trị mặc định |
|---|---:|
| Tiền thuê thông thường | 3.000 VNĐ/ngày |
| Giảm giá cho học sinh/sinh viên đã xác minh | 20% tiền thuê khi không áp dụng thẻ thành viên |
| Tiền cọc tiêu chuẩn | 100% giá bìa sách |
| Phí quá hạn | 5.000 VNĐ/ngày (theo chính sách và giới hạn xử lý) |
| Tỷ lệ bồi thường hư hỏng mặc định | 30% giá bìa |
| Bồi thường mất sách | 100% giá bìa + 20.000 VNĐ phí xử lý |
| Phí thẻ thành viên | 80.000 VNĐ/tháng |
| Số ngày thuê tối đa | 30 ngày |
| Số sách tối đa theo chính sách | 3 |
| Thời gian giữ chỗ mặc định | 24 giờ |

**Lưu ý:** Số tiền thực tế được tính bằng `PricingService` và chính sách gắn với giao dịch. Không sử dụng cấu hình `LIBRARY_FINE_PER_DAY` hoặc `config/library.php` của phiên bản cũ.

## 4. Hướng dẫn cài đặt bằng Laragon

### Bước 1. Chuẩn bị môi trường

Cài đặt Laragon (có PHP phù hợp), Composer, MySQL và Node.js/npm (Node.js có thể có sẵn trong Laragon). Khởi động dịch vụ cần thiết trong Laragon. Đặt mã nguồn vào thư mục, ví dụ:

```text
C:\laragon\www\QUANLYTHUVIEN
```

Nếu đã clone GitHub với tên `QUANLYTHUVIEN_GITHUB`, có thể sử dụng tên thư mục đó thay thế. Tất cả lệnh phía dưới phải được chạy **tại thư mục gốc của dự án**, nơi có file `artisan`.

### Bước 2. Mở Terminal và cài dependency

Trong PowerShell:

```powershell
cd C:\laragon\www\QUANLYTHUVIEN_GITHUB
composer install
```

### Bước 2a. Cài đặt và build tài nguyên giao diện

Kiểm tra Node.js và npm trong PowerShell:

```powershell
node -v
npm.cmd -v
```

Nếu không nhận lệnh nhưng Laragon đã cài Node.js, thêm **đúng đường dẫn thư mục chứa `node.exe` trên máy** vào `PATH` của Terminal hiện tại. Ví dụ, chỉ dùng khi máy có thư mục tương ứng:

```powershell
$nodeDir = "C:\laragon\bin\nodejs\node-v22"
$env:Path = "$nodeDir;$env:Path"
```

Tại thư mục dự án, chạy:

```powershell
npm.cmd ci
npm.cmd run build
Test-Path .\public\build\manifest.json
```

Nếu `npm.cmd ci` không tương thích với file lock hiện có, kiểm tra lỗi rồi mới cân nhắc `npm.cmd install`. Kết quả `Test-Path` mong đợi là `True`. **Không chạy `npm audit fix --force` chỉ để khắc phục lỗi giao diện.** Các tệp Bootstrap bắt buộc `public/vendor/bootstrap/bootstrap.min.css` và `bootstrap.bundle.min.js` đã được lưu trong mã nguồn GitHub.

### Bước 3. Tạo cơ sở dữ liệu

Dùng HeidiSQL hoặc phpMyAdmin tạo một database **trống**, ví dụ `qlthuvien_saysach`. Không chạy lệnh xóa dữ liệu trên database đang có thông tin cần giữ.

### Bước 4. Tạo file môi trường

```powershell
Copy-Item .env.example .env
```

Mở `.env` và chỉnh lại thông tin kết nối theo máy:

```dotenv
APP_NAME="Quản lý thư viện"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qlthuvien_saysach
DB_USERNAME=root
DB_PASSWORD=
```

Nếu MySQL đã đặt mật khẩu, cần nhập giá trị thật vào `DB_PASSWORD`. **Không đăng file `.env` lên GitHub.**

### Bước 5. Tạo khóa ứng dụng

```powershell
php artisan key:generate
php artisan optimize:clear
```

Lệnh `key:generate` tạo khóa riêng cho bản cài đặt. Không chia sẻ khóa này công khai.

### Bước 6. Khởi tạo bảng và dữ liệu ban đầu

**Chỉ áp dụng với database trống dành cho bản cài đặt mới:**

```powershell
php artisan migrate --seed
```

Lệnh tạo các bảng mới và nạp tài khoản mẫu, danh mục sách và chính sách thuê. Danh mục được đồng bộ từ `config/saysach_catalog.php`.

**Không chạy `php artisan migrate:fresh --seed` trên database đang sử dụng:** lệnh này xóa các bảng hiện có và dữ liệu bên trong.

### Bước 6a. Nạp dữ liệu minh họa để trình bày (tùy chọn)

`php artisan migrate --seed` chỉ tạo dữ liệu nền (ví dụ 2 tài khoản, 17 đầu sách, chính sách thuê); **không tự sinh bản sao và giao dịch mượn/trả**. Để có sẵn thông tin trong giao diện demo, mã nguồn cung cấp `database/seeders/SaySachDemoSeeder.php`.

**Chỉ thực hiện trên database riêng dành cho demo**, không chạy trên database thật, database đang sử dụng cho nghiệp vụ hoặc database kiểm thử. Seeder demo chỉ cho phép các tên database đã quy định trong mã nguồn, gồm `qlthuvien_saysach` và `qlthuvien_saysach_demo`; kiểm tra tên đang kết nối:

```powershell
php artisan config:show database.connections.mysql
```

Trước khi nạp, xuất bản sao lưu SQL bằng HeidiSQL/phpMyAdmin nếu database đã có dữ liệu cần giữ. Trong **PowerShell**, sau khi chắc chắn kết nối đúng database demo:

```powershell
$env:SAYSACH_DEMO_SEED="YES"
php artisan db:seed --class=SaySachDemoSeeder
Remove-Item Env:SAYSACH_DEMO_SEED
```

Nếu lệnh seed báo lỗi, vẫn chạy `Remove-Item Env:SAYSACH_DEMO_SEED -ErrorAction SilentlyContinue` để xóa biến môi trường khỏi Terminal. Sau khi chạy thành công, kiểm tra dữ liệu:

```powershell
php artisan tinker
```

Trong Tinker:

```php
DB::connection()->getDatabaseName();
App\Models\User::count();
App\Models\Book::count();
App\Models\BookCopy::count();
App\Models\BorrowRecord::count();
App\Models\Payment::count();
exit
```

Ở lần demo đã thực hiện trên máy nhóm: **7 tài khoản, 17 đầu sách, 39 bản sao, 11 phiếu mượn/trả, 1 thẻ thành viên, 8 thanh toán**. Đây chỉ là số liệu minh họa được ghi nhận sau khi seed, không phải cam kết cho mọi database và **không tự đồng bộ lên GitHub**. Seeder không nên dùng để tạo tồn kho thật.

### Bước 7. Liên kết thư mục lưu trữ

```powershell
php artisan storage:link
```

Thư mục lưu trữ công khai phục vụ những tệp được ứng dụng cho phép truy cập. Hồ sơ minh chứng riêng tư được xử lý qua chức năng phân quyền, không nên đưa công khai trực tiếp.

### Bước 8. Chạy ứng dụng

```powershell
php artisan serve --host=localhost --port=8000
```

Mở trình duyệt tại:

**http://localhost:8000**

Giữ Terminal chạy server mở trong thời gian sử dụng.

### Bước 9. Chạy tác vụ tự động (nếu cần)

Mở Terminal thứ hai tại thư mục dự án:

```powershell
php artisan schedule:work
```

Scheduler tự cập nhật phiếu quá hạn hằng ngày và kiểm tra giữ chỗ hết hạn theo chu kỳ giờ.

## 5. Tài khoản mặc định sau khi seed

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Quản trị viên | `admin@example.com` | `Password@123` |
| Độc giả | `user@example.com` | `Password@123` |

Các tài khoản được khởi tạo bởi `database/seeders/DatabaseSeeder.php` phục vụ cài đặt/kiểm thử. **Hãy đổi mật khẩu mặc định nếu sử dụng hệ thống ngoài môi trường thực hành.**

Seeder đồng bộ **17 đầu sách** theo danh mục Say Sách và thiết lập chính sách thuê. **Seeder nền không tự sinh bản sao hay tồn kho thực tế**; quản trị viên cần tạo bản sao theo số sách thực có. Chỉ dùng `SaySachDemoSeeder` khi muốn tạo dữ liệu *minh họa* trong database demo riêng.

## 6. Cập nhật từ phiên bản trước

Trước khi cập nhật, **sao lưu mã nguồn và database**. Giữ riêng `.env` và các tệp người dùng đã tải lên trong `storage/app/public`. Sau khi cập nhật mã nguồn tương thích, chạy (chỉ khi chắc chắn cấu trúc database phù hợp):

```powershell
php artisan optimize:clear
php artisan migrate --force
php artisan library:sync-catalog
php artisan storage:link
```

- `migrate --force` áp dụng migration còn thiếu; **cần rà soát trạng thái migration trước khi dùng với database của một phiên bản có cấu trúc khác**. Không xem đây là phương án chuyển đổi dữ liệu tự động được bảo đảm cho mọi bản cũ.
- `library:sync-catalog` cập nhật thông tin của 17 đầu sách được định nghĩa sẵn, giữ nguyên các bản sao và phiếu mượn; nếu một mã sách đã được sử dụng cho đầu sách khác, lệnh bỏ qua và cảnh báo.
- Nếu storage link đã tồn tại, không cần tạo lại.
- Nếu thay đổi tài nguyên frontend, chạy `npm.cmd ci` và `npm.cmd run build` để cập nhật CSS/JavaScript biên dịch.
- Sau khi khởi động lại server, nhấn **Ctrl + F5** để tải lại CSS/JS.

Không thay database hiện tại bằng database demo và không chạy `migrate:fresh` chỉ để cập nhật giao diện.

## 7. Kiểm thử

Từ thư mục dự án, chạy:

```powershell
php artisan optimize:clear
php artisan test
```

Trong lần kiểm thử gần nhất do nhóm thực hiện trên Laragon, **68 tests đã PASS với 647 assertions**. Kết quả này xác nhận các tình huống đã có trong bộ kiểm thử tại thời điểm chạy; vẫn cần kiểm tra thao tác và hiển thị giao diện thực tế trên trình duyệt.

Để xem các route đang đăng ký:

```powershell
php artisan route:list
```

Để kiểm tra style PHP bằng Laravel Pint (nếu đã cài dependency phát triển):

```powershell
./vendor/bin/pint --test
```

## 8. Một số lệnh Artisan hữu ích

| Lệnh | Mục đích |
|---|---|
| `php artisan serve` | Khởi động web server phát triển |
| `php artisan route:list` | Liệt kê route |
| `php artisan optimize:clear` | Xóa cache cấu hình, route, view và cache liên quan |
| `php artisan migrate:status` | Kiểm tra trạng thái migration |
| `php artisan db:seed` | Nạp dữ liệu nền (chỉ khi phù hợp database) |
| `php artisan db:seed --class=SaySachDemoSeeder` | Nạp dữ liệu minh họa **chỉ trong database demo** và sau khi bật biến xác nhận |
| `npm.cmd ci` / `npm.cmd run build` | Cài dependency frontend theo lockfile và build tài nguyên giao diện |
| `php artisan library:sync-catalog` | Đồng bộ thông tin danh mục sách |
| `php artisan library:mark-overdue` | Đánh dấu phiếu mượn quá hạn |
| `php artisan library:expire-reservations` | Hủy các phiếu giữ chỗ đã hết hạn |
| `php artisan schedule:work` | Chạy Scheduler trong môi trường phát triển |
| `php artisan test` | Chạy bộ kiểm thử tự động |

## 9. Các đường dẫn chính

| Đường dẫn | Chức năng | Quyền truy cập |
|---|---|---|
| `/` | Điều hướng theo trạng thái đăng nhập/vai trò | Mọi người |
| `/books` | Danh mục sách | Mọi người |
| `/books/{book}` | Chi tiết sách | Mọi người |
| `/login`, `/register` | Đăng nhập và đăng ký | Khách |
| `/history` | Lịch sử mượn | Tài khoản đăng nhập |
| `/my-membership` | Thẻ thành viên và yêu cầu đăng ký/gia hạn | Độc giả |
| `/student-verification` | Gửi và xem yêu cầu xác minh | Độc giả |
| `/admin/statistics` | Dashboard và thống kê | Quản trị viên |
| `/admin/books` | Quản lý đầu sách | Quản trị viên |
| `/admin/books/{book}/copies` | Quản lý bản sao sách | Quản trị viên |
| `/admin/authors`, `/admin/categories` | Quản lý tác giả và thể loại | Quản trị viên |
| `/admin/users` | Quản lý người dùng | Quản trị viên |
| `/admin/borrow` | Phiếu đặt giữ và bàn giao | Quản trị viên |
| `/admin/returns` | Tiếp nhận trả sách | Quản trị viên |
| `/admin/cards` | Quản lý thẻ thành viên | Quản trị viên |
| `/admin/verifications` | Duyệt hồ sơ xác minh | Quản trị viên |
| `/admin/payments` | Theo dõi thanh toán/tiền cọc | Quản trị viên |

## 10. Cấu trúc mã nguồn

```text
app/
  Http/Controllers/     Xử lý các chức năng ứng dụng
  Http/Middleware/      Kiểm tra đăng nhập, trạng thái và vai trò
  Http/Requests/        Quy tắc kiểm tra dữ liệu đầu vào
  Models/               Các model Eloquent
  Services/             Nghiệp vụ mượn sách, tính phí, thẻ thành viên
  Exports/              Xuất báo cáo thống kê
config/
  saysach_catalog.php   Dữ liệu danh mục sách
  saysach_labels.php    Nhãn hiển thị trạng thái
  hcm_wards.php         Danh sách phường dùng trong form
routes/                 Khai báo route theo nhóm chức năng
resources/views/        Giao diện Blade
database/migrations/     Cấu trúc database
database/seeders/        Dữ liệu nền và Seeder demo riêng
public/vendor/bootstrap/ Bootstrap CSS/JS dùng trực tiếp bởi layout
public/build/           CSS/JS biên dịch từ Vite (tạo trên máy)
public/css/             CSS giao diện
public/js/              JavaScript giao diện
public/images/catalog/  Ảnh bìa theo danh mục
public/fonts/           Font dùng trong giao diện
tests/                  Unit test và Feature test
```

## 11. Lưu ý khi bàn giao và làm việc nhóm

- Không đưa `.env`, mật khẩu riêng, dữ liệu người dùng thực hoặc thư mục `vendor/` **ở gốc dự án (Composer)** lên GitHub. Riêng `public/vendor/bootstrap/` chứa hai file tài nguyên giao diện tĩnh cần thiết và đã được đưa vào mã nguồn.
- Mỗi thành viên nên sử dụng branch riêng và tạo Pull Request trước khi hợp nhất vào `main`.
- Sau khi lấy mã nguồn mới, chạy `composer install`, `npm.cmd ci`, `npm.cmd run build`, cấu hình `.env`, kiểm tra migration rồi chạy `php artisan test`.
- Chỉ nhập số bản sao sách có thật. Không tự coi 17 đầu sách trong danh mục là 17 bản sao còn sẵn.
- Khi nâng cấp từ database cũ, luôn sao lưu và kiểm tra migration trước; không dùng `migrate:fresh` nếu muốn giữ dữ liệu.

---

**Say Sách — Website Quản lý thư viện**  
Dự án học tập phát triển trên nền tảng Laravel.
