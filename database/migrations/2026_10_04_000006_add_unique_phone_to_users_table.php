<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mỗi số điện thoại chỉ thuộc về một tài khoản (phone NULL thì không bị tính trùng).
     * Trước khi tạo chỉ mục, đưa các SĐT dạng +84... về 0... để thống nhất với validation.
     * Nếu CSDL cũ đã có hai tài khoản trùng SĐT thì cần sửa tay trước khi chạy migrate.
     */
    public function up(): void
    {
        DB::table('users')->where('phone', 'like', '+84%')->get(['id', 'phone'])->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update(['phone' => '0'.substr($row->phone, 3)]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
