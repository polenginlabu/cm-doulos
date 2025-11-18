<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Attendances';

    protected static ?string $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Member')
                            ->options(function () {
                                return \App\Models\User::query()
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\User::query()
                                    ->where(function ($q) use ($search) {
                                        $q->where('first_name', 'like', "%{$search}%")
                                          ->orWhere('last_name', 'like', "%{$search}%");
                                    })
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required(),
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
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cellGroup.name')
                    ->label('Cell Group')
                    ->sortable()
                    ->placeholder('No cell group'),
                Tables\Columns\BadgeColumn::make('attendance_type')
                    ->colors([
                        'purple' => 'sunday_service',
                        'blue' => 'crossover',
                        'green' => 'wildsons',
                        'primary' => 'cell_group',
                        'success' => 'service',
                        'info' => 'event',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'sunday_service' => 'Sunday Service',
                        'crossover' => 'CrossOver',
                        'wildsons' => 'WildSons',
                        'cell_group' => 'Cell Group',
                        'service' => 'Service',
                        'event' => 'Event',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Present')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Member')
                    ->options(function () {
                        return \App\Models\User::query()
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Tables\Filters\SelectFilter::make('cell_group_id')
                    ->label('Cell Group')
                    ->relationship('cellGroup', 'name'),
                Tables\Filters\SelectFilter::make('attendance_type')
                    ->options([
                        'sunday_service' => 'Sunday Service',
                        'crossover' => 'CrossOver',
                        'wildsons' => 'WildSons',
                        'cell_group' => 'Cell Group',
                        'service' => 'Service',
                        'event' => 'Event',
                    ]),
                Tables\Filters\TernaryFilter::make('is_present')
                    ->label('Present'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}

