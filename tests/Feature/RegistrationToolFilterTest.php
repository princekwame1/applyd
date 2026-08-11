<?php

namespace Tests\Feature;

use App\Livewire\RegistrationsTable;
use App\Models\Registration;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationToolFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function registration(array $tools, string $name): Registration
    {
        static $n = 0;
        $n++;

        return Registration::create([
            'full_name' => $name,
            'gender' => config('bootcamp.genders')[0],
            'age_range' => config('bootcamp.age_ranges')[0],
            'country' => 'Ghana',
            'city' => 'Accra',
            'phone_country_code' => '+233',
            'phone' => '24123456'.$n,
            'email' => 'person'.$n.'@example.com',
            'education' => config('bootcamp.education_levels')[0],
            'tools' => $tools,
            'marketing_opt_in' => true,
        ]);
    }

    protected function filterBy(array $tools)
    {
        return Livewire::test(RegistrationsTable::class)
            ->set('filterComponents.tools', $tools);
    }

    /**
     * The filter only accepts values that exist as options, so a test must own
     * the tools it filters on rather than relying on the migration's seed list.
     */
    protected function tool(string $name): Tool
    {
        static $order = 0;

        return Tool::firstOrCreate(
            ['name' => $name],
            ['category' => 'Testing', 'sort_order' => ++$order]
        );
    }

    public function test_filtering_by_one_tool_returns_only_registrants_who_picked_it(): void
    {
        $this->actingAs($this->admin());
        $this->tool('Notion');
        $this->tool('Figma');

        $notion = $this->registration(['Notion', 'Canva'], 'Notion User');
        $figma = $this->registration(['Figma'], 'Figma User');

        $this->filterBy(['Notion'])
            ->assertSee($notion->email)
            ->assertDontSee($figma->email);
    }

    public function test_selecting_several_tools_returns_anyone_who_picked_any_of_them(): void
    {
        $this->actingAs($this->admin());
        $this->tool('Notion');
        $this->tool('Figma');
        $this->tool('Trello');

        $a = $this->registration(['Notion'], 'A');
        $b = $this->registration(['Figma'], 'B');
        $c = $this->registration(['Trello'], 'C');

        $this->filterBy(['Notion', 'Figma'])
            ->assertSee($a->email)
            ->assertSee($b->email)
            ->assertDontSee($c->email);
    }

    public function test_a_registrant_is_not_matched_by_a_partial_tool_name(): void
    {
        $this->actingAs($this->admin());

        // Guards against a LIKE-style implementation: a registrant who picked
        // "Canva" must not surface when filtering on the distinct tool "Can",
        // even though one name is a prefix of the other.
        $this->tool('Can');
        $this->tool('Canva');

        $canva = $this->registration(['Canva'], 'Canva User');
        $can = $this->registration(['Can'], 'Can User');

        $this->filterBy(['Can'])
            ->assertSee($can->email)
            ->assertDontSee($canva->email);
    }

    public function test_a_tool_removed_from_the_tools_table_can_no_longer_be_filtered_on(): void
    {
        $this->actingAs($this->admin());

        // rappasoft validates multi-select values against the options list, so a
        // stale value is discarded rather than filtering to an empty table.
        $a = $this->registration(['Notion'], 'A');
        $b = $this->registration(['Figma'], 'B');

        Tool::where('name', 'Notion')->delete();

        $this->filterBy(['Notion'])
            ->assertSee($a->email)
            ->assertSee($b->email);
    }

    public function test_no_filter_shows_everyone(): void
    {
        $this->actingAs($this->admin());

        $a = $this->registration(['Notion'], 'A');
        $b = $this->registration(['Figma'], 'B');

        Livewire::test(RegistrationsTable::class)
            ->assertSee($a->email)
            ->assertSee($b->email);
    }

    public function test_the_filter_options_come_from_the_tools_table(): void
    {
        $this->actingAs($this->admin());

        Tool::query()->delete();
        Tool::create(['name' => 'Zapier', 'category' => 'Automation', 'sort_order' => 1]);
        Tool::create(['name' => 'Airtable', 'category' => 'Data', 'sort_order' => 2]);

        $filter = collect(Livewire::test(RegistrationsTable::class)->instance()->filters())
            ->firstWhere(fn ($f) => $f->getName() === 'Tools');

        $this->assertNotNull($filter);
        $this->assertSame(['Zapier' => 'Zapier', 'Airtable' => 'Airtable'], $filter->getOptions());
    }

    public function test_the_tools_column_lists_names_instead_of_a_count(): void
    {
        $this->actingAs($this->admin());

        $this->registration(['Notion', 'Canva'], 'Two Tools');

        Livewire::test(RegistrationsTable::class)
            ->assertSee('Notion, Canva')
            ->assertDontSee('2 selected');
    }

    public function test_a_long_tool_list_is_truncated_with_a_remainder(): void
    {
        $this->actingAs($this->admin());

        $this->registration(['Notion', 'Canva', 'Figma', 'Trello', 'Slack'], 'Many Tools');

        Livewire::test(RegistrationsTable::class)
            ->assertSee('Notion, Canva, Figma')
            ->assertSee('+2 more');
    }
}
