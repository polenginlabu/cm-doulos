<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscipleshipResource\Pages;
use App\Models\Discipleship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DiscipleshipResource extends Resource
{
    protected static ?string $model = Discipleship::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Discipleship Network';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If user is logged in, only show discipleships in their network
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user && method_exists($user, 'getNetworkUserIds')) {
                $networkIds = $user->getNetworkUserIds();
                $query->where(function ($q) use ($networkIds) {
                    $q->whereIn('mentor_id', $networkIds)
                      ->orWhereIn('disciple_id', $networkIds);
                });
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Discipleship Relationship')
                    ->schema([
                        Forms\Components\Select::make('mentor_id')
                            ->label('Mentor')
                            ->relationship(
                                'mentor',
                                'name',
                                function (Builder $query) {
                                    if (Auth::check()) {
                                        /** @var \App\Models\User $user */
                                        $user = Auth::user();
                                        if ($user && method_exists($user, 'getNetworkUserIds')) {
                                            $query->whereIn('id', $user->getNetworkUserIds());
                                        }
                                    }
                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The person who is mentoring'),
                        Forms\Components\Select::make('disciple_id')
                            ->label('Disciple')
                            ->relationship(
                                'disciple',
                                'name',
                                function (Builder $query) {
                                    if (Auth::check()) {
                                        /** @var \App\Models\User $user */
                                        $user = Auth::user();
                                        if ($user && method_exists($user, 'getNetworkUserIds')) {
                                            $query->whereIn('id', $user->getNetworkUserIds());
                                        }
                                    }
                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The person being mentored')
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($get('mentor_id') === $value) {
                                            $fail('A user cannot be their own disciple.');
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('started_at')
                            ->label('Started At')
                            ->default(now()),
                        Forms\Components\DatePicker::make('ended_at')
                            ->label('Ended At'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'completed' => 'Completed',
                            ])
                            ->default('active')
                            ->required(),
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
                Tables\Columns\TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('disciple.name')
                    ->label('Disciple')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('disciple.attendance_status')
                    ->label('Disciple Status')
                    ->badge()
                    ->colors([
                        'warning' => '1st',
                        'info' => '2nd',
                        'success' => '3rd',
                        'primary' => '4th',
                        'gray' => 'regular',
                    ]),
                Tables\Columns\TextColumn::make('started_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->date()
                    ->placeholder('Ongoing'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'gray' => 'completed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mentor_id')
                    ->label('Mentor')
                    ->relationship('mentor', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('disciple_id')
                    ->label('Disciple')
                    ->relationship('disciple', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'completed' => 'Completed',
                    ]),
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
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscipleships::route('/'),
            'create' => Pages\CreateDiscipleship::route('/create'),
            'edit' => Pages\EditDiscipleship::route('/{record}/edit'),
        ];
    }
}

