<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FacilitatorsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return User::facilitators()->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Portal access', 'Login sent', 'Roles', 'Added'];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?: '',
            match ($user->access_state) {
                'active' => 'Signed in',
                'sent' => 'Waiting to sign in',
                default => 'Never sent',
            },
            $user->credentials_sent_at?->format('Y-m-d H:i') ?: '',
            $user->role_label,
            $user->created_at->format('Y-m-d'),
        ];
    }
}
