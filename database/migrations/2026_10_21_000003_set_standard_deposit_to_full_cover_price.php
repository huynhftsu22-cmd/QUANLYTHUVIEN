<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::transaction(function () {
            $current = DB::table('rental_policies')->where('is_active',true)
                ->orderByDesc('effective_at')->orderByDesc('id')->lockForUpdate()->first();
            if (!$current || ((int)$current->deposit_rate === 100 && (int)$current->member_deposit_rate === 100)) return;
            // Keep historical policy values for loans already handed over.
            $new = (array)$current;
            unset($new['id']);
            $new['deposit_rate'] = 100;
            $new['member_deposit_rate'] = 100;
            $new['effective_at'] = today()->toDateString();
            $new['is_active'] = true;
            $new['created_at'] = now();
            $new['updated_at'] = now();
            DB::table('rental_policies')->where('is_active',true)->update(['is_active'=>false,'updated_at'=>now()]);
            DB::table('rental_policies')->insert($new);
        });
    }
    public function down(): void {
        // Do not erase or rewrite financial policy history during a rollback.
    }
};
