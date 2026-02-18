<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (?User $record): bool => $record?->isSuperAdmin() ?? false),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?User $record): bool => $record?->isSuperAdmin() ?? false),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255)
                    ->disabled(fn (?User $record): bool => $record?->isSuperAdmin() && ! auth()->user()?->isSuperAdmin())
                    ->helperText(fn (?User $record): ?string => $record?->isSuperAdmin() ? 'Super admin password can only be changed by the super admin themselves.' : null),
                Select::make('role')
                    ->options(fn (?User $record): array => $record?->isSuperAdmin()
                        ? ['superadmin' => 'Super Admin']
                        : ['admin' => 'Admin', 'user' => 'User'])
                    ->required()
                    ->default('user')
                    ->disabled(fn (?User $record): bool => $record?->isSuperAdmin() ?? false),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'revoked' => 'Revoked',
                    ])
                    ->required()
                    ->default('active')
                    ->disabled(fn (?User $record): bool => $record?->isSuperAdmin() ?? false),
                TextInput::make('company_name')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
            ]);
    }
}
