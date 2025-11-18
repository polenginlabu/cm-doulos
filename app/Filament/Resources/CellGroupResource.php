<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CellGroupResource\Pages;
use App\Models\CellGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CellGroupResource extends Resource
{
    protected static ?string $model = CellGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Cell Groups';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cell Group Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique code for this cell group'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Leadership & Structure')
                    ->schema([
                        Forms\Components\Select::make('leader_id')
                            ->label('Leader')
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
                            ->preload(),
                        Forms\Components\Select::make('parent_cell_group_id')
                            ->label('Parent Cell Group')
                            ->relationship('parentCellGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Select parent cell group for hierarchical structure'),
                        Forms\Components\Select::make('level')
                            ->options([
                                'cell' => 'Cell',
                                'network' => 'Network',
                                'zone' => 'Zone',
                                'region' => 'Region',
                            ])
                            ->default('cell')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('Leader')
                    ->sortable()
                    ->placeholder('No leader'),
                Tables\Columns\TextColumn::make('parentCellGroup.name')
                    ->label('Parent Group')
                    ->sortable()
                    ->placeholder('No parent'),
                Tables\Columns\BadgeColumn::make('level')
                    ->colors([
                        'primary' => 'cell',
                        'success' => 'network',
                        'warning' => 'zone',
                        'info' => 'region',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'cell' => 'Cell',
                        'network' => 'Network',
                        'zone' => 'Zone',
                        'region' => 'Region',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCellGroups::route('/'),
            'create' => Pages\CreateCellGroup::route('/create'),
            'view' => Pages\ViewCellGroup::route('/{record}'),
            'edit' => Pages\EditCellGroup::route('/{record}/edit'),
        ];
    }
}

