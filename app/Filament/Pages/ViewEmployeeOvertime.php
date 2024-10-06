<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use App\Livewire\EmployeesOvertime;

class ViewEmployeeOvertime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.view-employee-overtime';

    protected static ?string $title = 'Add Employees to Overtime';

    protected static ?string $navigationGroup = "Overtime/Assign";

    public static function canAccess(): bool
    {
    return Auth::user()->role === User::ROLE_ADMINUSER || Auth::user()->role === User::ROLE_VICEPRES || Auth::user()->role === User::ROLE_ADMIN;
    }

    protected function getWidgets(): array
    {
        return [
            EmployeesOvertime::class,
        ];

        
    }
}
