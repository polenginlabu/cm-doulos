<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Attendance History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('attendance_date')
                    ->label('Attendance Date')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('cell_group_id')
                    ->label('Cell Group')
                    ->relationship('cellGroup', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('attendance_type')
                    ->options([
                        'sunday_service' => 'Sunday Service (Main)',
                        'crossover' => 'CrossOver (Young Professionals)',
                        'wildsons' => 'WildSons (Youth)',
                        'cell_group' => 'Cell Group',
                        'service' => 'Service',
                        'event' => 'Event',
                    ])
                    ->default('sunday_service')
                    ->required(),
                Forms\Components\Toggle::make('is_present')
                    ->label('Present')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cellGroup.name')
                    ->label('Cell Group')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('attendance_type')
                    ->colors([
                        'primary' => 'cell_group',
                        'success' => 'service',
                        'info' => 'event',
                    ]),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Present')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_type')
                    ->options([
                        'sunday_service' => 'Sunday Service',
                        'crossover' => 'CrossOver',
                        'wildsons' => 'WildSons',
                        'cell_group' => 'Cell Group',
                        'service' => 'Service',
                        'event' => 'Event',
                    ]),
                Tables\Filters\Filter::make('attendance_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('attendance_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('attendance_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update user attendance stats (only count Sunday Service for status)
                        $user = $this->ownerRecord;
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
                        if ($record->attendance_type === 'sunday_service') {
                            $firstAttendance = $user->attendances()
                                ->where('attendance_type', 'sunday_service')
                                ->orderBy('attendance_date')
                                ->first();

                            if ($firstAttendance) {
                                $user->first_attendance_date = $firstAttendance->attendance_date;
                            }

                            $user->last_attendance_date = $record->attendance_date;
                        }

                        $user->save();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('attendance_date', 'desc');
    }
}

