<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('facebook_access_token')->nullable()->default(NULL);
            $table->bigInteger('facebook_id')->nullable()->default(NULL);
            $table->ipAddress('ip_address')->nullable()->default(NULL);
            $table->datetime('confirmed')->nullable()->default(NULL);
            $table->string('confirmation_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('facebook_access_token');
            $table->dropColumn('facebook_id');
            $table->dropColumn('ip_address');
            $table->dropColumn('confirmed');
            $table->dropColumn('confirmation_code');
        });
    }
}