<?php

namespace App\Exports;

use App\Models\Post;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PostsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Post::with('category')->newestFirst()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Title', 'Category', 'Author', 'Status', 'Published At', 'Updated At'];
    }

    public function map($post): array
    {
        return [
            $post->id,
            $post->title,
            $post->category?->name,
            $post->author,
            $post->is_published ? 'Published' : 'Draft',
            $post->published_at?->format('Y-m-d H:i'),
            $post->updated_at->format('Y-m-d H:i'),
        ];
    }
}
