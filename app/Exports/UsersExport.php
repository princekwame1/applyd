<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return User::with('roles')->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Roles', 'Created At'];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->getRoleNames()->implode(', '),
            $user->created_at->format('Y-m-d H:i'),
        ];
    }
}
