<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingBatchResource\Pages;
use App\Models\TrainingBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrainingBatchResource extends Resource
{
    protected static ?string $model = TrainingBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Training Batches';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Information')
                    ->schema([
                        Forms\Components\Select::make('training_id')
                            ->label('Training')
                            ->options(function () {
                                return \App\Models\Training::where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($training) {
                                        return [$training->id => $training->name . ' (' . $training->code . ')'];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Select the training program'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Q1 2025, Batch 1, January 2025')
                            ->helperText('Batch name or identifier'),
                        Forms\Components\TextInput::make('code')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., LC-Q1-2025, SOL1-B1')
                            ->helperText('Optional unique code for this batch'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active batches can have new enrollments'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('training.name')
                    ->label('Training')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->counts('enrollments')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_id')
                    ->label('Training')
                    ->options(function () {
                        return \App\Models\Training::orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($training) {
                                return [$training->id => $training->name];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingBatches::route('/'),
            'create' => Pages\CreateTrainingBatch::route('/create'),
            'edit' => Pages\EditTrainingBatch::route('/{record}/edit'),
        ];
    }
}

