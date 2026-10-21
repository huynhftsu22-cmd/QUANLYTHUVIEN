<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{User,Author,Category,Book,BookCopy,RentalPolicy};
class DatabaseSeeder extends Seeder {
 public function run(): void {
  User::firstOrCreate(['email'=>'admin@example.com'],['user_code'=>'NV0001','name'=>'Quản trị thư viện','phone'=>'0900000001','address'=>null,'password'=>'Password@123','role'=>'admin','status'=>'active']);
  User::firstOrCreate(['email'=>'user@example.com'],['user_code'=>'DG0001','name'=>'Độc giả','phone'=>'0900000002','address'=>null,'password'=>'Password@123','role'=>'user','status'=>'active']);
  $this->call(SaySachCatalogSeeder::class);
  RentalPolicy::firstOrCreate(['effective_at'=>'2026-10-18'],['daily_rate'=>3000,'student_discount'=>20,'deposit_rate'=>100,'member_deposit_rate'=>100,'late_fee'=>5000,'damage_rate'=>30,'lost_rate'=>100,'lost_admin_fee'=>20000,'card_fee_monthly'=>80000,'max_days'=>30,'max_books'=>3,'hold_hours'=>24,'is_active'=>true]);
 }
}
