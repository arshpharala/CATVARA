<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('name');          // Inventory, Orders, POS
            $table->string('slug')->unique(); // inventory, orders
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('name');           // View Orders
            $table->string('slug')->unique(); // orders.view
            $table->unsignedBigInteger('module_id');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('module_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'modules');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('name'); // Admin, Manager, Staff
            $table->string('slug'); // admin, manager
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);

            $table->foreign('company_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'companies')
                ->cascadeOnDelete();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);

            $table->foreign('role_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'roles')
                ->cascadeOnDelete();

            $table->foreign('permission_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'permissions')
                ->cascadeOnDelete();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');

            $table->boolean('is_owner')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'user_id']);

            $table->foreign('company_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'companies')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'users')
                ->cascadeOnDelete();
        });

        Schema::create('company_user_role', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');

            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'role_id']);

            $table->foreign('company_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'companies')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'users')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'roles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('company_user_role');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('modules');

        Schema::enableForeignKeyConstraints();
    }
};
