<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::statement('TRUNCATE users;');

        /**
         * Users
         */


        $user = new \App\Models\User;
        $user->name = "Admin";
        $user->email = "admin@cartvy.com";
        $user->password = bcrypt('admin@123');
        $user->mobile = "919999999999";
        $user->type = 1;
        $user->status = 1;
        $user->is_admin = 1;
        $user->save();

        $user = new \App\Models\User;
        $user->name = "User";
        $user->email = "user@towingapp.com";
        $user->password = bcrypt('hello@123');
        $user->mobile = "919999999998";
        $user->type = 2;
        $user->status = 1;
        $user->is_admin = 0;
        $user->save();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
