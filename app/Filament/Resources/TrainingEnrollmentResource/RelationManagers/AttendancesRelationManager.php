<?php

namespace App\Filament\Resources\TrainingEnrollmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Attendance History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('lesson_number')
                    ->label('Lesson Number')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(function ($get, $livewire) {
                        if ($livewire->ownerRecord && $livewire->ownerRecord->training) {
                            return $livewire->ownerRecord->training->total_lessons;
                        }
                        return 100;
                    })
                    ->helperText('Lesson number (1 to total lessons)')
                    ->rules([
                        function ($get, $livewire) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                if ($value && $livewire->ownerRecord) {
                                    // Check if this lesson is already recorded for this enrollment
                                    $query = \App\Models\TrainingAttendance::where('training_enrollment_id', $livewire->ownerRecord->id)
                                        ->where('lesson_number', $value);

                                    // Exclude current record when editing
                                    if ($livewire->mountedTableActionRecord) {
                                        $query->where('id', '!=', $livewire->mountedTableActionRecord->id);
                                    }

                                    if ($query->exists()) {
                                        $fail('Attendance for this lesson has already been recorded for this enrollment.');
                                    }
                                }
                            };
                        },
                    ]),
                Forms\Components\DatePicker::make('attendance_date')
                    ->label('Attendance Date')
                    ->default(now())
                    ->required(),
                Forms\Components\Toggle::make('is_present')
                    ->label('Present')
                    ->default(true)
                    ->helperText('Mark as present or absent'),
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('lesson_number')
            ->columns([
                Tables\Columns\TextColumn::make('lesson_number')
                    ->label('Lesson')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Present')
                    ->boolean(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_present')
                    ->label('Present'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->defaultSort('lesson_number', 'asc');
    }
}

