<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var User $user */
        $user = $this->getRecord();

        // Block non-superadmin from editing superadmin record
        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'You cannot edit the super admin account.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Model $record): bool => ! $record->isSuperAdmin()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        // Prevent changing superadmin's role or status
        if ($record->isSuperAdmin()) {
            $data['role'] = 'superadmin';
            $data['status'] = 'active';
        }

        return $data;
    }
}
