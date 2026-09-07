<?php

namespace Tests\Feature;

use App\Livewire\DigitalProductsTable;
use App\Models\DigitalProduct;
use App\Models\EmailLog;
use App\Models\ProductOrder;
use App\Models\User;
use App\Support\Cms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Selling a digital product.
 *
 * The two things worth pinning: a file is only ever reachable through a
 * settled order, and an order is only ever settled once — a replayed callback
 * must not send a second delivery email, which reads to a buyer as a second
 * charge.
 */
class DigitalProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cms::flush();
        config(['services.paystack.secret' => 'sk_test_key']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function product(array $attributes = []): DigitalProduct
    {
        return DigitalProduct::create(array_merge([
            'title' => 'Content Calendar',
            'price' => 50,
            'category' => 'Templates',
            'file_path' => 'products/calendar.pdf',
            'file_name' => 'calendar.pdf',
            'file_size' => 2048,
            'is_published' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    private function order(DigitalProduct $product, array $attributes = []): ProductOrder
    {
        return ProductOrder::create(array_merge([
            'digital_product_id' => $product->id,
            'product_title' => $product->title,
            'buyer_name' => 'Ama Mensah',
            'buyer_email' => 'ama@example.com',
            'amount' => $product->price,
            'reference' => ProductOrder::newReference(),
            'status' => 'pending',
            'download_token' => ProductOrder::newToken(),
        ], $attributes));
    }

    /* ------------------------------------------------------------ the shop */

    public function test_the_shop_lists_published_products_only(): void
    {
        $this->product(['title' => 'On Sale']);
        $this->product(['title' => 'Not Ready', 'is_published' => false]);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('On Sale')
            ->assertDontSee('Not Ready');
    }

    public function test_an_unpublished_product_is_not_reachable(): void
    {
        $product = $this->product(['is_published' => false]);

        $this->get('/shop/'.$product->slug)->assertNotFound();
        $this->post('/shop/'.$product->slug.'/buy', [
            'buyer_name' => 'Ama Mensah',
            'buyer_email' => 'ama@example.com',
        ])->assertNotFound();
    }

    /* ------------------------------------------------------------- buying */

    public function test_buying_redirects_to_paystack_and_records_a_pending_order(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz'],
            ]),
        ]);

        $product = $this->product();

        $this->post('/shop/'.$product->slug.'/buy', [
            'buyer_name' => 'Ama Mensah',
            'buyer_email' => 'ama@example.com',
            'buyer_phone' => '0240000000',
        ])->assertRedirect('https://checkout.paystack.com/xyz');

        $order = ProductOrder::firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame('Content Calendar', $order->product_title);
        $this->assertSame(50.0, (float) $order->amount);
        $this->assertNotEmpty($order->download_token);
        // Nothing is delivered before the money is confirmed.
        $this->assertSame(0, EmailLog::count());
    }

    public function test_a_free_product_is_settled_and_delivered_without_paystack(): void
    {
        $product = $this->product(['title' => 'Free Checklist', 'price' => 0]);

        $response = $this->post('/shop/'.$product->slug.'/buy', [
            'buyer_name' => 'Ama Mensah',
            'buyer_email' => 'ama@example.com',
        ]);

        $order = ProductOrder::firstOrFail();

        $response->assertRedirect(route('shop.download', $order->download_token));
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(1, EmailLog::where('template_key', 'product_delivery')->count());
    }

    public function test_a_successful_callback_settles_the_order_and_emails_the_link(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ]),
        ]);

        $product = $this->product();
        $order = $this->order($product);

        $this->get('/shop/callback?reference='.$order->reference)
            ->assertRedirect(route('shop.download', $order->download_token));

        $order->refresh();

        $this->assertSame('paid', $order->status);

        $log = EmailLog::where('template_key', 'product_delivery')->firstOrFail();
        $this->assertSame('ama@example.com', $log->email);
        $this->assertStringContainsString($order->download_token, $log->cta_url);
    }

    public function test_a_replayed_callback_does_not_deliver_twice(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ]),
        ]);

        $order = $this->order($this->product());

        $this->get('/shop/callback?reference='.$order->reference);
        $this->get('/shop/callback?reference='.$order->reference);

        $this->assertSame(1, EmailLog::where('template_key', 'product_delivery')->count());
    }

    public function test_a_failed_payment_leaves_the_order_unpaid(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'abandoned'],
            ]),
        ]);

        $product = $this->product();
        $order = $this->order($product);

        $this->get('/shop/callback?reference='.$order->reference)
            ->assertRedirect(route('shop.show', $product));

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame(0, EmailLog::count());
    }

    /* --------------------------------------------------------- downloading */

    public function test_the_file_is_only_served_for_a_settled_order(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('products/calendar.pdf', 'the goods');

        $product = $this->product();
        $pending = $this->order($product);
        $paid = $this->order($product, [
            'reference' => ProductOrder::newReference(),
            'download_token' => ProductOrder::newToken(),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->get('/downloads/'.$pending->download_token.'/file')->assertForbidden();
        $this->get('/downloads/'.$paid->download_token.'/file')->assertOk();

        $this->assertSame(1, $paid->fresh()->download_count);
        $this->assertNotNull($paid->fresh()->last_downloaded_at);
    }

    public function test_an_unknown_token_is_not_found(): void
    {
        $this->get('/downloads/'.str_repeat('z', 20))->assertNotFound();
        $this->get('/downloads/'.str_repeat('z', 20).'/file')->assertNotFound();
    }

    public function test_a_link_product_shows_the_link_and_serves_no_file(): void
    {
        $product = $this->product([
            'title' => 'Notion Workspace',
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'external_url' => 'https://example.com/workspace',
        ]);

        $order = $this->order($product, ['status' => 'paid', 'paid_at' => now()]);

        $this->get('/downloads/'.$order->download_token)
            ->assertOk()
            ->assertSee('https://example.com/workspace');

        $this->get('/downloads/'.$order->download_token.'/file')->assertNotFound();
    }

    /* -------------------------------------------------------------- admin */

    public function test_an_admin_adds_a_product_and_the_file_stays_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/dashboard/products', [
                'title' => 'Pitch Deck Template',
                'tagline' => 'Twelve slides that close',
                'price' => '120',
                'category' => 'Templates',
                'delivery' => 'file',
                'file' => UploadedFile::fake()->create('deck.pdf', 40, 'application/pdf'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('dashboard.products'));

        $product = DigitalProduct::firstOrFail();

        $this->assertSame('pitch-deck-template', $product->slug);
        $this->assertSame('deck.pdf', $product->file_name);
        $this->assertTrue($product->deliversFile());
        Storage::disk('local')->assertExists($product->file_path);
        Storage::disk('public')->assertMissing($product->file_path);
    }

    public function test_a_product_needs_a_file_or_a_link(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/products', [
                'title' => 'Nothing Attached',
                'price' => '10',
                'delivery' => 'file',
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, DigitalProduct::count());
    }

    public function test_switching_to_a_link_drops_the_stored_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('products/calendar.pdf', 'the goods');

        $product = $this->product();

        $this->actingAs($this->admin())
            ->put('/dashboard/products/'.$product->id, [
                'title' => $product->title,
                'price' => '50',
                'delivery' => 'link',
                'external_url' => 'https://example.com/file',
                'is_published' => '1',
            ])
            ->assertRedirect(route('dashboard.products'));

        $product->refresh();

        $this->assertNull($product->file_path);
        $this->assertSame('https://example.com/file', $product->external_url);
        Storage::disk('local')->assertMissing('products/calendar.pdf');
    }

    public function test_a_sold_product_cannot_be_deleted(): void
    {
        $product = $this->product();
        $this->order($product, ['status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($this->admin())
            ->delete('/dashboard/products/'.$product->id)
            ->assertRedirect(route('dashboard.products'));

        $this->assertDatabaseHas('digital_products', ['id' => $product->id]);

        // And the bulk path, which would otherwise go straight round the rule.
        Livewire::actingAs($this->admin())
            ->test(DigitalProductsTable::class)
            ->call('performDelete', $product->id);

        $this->assertDatabaseHas('digital_products', ['id' => $product->id]);
    }

    public function test_deleting_an_unsold_product_removes_its_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('products/calendar.pdf', 'the goods');

        $product = $this->product();

        $this->actingAs($this->admin())
            ->delete('/dashboard/products/'.$product->id)
            ->assertRedirect(route('dashboard.products'));

        $this->assertDatabaseMissing('digital_products', ['id' => $product->id]);
        Storage::disk('local')->assertMissing('products/calendar.pdf');
    }

    public function test_an_admin_can_resend_a_download_link(): void
    {
        $order = $this->order($this->product(), ['status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($this->admin())
            ->from(route('dashboard.product-orders'))
            ->post('/dashboard/product-orders/'.$order->id.'/resend')
            ->assertRedirect(route('dashboard.product-orders'));

        $this->assertSame(1, EmailLog::where('template_key', 'product_delivery')->count());
    }

    public function test_marking_an_offline_order_paid_delivers_it(): void
    {
        $order = $this->order($this->product());

        $this->actingAs($this->admin())
            ->from(route('dashboard.product-orders'))
            ->post('/dashboard/product-orders/'.$order->id.'/mark-paid')
            ->assertRedirect(route('dashboard.product-orders'));

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, EmailLog::where('template_key', 'product_delivery')->count());
    }

    /** Every screen in the feature renders — a Blade slip here is otherwise silent. */
    public function test_the_screens_render(): void
    {
        $product = $this->product();
        $order = $this->order($product, ['status' => 'paid', 'paid_at' => now()]);

        $this->get('/shop/'.$product->slug)->assertOk()->assertSee('Content Calendar');
        $this->get('/downloads/'.$order->download_token)->assertOk();

        $admin = $this->admin();
        $this->actingAs($admin)->get('/dashboard/products')->assertOk();
        $this->actingAs($admin)->get('/dashboard/products/'.$product->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/dashboard/product-orders')->assertOk();
        $this->actingAs($admin)->get('/dashboard/product-orders/'.$order->id)->assertOk()->assertSee($order->reference);
    }

    public function test_the_shop_is_closed_to_a_guest_dashboard(): void
    {
        $this->get('/dashboard/products')->assertRedirect(route('login'));
        $this->get('/dashboard/product-orders')->assertRedirect(route('login'));
    }
}
