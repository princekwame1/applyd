<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Digital products — things the academy sells as a download rather than a seat
 * in a room: templates, guides, workbooks, spreadsheets.
 *
 * A product is delivered one of two ways and never both: a file we hold on the
 * **private** disk, or a link to somewhere the buyer already has to be sent
 * (a Drive folder, a Notion page). Whichever it is, it is only ever reached
 * through the order that paid for it — there is no public URL to a product
 * file, because a URL that works without an order is the whole business model
 * handed to anyone who shares it.
 *
 * Orders are the money record, so they outlive edits to the catalogue: the
 * title and the price are snapshotted, and a product with a settled order
 * cannot be deleted (the buyer's download still has to resolve).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 120)->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable();
            $table->string('format', 40)->nullable();      // "PDF", "Excel template", …
            $table->decimal('price', 10, 2)->default(0);   // 0 is a real price: a free download
            $table->string('cover')->nullable();           // public disk — it is meant to be seen

            // The file, on the private disk. Null when the product is delivered
            // as a link instead; exactly one of the two is always set.
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('external_url')->nullable();

            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            // Declared inside the CREATE so it holds on SQLite too, and
            // restricting: a paid order's download has to keep resolving, so
            // the product behind it may not simply vanish.
            $table->foreignId('digital_product_id')->constrained()->restrictOnDelete();
            // What was bought, settled at purchase time — the catalogue can be
            // renamed and repriced afterwards without rewriting history.
            $table->string('product_title');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone', 40)->nullable();
            // `amount` is what the product is worth to the academy and is what
            // revenue is counted from; the Paystack charge sits beside it.
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('reference', 64)->unique();
            $table->string('status', 20)->default('pending');   // pending | paid | failed
            $table->timestamp('paid_at')->nullable();

            // The buyer's key to their own download. Long and random because it
            // is the only thing standing between a link and the file.
            $table->string('download_token', 64)->unique();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['digital_product_id', 'status']);
            $table->index('buyer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_orders');
        Schema::dropIfExists('digital_products');
    }
};
