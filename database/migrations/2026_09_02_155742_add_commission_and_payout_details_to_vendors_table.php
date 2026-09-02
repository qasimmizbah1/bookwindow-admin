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
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('vendor_name');
            $table->string('pan_number')->nullable()->after('isbn_number');
            $table->string('gst_number')->nullable()->after('pan_number');
            $table->string('city')->nullable()->after('vendor_address');
            $table->string('state')->nullable()->after('city');
            $table->string('pincode')->nullable()->after('state');
            $table->string('bank_name')->nullable()->after('pincode');
            $table->string('account_holder_name')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_holder_name');
            $table->string('ifsc_code')->nullable()->after('account_number');
            $table->string('upi_id')->nullable()->after('ifsc_code');
            $table->decimal('commission_percentage', 5, 2)->default(7.00)->after('vendor_website');
            $table->string('approval_status')->default('approved')->after('commission_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'contact_person',
                'pan_number',
                'gst_number',
                'city',
                'state',
                'pincode',
                'bank_name',
                'account_holder_name',
                'account_number',
                'ifsc_code',
                'upi_id',
                'commission_percentage',
                'approval_status',
            ]);
        });
    }
};
