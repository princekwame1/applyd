<?php

namespace App\Livewire;

use App\Models\Registration;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RegistrationsTable extends DataTableComponent
{
    protected $model = Registration::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([15, 25, 50, 100]);
        $this->setPerPage(15);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')
                ->sortable()
                ->searchable(),
            Column::make('Location', 'country')
                ->sortable()
                ->searchable()
                ->format(fn ($value, $row) => $value.' — '.$row->city),
            Column::make('City', 'city')
                ->searchable()
                ->hideIf(true),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Column::make('Phone', 'phone')
                ->searchable()
                ->format(fn ($value, $row) => $row->phone_country_code.' '.$value),
            Column::make('Tools', 'tools')
                ->format(fn ($value) => count($value ?? []).' selected'),
            Column::make('Opt-in', 'marketing_opt_in')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Yes</span>'
                    : '<span class="badge badge-no">No</span>')
                ->html(),
            Column::make('Registered', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => '<a href="'.route('dashboard.show', $value).'">View</a>')
                ->html(),
        ];
    }
}
