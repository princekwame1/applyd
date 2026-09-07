<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Company;
use App\Models\JobOpening;
use App\Services\JobBoardNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class JobPostingsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = JobOpening::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);

        $this->setAdditionalSelects([
            'job_openings.company_id',
            'job_openings.is_open',
            'job_openings.deadline',
            'job_openings.review_note',
        ]);
    }

    public function builder(): Builder
    {
        // The company's own status decides half of whether a posting is live,
        // and the "Live" column reads it per row.
        return JobOpening::query()->with('company');
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Review')
                ->options([
                    '' => 'All',
                    JobOpening::PENDING => 'Pending review',
                    JobOpening::APPROVED => 'Approved',
                    JobOpening::REJECTED => 'Rejected',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('job_openings.status', $value)),
            SelectFilter::make('On the board')
                ->options(['' => 'All', 'yes' => 'Live now', 'no' => 'Not live'])
                ->filter(function (Builder $builder, string $value) {
                    // Reuses the one definition of "live" rather than
                    // restating it, so the filter can't drift from /jobs.
                    return $value === 'yes'
                        ? $builder->open()
                        : $builder->whereNotIn('job_openings.id', JobOpening::query()->open()->select('job_openings.id'));
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Company', 'company.name')
                ->sortable()
                ->searchable(),
            Column::make('Sector', 'sector')
                ->sortable()
                ->format(fn ($value) => e($value ?: '—')),
            Column::make('Review', 'status')
                ->sortable()
                ->format(fn ($value) => match ($value) {
                    JobOpening::APPROVED => '<span class="badge badge-yes">Approved</span>',
                    JobOpening::REJECTED => '<span class="badge badge-no">Rejected</span>',
                    default => '<span class="status-chip status-pending">Pending</span>',
                })
                ->html(),
            // Approved is not the same as visible: the company may still be
            // unverified, or the recruiter may have closed it. Saying which
            // saves an admin wondering why an approved job isn't showing.
            Column::make('On the board')
                ->label(function ($row) {
                    if ($row->is_live) {
                        return '<span class="badge badge-yes">Live</span>';
                    }

                    $reason = match (true) {
                        ! $row->isApproved() => 'awaiting review',
                        ! $row->company?->isApproved() => 'company '.($row->company?->status === Company::REJECTED ? 'rejected' : 'unverified'),
                        ! $row->is_open => 'closed by employer',
                        default => 'past deadline',
                    };

                    return '<span class="badge badge-no">No</span> <span style="color:var(--ink-soft); font-size:.85em;">'.e($reason).'</span>';
                })
                ->html(),
            Column::make('Applicants')
                ->label(fn ($row) => number_format($row->applications()->count())),
            Column::make('Posted', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.job-postings.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return [
            'approveSelected' => 'Approve selected',
            'deleteSelected' => 'Delete selected',
        ];
    }

    /** Bulk approve only — a rejection needs its own reason. */
    public function approveSelected(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'super']), 403);

        $notifier = app(JobBoardNotificationService::class);
        $approved = 0;
        $skipped = 0;

        foreach (JobOpening::whereIn('id', $this->getSelected())->with('company.user')->get() as $opening) {
            if ($opening->approve(auth()->user())) {
                $notifier->jobApproved($opening);
                $approved++;
            } else {
                $skipped++;
            }
        }

        $this->clearSelected();

        $message = $approved.' '.str('posting')->plural($approved).' approved and the employers emailed.';

        if ($skipped) {
            $message .= ' '.$skipped.' already approved, so nothing was sent.';
        }

        session()->flash('status', $message);
    }

    protected function deleteNoun(): string
    {
        return 'posting';
    }

    protected function deleteLabel(Model $row): string
    {
        return $row->title;
    }

    protected function deleteWarning(): string
    {
        return 'Every application sent to it, and the CVs attached, go too. Rejecting it instead keeps the record and tells the employer why.';
    }
}
