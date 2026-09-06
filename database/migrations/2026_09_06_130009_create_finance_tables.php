<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lenders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->enum('lender_type', ['bank', 'nbfc', 'coop'])->default('bank');
            $table->decimal('interest_min', 5, 2)->nullable();
            $table->decimal('interest_max', 5, 2)->nullable();
            $table->unsignedSmallInteger('tenure_min_months')->default(12);
            $table->unsignedSmallInteger('tenure_max_months')->default(84);
            $table->decimal('amount_min', 12, 2)->nullable();
            $table->decimal('amount_max', 12, 2)->nullable();
            $table->decimal('processing_fee_percent', 5, 2)->nullable();
            $table->decimal('max_ltv_percent', 5, 2)->default(80);
            $table->json('states_served')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();      // KJ-LN-00789
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('purpose', ['new_purchase', 'used_purchase', 'refinance'])->default('new_purchase');
            $table->nullableMorphs('financeable');             // Product | UsedListing
            $table->string('applicant_name');
            $table->string('mobile', 15);
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            // KYC identifiers are stored masked; full values are never persisted in v1
            $table->string('pan_masked', 12)->nullable();
            $table->string('aadhaar_masked', 14)->nullable();
            $table->decimal('annual_income', 12, 2)->nullable();
            $table->enum('income_source', ['farming', 'business', 'salary', 'other'])->default('farming');
            $table->decimal('land_holding_acres', 8, 2)->nullable();
            $table->decimal('machinery_price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('loan_amount', 12, 2);
            $table->unsignedSmallInteger('tenure_months')->default(60);
            $table->decimal('expected_interest_rate', 5, 2)->nullable();
            $table->decimal('calculated_emi', 12, 2)->nullable();
            $table->unsignedSmallInteger('cibil_score')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address')->nullable();
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'docs_pending', 'sent_to_lender',
                'sanctioned', 'rejected', 'disbursed', 'cancelled',
            ])->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'submitted_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('mobile');
        });

        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->enum('doc_type', [
                'aadhaar', 'pan', 'land_record', 'bank_statement', 'income_proof',
                'photo', 'quotation', 'rc', 'other',
            ]);
            $table->string('file_path');        // private disk, signed URLs only
            $table->string('original_name')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['loan_application_id', 'status']);
        });

        Schema::create('loan_application_lenders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lender_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['sent', 'acknowledged', 'sanctioned', 'rejected'])->default('sent');
            $table->decimal('sanctioned_amount', 12, 2)->nullable();
            $table->decimal('offered_rate', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['loan_application_id', 'lender_id'], 'loan_lender_unique');
        });

        Schema::create('loan_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('loan_application_id');
        });

        Schema::create('insurance_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->json('coverage_types')->nullable();
            $table->text('description')->nullable();
            $table->json('states_served')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('insurance_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('applicant_name');
            $table->string('mobile', 15);
            $table->string('registration_number', 20)->nullable();
            $table->year('manufacturing_year')->nullable();
            $table->enum('coverage_type', ['comprehensive', 'third_party', 'own_damage'])->default('comprehensive');
            $table->date('previous_policy_expiry')->nullable();
            $table->boolean('has_claim_history')->default(false);
            $table->decimal('idv_expected', 12, 2)->nullable();
            $table->enum('status', ['new', 'contacted', 'quoted', 'converted', 'lost'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('emi_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('interest_rate', 5, 2);
            $table->unsignedSmallInteger('tenure_months');
            $table->enum('frequency', ['monthly', 'quarterly', 'half_yearly', 'yearly'])->default('monthly');
            $table->decimal('emi', 12, 2);
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_payable', 12, 2);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'emi_calculations', 'insurance_enquiries', 'insurance_partners', 'loan_status_logs',
            'loan_application_lenders', 'loan_documents', 'loan_applications', 'lenders',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
