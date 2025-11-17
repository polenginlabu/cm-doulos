<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        // Update user attendance stats (only count Sunday Service for status)
        $user = User::find($this->record->user_id);
        if ($user) {
            $sundayAttendances = $user->attendances()
                ->where('attendance_type', 'sunday_service')
                ->count();

            $user->total_attendances = $sundayAttendances;

            // Update attendance status based on Sunday Service only
            if ($sundayAttendances >= 4) {
                $user->attendance_status = 'regular';
            } elseif ($sundayAttendances == 3) {
                $user->attendance_status = '4th';
            } elseif ($sundayAttendances == 2) {
                $user->attendance_status = '3rd';
            } elseif ($sundayAttendances == 1) {
                $user->attendance_status = '2nd';
            } else {
                $user->attendance_status = '1st';
            }

            // Update dates (only for Sunday Service)
            if ($this->record->attendance_type === 'sunday_service') {
                $firstAttendance = $user->attendances()
                    ->where('attendance_type', 'sunday_service')
                    ->orderBy('attendance_date')
                    ->first();

                if ($firstAttendance) {
                    $user->first_attendance_date = $firstAttendance->attendance_date;
                }

                $user->last_attendance_date = $this->record->attendance_date;
            }

            $user->save();
        }
    }
}

