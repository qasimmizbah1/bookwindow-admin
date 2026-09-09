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
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();

            // 1. Branding & Identity
            $table->string('site_name')->nullable();
            $table->string('site_tagline')->nullable();
            $table->string('site_logo')->nullable();
            $table->string('site_logo_dark')->nullable();
            $table->string('site_favicon')->nullable();
            $table->string('footer_copyright')->nullable();

            // 2. Scripts & Tracking
            $table->mediumText('gtm_head_code')->nullable();
            $table->text('gtm_body_code')->nullable();
            $table->string('ga_measurement_id')->nullable();
            $table->mediumText('ga_script_code')->nullable();
            $table->string('meta_pixel_id')->nullable();
            $table->mediumText('meta_pixel_code')->nullable();
            $table->mediumText('custom_head_scripts')->nullable();
            $table->mediumText('custom_footer_scripts')->nullable();

            // 3. Contact & Support
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->text('contact_address')->nullable();
            $table->string('business_hours')->nullable();

            // 4. Social Media Profiles
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_youtube')->nullable();
            $table->string('social_linkedin')->nullable();

            // Audit
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
