<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * What the money is for. A category belongs to one side of the books, so an
 * expense heading can never be picked for income.
 */
class FinanceCategoryController extends Controller
{
    public function index()
    {
        return view('dashboard.finance.categories');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (FinanceCategory::max('sort_order') ?? 0) + 1;

        FinanceCategory::create($data);

        return $this->modalOk($request, 'dashboard.finance.categories', 'Category added.');
    }

    public function edit(Request $request, FinanceCategory $category)
    {
        if ($request->ajax()) {
            return view('dashboard.finance.partials.category-form', ['model' => $category]);
        }

        return redirect()->route('dashboard.finance.categories');
    }

    public function update(Request $request, FinanceCategory $category)
    {
        $data = $this->validated($request, $category);

        // Moving a category across the books would leave its existing entries
        // filed under a heading from the other side.
        if ($data['type'] !== $category->type && $category->transactions()->exists()) {
            return $this->modalError(
                $request,
                'dashboard.finance.categories',
                'This category already has '.$category->transactions()->count().' entries, so it can\'t be moved to the other side of the books. Retire it and make a new one instead.',
            );
        }

        $category->update($data);

        return $this->modalOk($request, 'dashboard.finance.categories', 'Category updated.');
    }

    public function destroy(FinanceCategory $category)
    {
        // Entries outlive their heading: deleting a used category would silently
        // reclassify history, so it gets switched off instead.
        if ($category->transactions()->exists()) {
            return redirect()
                ->route('dashboard.finance.categories')
                ->with('error', 'This category is used by '.$category->transactions()->count().' entries, so it can\'t be deleted. Switch it off instead — it disappears from the picker and the old entries keep their heading.');
        }

        $category->delete();

        return redirect()
            ->route('dashboard.finance.categories')
            ->with('status', 'Category deleted.');
    }

    private function validated(Request $request, ?FinanceCategory $category = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                // "Fees" is allowed on both sides, just not twice on one.
                Rule::unique('finance_categories', 'name')
                    ->where('type', $request->input('type'))
                    ->ignore($category?->id),
            ],
            'type' => ['required', Rule::in(array_keys(FinanceTransaction::TYPES))],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'There\'s already a category with that name on this side of the books.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
