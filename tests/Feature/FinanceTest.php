<?php

namespace Tests\Feature;

use App\Exports\FinanceTransactionsExport;
use App\Livewire\FinanceCategoriesTable;
use App\Livewire\FinanceTransactionsTable;
use App\Models\FinanceCategory;
use App\Models\FinanceDocument;
use App\Models\FinanceTransaction;
use App\Models\User;
use App\Support\Finance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The books: recording money in and out, the paperwork behind each entry, and
 * the totals everything on the overview is read off.
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        // Granted directly as well as through the role, so a test that strips
        // the permission off the admin role to build an outsider doesn't
        // quietly lock this user out too.
        $user->givePermissionTo('manage finance');

        return $user;
    }

    /**
     * An admin who may not see the books. The permission is seeded onto the
     * admin *role*, so revoking it from one user would change nothing — the
     * role is what has to lose it.
     */
    protected function outsider(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Role::findByName('admin')->revokePermissionTo('manage finance');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    protected function category(string $type = FinanceTransaction::EXPENSE): FinanceCategory
    {
        return FinanceCategory::ofType($type)->ordered()->first();
    }

    protected function entry(array $attributes = []): FinanceTransaction
    {
        return FinanceTransaction::create(array_merge([
            'type' => FinanceTransaction::EXPENSE,
            'amount' => '250.00',
            'occurred_on' => now()->subDay()->toDateString(),
            'party' => 'Accra Conference Centre',
        ], $attributes));
    }

    public function test_the_migration_seeds_categories_for_both_sides_of_the_books(): void
    {
        $this->assertTrue(FinanceCategory::ofType(FinanceTransaction::INCOME)->exists());
        $this->assertTrue(FinanceCategory::ofType(FinanceTransaction::EXPENSE)->exists());
    }

    public function test_a_guest_cannot_reach_any_of_it(): void
    {
        $entry = $this->entry();

        $this->get(route('dashboard.finance'))->assertRedirect(route('login'));
        $this->get(route('dashboard.finance.transaction', $entry))->assertRedirect(route('login'));
        $this->get(route('dashboard.finance.categories'))->assertRedirect(route('login'));
        $this->post(route('dashboard.finance.store'), ['amount' => 10])->assertRedirect(route('login'));

        $this->assertSame(1, FinanceTransaction::count());
    }

    public function test_an_admin_without_the_finance_permission_is_kept_out(): void
    {
        $entry = $this->entry();

        $this->actingAs($this->outsider());

        $this->get(route('dashboard.finance'))->assertForbidden();
        $this->get(route('dashboard.finance.transaction', $entry))->assertForbidden();
        $this->get(route('dashboard.finance.export'))->assertForbidden();
        $this->post(route('dashboard.finance.store'), ['amount' => 10])->assertForbidden();

        // …and the sidebar doesn't dangle a link they can't use.
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Income &amp; Expenses', false);
    }

    public function test_the_sidebar_offers_finance_to_someone_who_may_see_it(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Income &amp; Expenses', false);
    }

    public function test_an_admin_records_money_out_and_it_gets_a_reference(): void
    {
        $admin = $this->admin();
        $category = $this->category();

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE,
            'finance_category_id' => $category->id,
            'amount' => '1250.50',
            'occurred_on' => now()->subDays(3)->toDateString(),
            'party' => 'Accra Conference Centre',
            'method' => 'Bank transfer',
            'document_no' => 'INV-2026-0184',
            'note' => 'Deposit for the September cohort venue.',
        ])->assertRedirect(route('dashboard.finance'));

        $entry = FinanceTransaction::sole();

        $this->assertSame('1250.50', $entry->amount);
        $this->assertStringStartsWith('EX-', $entry->reference);
        $this->assertSame($category->id, $entry->finance_category_id);
        // Who keyed it in is part of the audit trail.
        $this->assertSame($admin->id, $entry->recorded_by);
    }

    public function test_money_in_gets_its_own_reference_prefix(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::INCOME,
            'amount' => '400',
            'occurred_on' => now()->toDateString(),
        ]);

        $this->assertStringStartsWith('IN-', FinanceTransaction::sole()->reference);
    }

    public function test_a_category_from_the_wrong_side_of_the_books_is_refused(): void
    {
        $incomeCategory = $this->category(FinanceTransaction::INCOME);

        $this->actingAs($this->admin())->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE,
            'finance_category_id' => $incomeCategory->id,
            'amount' => '50',
            'occurred_on' => now()->toDateString(),
        ])->assertSessionHasErrors('finance_category_id');

        $this->assertSame(0, FinanceTransaction::count());
    }

    public function test_an_amount_must_be_positive_and_the_date_cannot_be_in_the_future(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE, 'amount' => '0', 'occurred_on' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE, 'amount' => '-40', 'occurred_on' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE, 'amount' => '40', 'occurred_on' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('occurred_on');

        $this->assertSame(0, FinanceTransaction::count());
    }

    public function test_an_invoice_and_a_receipt_land_on_the_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE,
            'amount' => '900',
            'occurred_on' => now()->toDateString(),
            'invoice' => UploadedFile::fake()->create('venue-invoice.pdf', 90, 'application/pdf'),
            'receipt' => UploadedFile::fake()->create('venue-receipt.jpg', 60, 'image/jpeg'),
        ])->assertRedirect(route('dashboard.finance'));

        $entry = FinanceTransaction::sole()->load('documents');

        $this->assertSame('venue-invoice.pdf', $entry->invoice()->original_name);
        $this->assertSame('venue-receipt.jpg', $entry->receipt()->original_name);

        foreach ($entry->documents as $document) {
            $this->assertStringStartsWith('finance/'.$entry->id.'/', $document->path);
            Storage::disk('local')->assertExists($document->path);
        }

        // Never the public disk — a receipt must not be guessable from a URL.
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_an_unsupported_or_oversized_upload_is_refused(): void
    {
        Storage::fake('local');

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE, 'amount' => '10', 'occurred_on' => now()->toDateString(),
            'invoice' => UploadedFile::fake()->create('payload.php', 5, 'text/x-php'),
        ])->assertSessionHasErrors('invoice');

        $this->actingAs($admin)->post(route('dashboard.finance.store'), [
            'type' => FinanceTransaction::EXPENSE, 'amount' => '10', 'occurred_on' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->create('huge.pdf', FinanceDocument::MAX_KB + 500, 'application/pdf'),
        ])->assertSessionHasErrors('receipt');

        $this->assertSame(0, FinanceTransaction::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_re_uploading_an_invoice_replaces_the_old_one_on_disk(): void
    {
        Storage::fake('local');

        $entry = $this->entry();

        $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
            'kind' => FinanceDocument::INVOICE,
            'document' => UploadedFile::fake()->create('first.pdf', 20, 'application/pdf'),
        ]);

        $first = $entry->documents()->sole();

        $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
            'kind' => FinanceDocument::INVOICE,
            'document' => UploadedFile::fake()->create('corrected.pdf', 20, 'application/pdf'),
        ]);

        // One invoice, not two — and the superseded file is gone, not orphaned.
        $this->assertSame(1, $entry->documents()->where('kind', FinanceDocument::INVOICE)->count());
        $this->assertSame('corrected.pdf', $entry->documents()->sole()->original_name);
        Storage::disk('local')->assertMissing($first->path);
    }

    public function test_other_documents_accumulate_rather_than_replace(): void
    {
        Storage::fake('local');

        $entry = $this->entry();

        foreach (['quote.pdf', 'delivery-note.pdf'] as $name) {
            $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
                'kind' => FinanceDocument::OTHER,
                'document' => UploadedFile::fake()->create($name, 20, 'application/pdf'),
            ]);
        }

        $this->assertSame(2, $entry->documents()->count());
    }

    public function test_a_document_only_downloads_for_someone_allowed_in(): void
    {
        Storage::fake('local');

        $entry = $this->entry();

        $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
            'kind' => FinanceDocument::RECEIPT,
            'document' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ]);

        $document = $entry->documents()->sole();

        $this->actingAs($this->outsider())
            ->get(route('dashboard.finance.document', $document))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('dashboard.finance.document', $document))
            ->assertOk()
            ->assertDownload('receipt.pdf');
    }

    public function test_deleting_an_entry_takes_its_files_off_the_disk(): void
    {
        Storage::fake('local');

        $entry = $this->entry();

        $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
            'kind' => FinanceDocument::RECEIPT,
            'document' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ]);

        $path = $entry->documents()->sole()->path;

        $this->actingAs($this->admin())
            ->delete(route('dashboard.finance.destroy', $entry))
            ->assertRedirect(route('dashboard.finance'));

        $this->assertSame(0, FinanceTransaction::count());
        $this->assertSame(0, FinanceDocument::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_the_totals_add_up_and_respect_the_period(): void
    {
        $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '1000.00', 'occurred_on' => '2026-03-10']);
        $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '250.25', 'occurred_on' => '2026-03-20']);
        $this->entry(['type' => FinanceTransaction::EXPENSE, 'amount' => '400.25', 'occurred_on' => '2026-03-15']);
        // Outside the window below.
        $this->entry(['type' => FinanceTransaction::EXPENSE, 'amount' => '9999.00', 'occurred_on' => '2025-11-01']);

        $march = Finance::summarise(FinanceTransaction::query()->betweenDates('2026-03-01', '2026-03-31'));

        $this->assertSame(1250.25, $march['income']);
        $this->assertSame(400.25, $march['expense']);
        $this->assertSame(850.0, $march['net']);
        $this->assertSame(3, $march['count']);

        $all = Finance::summarise(FinanceTransaction::query());
        $this->assertSame(-9149.0, round($all['net'], 2));
    }

    public function test_the_overview_reports_on_the_window_it_was_asked_for(): void
    {
        $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '1000.00', 'occurred_on' => '2026-03-10']);
        $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '77.00', 'occurred_on' => '2025-01-05']);

        $this->actingAs($this->admin())
            ->get(route('dashboard.finance', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee(Finance::money(1000))
            ->assertDontSee(Finance::money(77));
    }

    public function test_the_breakdown_totals_by_category(): void
    {
        $venue = $this->category();
        $other = FinanceCategory::ofType(FinanceTransaction::EXPENSE)->where('id', '!=', $venue->id)->first();

        $this->entry(['finance_category_id' => $venue->id, 'amount' => '300.00']);
        $this->entry(['finance_category_id' => $venue->id, 'amount' => '100.00']);
        $this->entry(['finance_category_id' => $other->id, 'amount' => '100.00']);
        $this->entry(['finance_category_id' => null, 'amount' => '100.00']);

        $rows = Finance::byCategory(FinanceTransaction::query(), FinanceTransaction::EXPENSE);

        $this->assertSame($venue->name, $rows[0]['name']);
        $this->assertSame(400.0, $rows[0]['total']);
        $this->assertSame(67.0, (float) $rows[0]['share']);
        $this->assertContains('Uncategorised', array_column($rows, 'name'));
    }

    public function test_the_tables_show_income_and_expense_in_their_own_columns(): void
    {
        $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '1000.00', 'party' => 'Sponsor Ltd']);
        $this->entry(['type' => FinanceTransaction::EXPENSE, 'amount' => '250.00', 'party' => 'Accra Conference Centre']);

        $this->actingAs($this->admin());

        Livewire::test(FinanceTransactionsTable::class)
            ->assertSee('Sponsor Ltd')
            ->assertSee('Accra Conference Centre')
            ->assertSee(Finance::money(1000))
            ->assertSee(Finance::money(250));

        Livewire::test(FinanceCategoriesTable::class)
            ->assertSee($this->category()->name)
            ->assertSee('Money out');
    }

    public function test_a_category_in_use_cannot_be_deleted_or_moved_across_the_books(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $this->entry(['finance_category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('dashboard.finance.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->put(route('dashboard.finance.categories.update', $category), [
                'name' => $category->name, 'type' => FinanceTransaction::INCOME, 'is_active' => '1',
            ])
            ->assertSessionHas('error');

        $this->assertSame(FinanceTransaction::EXPENSE, $category->refresh()->type);
        $this->assertTrue($category->exists());
    }

    public function test_retiring_a_category_keeps_its_history_but_drops_it_from_the_picker(): void
    {
        $category = $this->category();
        $this->entry(['finance_category_id' => $category->id]);

        $this->actingAs($this->admin())->put(route('dashboard.finance.categories.update', $category), [
            'name' => $category->name, 'type' => $category->type,
        ]);

        $this->assertFalse($category->refresh()->is_active);
        $this->assertSame(1, $category->transactions()->count());
        $this->assertFalse(FinanceCategory::active()->whereKey($category->id)->exists());
    }

    public function test_deleting_a_category_leaves_its_entries_standing(): void
    {
        $category = FinanceCategory::create(['name' => 'Temporary', 'type' => FinanceTransaction::EXPENSE]);
        $entry = $this->entry(['finance_category_id' => $category->id]);

        // Nothing filed under it yet at the DB level? There is — so force the
        // path the FK guards: delete the model directly, as an admin can once
        // the entries move away.
        $entry->update(['finance_category_id' => null]);

        $this->actingAs($this->admin())
            ->delete(route('dashboard.finance.categories.destroy', $category))
            ->assertSessionHas('status');

        $this->assertSame(1, FinanceTransaction::count());
        $this->assertNull($entry->refresh()->finance_category_id);
    }

    public function test_the_export_splits_money_in_and_out_into_their_own_columns(): void
    {
        $income = $this->entry(['type' => FinanceTransaction::INCOME, 'amount' => '1000.00', 'occurred_on' => '2026-03-10']);
        $expense = $this->entry(['type' => FinanceTransaction::EXPENSE, 'amount' => '250.00', 'occurred_on' => '2026-03-11']);

        $export = new FinanceTransactionsExport('2026-03-01', '2026-03-31');

        $this->assertSame([1000.0, null], array_slice($export->map($income), 4, 2));
        $this->assertSame([null, 250.0], array_slice($export->map($expense), 4, 2));
        $this->assertCount(2, $export->collection());

        $this->actingAs($this->admin())
            ->get(route('dashboard.finance.export', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk();
    }

    public function test_the_detail_page_lists_the_paperwork(): void
    {
        Storage::fake('local');

        $entry = $this->entry(['note' => 'Deposit for the September cohort venue.']);

        $this->actingAs($this->admin())->post(route('dashboard.finance.documents.store', $entry), [
            'kind' => FinanceDocument::INVOICE,
            'document' => UploadedFile::fake()->create('venue-invoice.pdf', 20, 'application/pdf'),
        ]);

        $this->actingAs($this->admin())
            ->get(route('dashboard.finance.transaction', $entry))
            ->assertOk()
            ->assertSee($entry->reference)
            ->assertSee('venue-invoice.pdf')
            ->assertSee('Deposit for the September cohort venue.')
            ->assertSee('Accra Conference Centre');
    }
}
