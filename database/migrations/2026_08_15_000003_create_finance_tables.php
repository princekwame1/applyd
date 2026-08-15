<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money in and money out, with the paperwork attached.
 *
 * Income and expense share one table rather than living in two: every report
 * worth having (net position, a month's activity, one category's history)
 * wants both sides together, and splitting them would mean writing every query
 * twice. `type` is what separates them.
 *
 * Amounts are DECIMAL, never float — a running total of binary fractions drifts,
 * and this is the one place in the app where that would show up as real money
 * being wrong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 10);             // income | expense
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // "Fees" can exist on both sides of the books without clashing.
            $table->unique(['name', 'type']);
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 10);             // income | expense
            // A category can be retired without taking its history with it, so
            // the link goes null rather than blocking or cascading.
            $table->foreignId('finance_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('occurred_on');
            $table->string('party')->nullable();    // who it came from / went to
            $table->string('method', 40)->nullable();
            $table->string('document_no', 60)->nullable();  // their invoice/receipt number
            $table->text('note')->nullable();
            // Who keyed it in. Kept for the audit trail, so deleting a staff
            // account must not delete or block their entries.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'occurred_on']);
            $table->index('occurred_on');
        });

        Schema::create('finance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);             // invoice | receipt | other
            // Private disk. A receipt is only ever served through the
            // authorised dashboard download route.
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['finance_transaction_id', 'kind']);
        });

        $this->seedCategories();
    }

    /**
     * A starting set so the first entry doesn't have to begin with admin. All
     * ordinary rows — rename, retire or delete any of them from the dashboard.
     */
    private function seedCategories(): void
    {
        $now = now();
        $order = 0;
        $rows = [];

        $seed = [
            'income' => ['Bootcamp registrations', 'Course tuition', 'Recruiter plans', 'Sponsorship', 'Other income'],
            'expense' => ['Venue & logistics', 'Facilitator fees', 'Equipment', 'Marketing', 'Software & subscriptions', 'Refreshments', 'Transport', 'Other expenses'],
        ];

        foreach ($seed as $type => $names) {
            foreach ($names as $name) {
                $rows[] = [
                    'name' => $name,
                    'type' => $type,
                    'is_active' => true,
                    'sort_order' => ++$order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('finance_categories')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_documents');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_categories');
    }
};
