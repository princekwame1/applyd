<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the passed-on Paystack charge is recorded.
 *
 * It is kept apart from the amount on purpose. `amount`, `tuition_amount` and
 * a purchase's `price` all mean "what this is worth to the academy" — they
 * drive balances, revenue figures and the books. The transaction charge is
 * money the payer hands to Paystack on our behalf; folding it into those
 * columns would inflate every one of them and make a fully-paid tuition look
 * overpaid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->decimal('amount_fee', 10, 2)->default(0)->after('amount');
            $table->decimal('tuition_fee', 10, 2)->default(0)->after('tuition_amount');
        });

        Schema::table('plan_purchases', function (Blueprint $table) {
            $table->decimal('fee', 10, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['amount_fee', 'tuition_fee']);
        });

        Schema::table('plan_purchases', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};
