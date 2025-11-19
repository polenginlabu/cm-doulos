<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Discipleship;

class DisciplesRelationManager extends RelationManager
{
    protected static string $relationship = 'mentorships';

    protected static ?string $title = 'Disciples';

    protected static ?string $modelLabel = 'Disciple';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('disciple_id')
                    ->label('Disciple')
                    ->options(function () {
                        $query = User::query();

                        // Filter by owner's (mentor's) gender
                        if ($this->ownerRecord && $this->ownerRecord->gender) {
                            $query->where('gender', $this->ownerRecord->gender);
                        }

                        // Filter by network: only show users in the owner's network
                        if ($this->ownerRecord && method_exists($this->ownerRecord, 'getNetworkUserIds')) {
                            $networkIds = $this->ownerRecord->getNetworkUserIds();
                            $query->whereIn('id', $networkIds);
                        }

                        // Only show users not already in an active discipleship
                        $query->whereDoesntHave('discipleships', function ($q) {
                            $q->where('status', 'active');
                        });

                        return $query->orderBy('first_name')->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name];
                            })
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search): array {
                        $query = User::where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        })
                            ->orderBy('first_name')->orderBy('last_name');

                        // Filter by owner's (mentor's) gender
                        if ($this->ownerRecord && $this->ownerRecord->gender) {
                            $query->where('gender', $this->ownerRecord->gender);
                        }

                        // Filter by network: only show users in the owner's network
                        if ($this->ownerRecord && method_exists($this->ownerRecord, 'getNetworkUserIds')) {
                            $networkIds = $this->ownerRecord->getNetworkUserIds();
                            $query->whereIn('id', $networkIds);
                        }

                        // Only show users not already in an active discipleship
                        $query->whereDoesntHave('discipleships', function ($q) {
                            $q->where('status', 'active');
                        });

                        return $query->limit(50)
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value): ?string =>
                        User::find($value)?->name
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Select a member to become a disciple. Only members with the same gender as you and not already in a discipleship will be shown.')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if ($this->ownerRecord->id === $value) {
                                    $fail('A user cannot be their own disciple.');
                                }
                            };
                        },
                    ]),
                Forms\Components\DatePicker::make('started_at')
                    ->label('Started At')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'completed' => 'Completed',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('disciple.name')
            ->columns([
                Tables\Columns\TextColumn::make('disciple.name')
                    ->label('Disciple Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('disciple.email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('No email'),
                Tables\Columns\TextColumn::make('disciple.attendance_status')
                    ->label('Attendance Status')
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
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'gray' => 'completed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'completed' => 'Completed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['mentor_id'] = $this->ownerRecord->id;
                        return $data;
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
            ]);
    }

    /**
     * Show ALL disciples in this person's network (not just first-level),
     * by listing every discipleship where the disciple is in the owner's
     * network tree.
     */
    protected function getTableQuery(): Builder
    {
        $owner = $this->ownerRecord;

        // Only consider ACTIVE discipleships, same as FamilyTree and other
        // places in the app. A disciple can only have ONE active mentor.
        $query = Discipleship::query()
            ->where('status', 'active')
            ->with('disciple');

        if ($owner && method_exists($owner, 'getNetworkUserIds')) {
            $networkIds = $owner->getNetworkUserIds();

            // Remove the owner themself from the list.
            $networkIds = array_filter($networkIds, fn ($id) => $id !== $owner->id);

            if (! empty($networkIds)) {
                $query->whereIn('disciple_id', $networkIds);
            } else {
                // If there are no disciples in the network, return an empty result.
                $query->whereRaw('1 = 0');
            }
        } else {
            // Fallback: default to an empty result if we can't get network IDs.
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

