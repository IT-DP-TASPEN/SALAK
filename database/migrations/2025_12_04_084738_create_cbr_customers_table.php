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
        Schema::create('cbr_customers', function (Blueprint $table) {
            $table->id();
            $table->string('cif_no');
            $table->string('cif_alternate_no')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('branch');
            $table->string('related_party')->nullable();
            $table->string('owner_group')->nullable();
            $table->string('city')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('urban_village')->nullable();
            $table->string('sub_district')->nullable();
            $table->string('citydati2')->nullable();
            $table->string('status')->nullable();
            $table->string('status_descriptions')->nullable();
            $table->string('identity_number')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('mother_maiden_name')->nullable();
            $table->tinyInteger('gender')->nullable()->comment('1=Male, 2=Female');
            $table->string('member_type')->nullable();
            $table->string('working_place')->nullable();
            $table->string('working_id')->nullable();
            $table->string('position_id')->nullable();
            $table->string('management_name')->nullable();
            $table->tinyInteger('management_gender')->nullable();
            $table->string('management_address1')->nullable();
            $table->string('management_address2')->nullable();
            $table->string('management_city')->nullable();
            $table->string('management_urban_village')->nullable();
            $table->string('management_sub_district')->nullable();
            $table->string('management_identity')->nullable();
            $table->string('shareholding')->nullable();
            $table->string('debtor_group')->nullable();
            $table->string('country')->nullable();
            $table->string('sub_industry')->nullable();
            $table->string('relation_with_bank')->nullable();
            $table->string('office_address1')->nullable();
            $table->string('office_address2')->nullable();
            $table->string('management_citydati2')->nullable();
            $table->string('occupation_type')->nullable();
            $table->string('din')->nullable();
            $table->string('alias_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('area_code')->nullable();
            $table->string('monthly_income')->nullable();
            $table->tinyInteger('income_source')->nullable();
            $table->integer('number_of_child')->nullable();
            $table->tinyInteger('marital_status')->nullable();
            $table->string('spouse_identity')->nullable();
            $table->string('spouse_name')->nullable();
            $table->date('spouse_birth_date')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('mobile_no')->nullable();
            $table->date('createdate')->nullable();
            $table->date('updatedate')->nullable();
            $table->string('last_education')->nullable();
            $table->string('email')->nullable();
            $table->string('corp_last_deed_place')->nullable();
            $table->string('corp_initial_deed_number')->nullable();
            $table->date('corp_initial_deed_date')->nullable();
            $table->string('corp_last_deed_number')->nullable();
            $table->date('corp_last_deed_date')->nullable();
            $table->string('corp_business_type')->nullable();
            $table->string('violating_maximum_credit_limit')->nullable();
            $table->string('exceed_maximum_credit_limit')->nullable();
            $table->string('debtor_rating')->nullable();
            $table->string('rating_agencies')->nullable();
            $table->date('corp_notary_deed_issued_date')->nullable();
            $table->string('idtype')->nullable();
            $table->string('statement_from_related_party')->nullable();
            $table->string('debtor_type')->nullable();
            $table->string('industry')->nullable();
            $table->string('customer_data')->nullable();

            $table
                ->foreign('branch')
                ->references('branch_code_fincloud')
                ->on('branch_offices');

            $table->index('cif_no');
            $table->index('cif_alternate_no');
            $table->index('identity_number');
            $table->index('customer_name');
            $table->index('owner_group');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbr_customers');
    }
};
