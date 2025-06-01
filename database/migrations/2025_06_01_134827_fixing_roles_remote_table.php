<?php

use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $get_roles = Role::whereNot('slug', 'client')->orderBy('id', 'asc')->get()->toArray();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        UserRole::truncate();

        for ($i = 0; $i < count($get_roles); $i++) {
            Role::create([
                'name' => $get_roles[$i]['name'],
                'slug' => $get_roles[$i]['slug'],
                'description' => $get_roles[$i]['description'],
            ]);
        }

        $find_admin = User::where('email', 'admin@example.com')->first();

        if (!$find_admin) {
            $find_admin = User::first();
        }

        if (!$find_admin)
            return;

        $super_admin_role = Role::where('slug', 'super-admin')->first();

        if (!$super_admin_role)
            return;

        UserRole::create([
            'user_id' => $find_admin->id,
            "role_id" => $super_admin_role->id,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
