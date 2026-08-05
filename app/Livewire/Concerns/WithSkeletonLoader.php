<?php

namespace App\Livewire\Concerns;

trait WithSkeletonLoader
{
    /**
     * Enable a skeleton loading placeholder for the table.
     * Call this from the table's configure() method.
     */
    public function configureSkeletonLoader(): void
    {
        $this->setLoadingPlaceholderEnabled();
        $this->setLoadingPlaceholderBlade('livewire-tables.loading-skeleton');
    }
}
