<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'revoked' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'User',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'revoked' => 'Revoked',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status === 'active' && $record->id !== auth()->id())
                    ->action(fn (User $record) => $record->update(['status' => 'revoked'])),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status === 'revoked')
                    ->action(fn (User $record) => $record->update(['status' => 'active'])),
                Action::make('managePassword')
                    ->label('Manage Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Manage User Password')
                    ->modalDescription('Choose how to handle the password for this user.')
                    ->form([
                        Radio::make('action_type')
                            ->label('Action')
                            ->options([
                                'set_password' => 'Set a specific password',
                                'set_password_and_notify' => 'Set password & email user',
                                'send_reset_link' => 'Send password reset link',
                            ])
                            ->default('set_password')
                            ->required()
                            ->live(),
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->minLength(8)
                            ->confirmed()
                            ->visible(fn ($get): bool => in_array($get('action_type'), ['set_password', 'set_password_and_notify']))
                            ->required(fn ($get): bool => in_array($get('action_type'), ['set_password', 'set_password_and_notify'])),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->visible(fn ($get): bool => in_array($get('action_type'), ['set_password', 'set_password_and_notify'])),
                    ])
                    ->action(function (User $record, array $data, Action $action): void {
                        match ($data['action_type']) {
                            'set_password' => static::handleSetPassword($record, $data['new_password']),
                            'set_password_and_notify' => static::handleSetPasswordAndNotify($record, $data['new_password']),
                            'send_reset_link' => static::handleSendResetLink($record),
                        };
                    })
                    ->successNotification(null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function handleSetPassword(User $record, string $password): void
    {
        $record->update(['password' => $password]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($record)
            ->log('set user password manually');

        Notification::make()
            ->title('Password updated successfully.')
            ->success()
            ->send();
    }

    private static function handleSetPasswordAndNotify(User $record, string $password): void
    {
        $record->update(['password' => $password]);
        $record->notify(new PasswordChangedNotification($password));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($record)
            ->log('set user password and sent email notification');

        Notification::make()
            ->title('Password updated and notification sent.')
            ->success()
            ->send();
    }

    private static function handleSendResetLink(User $record): void
    {
        $status = Password::sendResetLink(['email' => $record->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Failed to send reset link')
                ->body(__($status))
                ->danger()
                ->send();

            return;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($record)
            ->log('sent password reset link');

        Notification::make()
            ->title('Password reset link sent successfully.')
            ->success()
            ->send();
    }
}
