<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected function hasForeignKey(string $table, string $fkName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $db = $connection->getDatabaseName();
            $result = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = ?",
                [$db, $table, $fkName]
            );
            return (bool) $result;
        }

        // For sqlite or other drivers, don't attempt complex checks; assume absent
        return false;
    }

    public function up()
    {
        $userTable = config('auth.providers.users.table', 'users');

        // Only add FK if the users table exists and the FK is not present
        if (Schema::hasTable($userTable) && Schema::hasTable('user_discounts')) {
            $fkName = 'user_discounts_user_id_foreign';

            if (! $this->hasForeignKey('user_discounts', $fkName)) {
                Schema::table('user_discounts', function (Blueprint $table) use ($userTable) {
                    $table->foreign('user_id')->references('id')->on($userTable)->onDelete('cascade');
                });
            }

            // same for discount_audits
            $fkName2 = 'discount_audits_user_id_foreign';
            if (! $this->hasForeignKey('discount_audits', $fkName2)) {
                Schema::table('discount_audits', function (Blueprint $table) use ($userTable) {
                    $table->foreign('user_id')->references('id')->on($userTable)->onDelete('cascade');
                });
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('user_discounts')) {
            Schema::table('user_discounts', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('discount_audits')) {
            Schema::table('discount_audits', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
    }
};
