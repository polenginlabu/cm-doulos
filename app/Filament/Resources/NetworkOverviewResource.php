<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NetworkOverviewResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NetworkOverviewResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Network Overview';

    protected static ?string $navigationGroup = 'Network Management';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        // Only allow access if user is super admin or network admin
        return Auth::check() && (Auth::user()->is_super_admin || Auth::user()->is_network_admin);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form schema can be minimal since this is mainly for viewing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('direct_leader')
                    ->label('Direct Leader')
                    ->getStateUsing(function ($record) {
                        $discipleship = \App\Models\Discipleship::where('disciple_id', $record->id)
                            ->where('status', 'active')
                            ->with('mentor')
                            ->first();

                        if ($discipleship && $discipleship->mentor) {
                            return $discipleship->mentor->name;
                        }

                        return '—';
                    })
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('disciples_count')
                    ->label('Disciples')
                    ->getStateUsing(function ($record) {
                        return \App\Models\Discipleship::where('mentor_id', $record->id)
                            ->where('status', 'active')
                            ->count();
                    })
                    ->sortable(false),
                Tables\Columns\BadgeColumn::make('attendance_status')
                    ->label('Attendance Status')
                    ->colors([
                        'warning' => '1st',
                        'info' => '2nd',
                        'success' => '3rd',
                        'primary' => '4th',
                        'gray' => 'regular',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direct_leader')
                    ->label('Direct Leader')
                    ->options(function () {
                        return \App\Models\User::query()
                            ->whereHas('disciples', function ($query) {
                                $query->where('status', 'active');
                            })
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name];
                            })
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->whereHas('discipleships', function ($q) use ($data) {
                                $q->where('mentor_id', $data['value'])
                                  ->where('status', 'active');
                            });
                        }
                        return $query;
                    })
                    ->searchable(),
            ])
            ->defaultSort('first_name', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super admins and network admins can see all users
        // No additional filtering needed since canAccess already handles permissions
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNetworkOverviews::route('/'),
            'network-tree' => Pages\NetworkTree::route('/tree'),
        ];
    }
}

