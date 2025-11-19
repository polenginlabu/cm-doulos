<?php

namespace App\Filament\Forms\Components;

use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

class UserSelect extends Select
{
    /**
     * Create a server-side searchable user select field
     *
     * @param string $name The field name
     * @param array $options Configuration options:
     *   - label: Field label
     *   - gender: Filter by gender (male/female)
     *   - primaryLeaderOnly: Only show primary leaders
     *   - excludePrimaryLeader: Exclude primary leaders from results
     *   - networkLeaderId: Filter by network leader (primary_user_id)
     *   - excludeUserId: Exclude specific user ID
     *   - excludeCurrentUser: Exclude logged-in user
     *   - excludeRecord: Exclude the record being edited
     *   - activeOnly: Only show active users (default: true)
     *   - limit: Search results limit (default: 50)
     *   - reactive: Make field reactive (default: false)
     *   - nullable: Allow null values (default: false)
     *   - helperText: Helper text to display
     *   - allowEmptySearch: Allow showing results when search is empty (default: false)
     * @return static
     */
    public static function make(string $name, array $options = []): static
    {
        $label = $options['label'] ?? 'User';
        $gender = $options['gender'] ?? null;
        $primaryLeaderOnly = $options['primaryLeaderOnly'] ?? false;
        $excludePrimaryLeader = $options['excludePrimaryLeader'] ?? false;
        $networkLeaderId = $options['networkLeaderId'] ?? null;
        $excludeUserId = $options['excludeUserId'] ?? null;
        $excludeCurrentUser = $options['excludeCurrentUser'] ?? true;
        $excludeRecord = $options['excludeRecord'] ?? null;
        $activeOnly = $options['activeOnly'] ?? true;
        $limit = $options['limit'] ?? 50;
        $reactive = $options['reactive'] ?? false;
        $nullable = $options['nullable'] ?? false;
        $helperText = $options['helperText'] ?? null;
        $allowEmptySearch = $options['allowEmptySearch'] ?? false;

        $component = parent::make($name)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(function (string $search, $get, $record) use (
                $gender,
                $primaryLeaderOnly,
                $excludePrimaryLeader,
                $networkLeaderId,
                $excludeUserId,
                $excludeCurrentUser,
                $excludeRecord,
                $activeOnly,
                $limit,
                $allowEmptySearch
            ) {
                $query = User::query();

                // Apply active filter
                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                // Apply primary leader filter
                if ($primaryLeaderOnly) {
                    $query->where('is_primary_leader', true);
                }

                // Exclude primary leaders
                if ($excludePrimaryLeader) {
                    $query->where('is_primary_leader', false);
                }

                // Apply network leader filter (filter cell leaders by their network leader)
                $networkLeaderValue = $networkLeaderId;
                if (is_callable($networkLeaderId)) {
                    $networkLeaderValue = $networkLeaderId($get);
                } elseif ($networkLeaderId === null && $get) {
                    // Try to get network leader from form state (common field name: primary_user_id)
                    $networkLeaderValue = $get('primary_user_id');
                }

                if ($networkLeaderValue) {
                    $query->where('primary_user_id', $networkLeaderValue);
                }

                // Apply search filter
                // If allowEmptySearch is false, require search term for server-side autocomplete
                // This prevents loading all users when the field is opened
                if (!empty(trim($search))) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                } elseif (!$allowEmptySearch) {
                    // If search is empty and we don't allow empty search, return empty results
                    return [];
                }

                // Apply gender filter (can be from form state or static)
                $genderValue = $gender;
                if (is_callable($gender)) {
                    $genderValue = $gender($get);
                } elseif ($gender === null && $get) {
                    // Try to get gender from form state
                    $genderValue = $get('gender');
                }

                if ($genderValue) {
                    $query->where('gender', $genderValue);
                }

                // Apply network filtering (filter by authenticated user's network)
                if (Auth::check()) {
                    $authUser = Auth::user();
                    // Super admins and network admins can see all users
                    if (!$authUser->is_super_admin && !$authUser->is_network_admin) {
                        if (method_exists($authUser, 'getNetworkUserIds')) {
                            /** @phpstan-ignore-next-line */
                            $networkIds = $authUser->getNetworkUserIds(); // @phpstan-ignore-line
                            if (!empty($networkIds)) {
                                $query->whereIn('id', $networkIds);
                            }
                        }
                    }
                }

                // Exclude specific user ID
                if ($excludeUserId) {
                    $excludeId = is_callable($excludeUserId) ? $excludeUserId($get, $record) : $excludeUserId;
                    if ($excludeId) {
                        $query->where('id', '!=', $excludeId);
                    }
                }

                // Exclude current user
                if ($excludeCurrentUser && Auth::check()) {
                    $query->where('id', '!=', Auth::id());
                }

                // Exclude record being edited
                if ($excludeRecord !== false) {
                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    } elseif (is_callable($excludeRecord)) {
                        $excludeId = $excludeRecord($get);
                        if ($excludeId) {
                            $query->where('id', '!=', $excludeId);
                        }
                    }
                }

                return $query
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->limit($limit)
                    ->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => $user->name];
                    })
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                if (!$value) {
                    return null;
                }
                return User::find($value)?->name;
            });

        // Only load selected option, not all options
        // For network leaders, we can optionally preload all options when gender is selected
        $fieldName = $name;
        $preloadOptions = $options['preloadOptions'] ?? false;

        // If allowEmptySearch is true, also use options() to show initial results
        if ($allowEmptySearch) {
            $component->options(function ($get, $record) use (
                $fieldName,
                $gender,
                $primaryLeaderOnly,
                $excludePrimaryLeader,
                $activeOnly,
                $excludeCurrentUser,
                $excludeRecord,
                $limit
            ) {
                $query = User::query();

                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                if ($primaryLeaderOnly) {
                    $query->where('is_primary_leader', true);
                }

                if ($excludePrimaryLeader) {
                    $query->where('is_primary_leader', false);
                }

                // Apply network filtering (filter by authenticated user's network)
                if (Auth::check()) {
                    $authUser = Auth::user();
                    // Super admins and network admins can see all users
                    if (!$authUser->is_super_admin && !$authUser->is_network_admin) {
                        if (method_exists($authUser, 'getNetworkUserIds')) {
                            /** @phpstan-ignore-next-line */
                            $networkIds = $authUser->getNetworkUserIds(); // @phpstan-ignore-line
                            if (!empty($networkIds)) {
                                $query->whereIn('id', $networkIds);
                            }
                        }
                    }
                }

                // Apply gender filter
                $genderValue = $gender;
                if (is_callable($gender)) {
                    $genderValue = $gender($get);
                } elseif ($gender === null && $get) {
                    $genderValue = $get('gender');
                }

                if ($genderValue) {
                    $query->where('gender', $genderValue);
                }

                // Exclude current user
                if ($excludeCurrentUser && Auth::check()) {
                    $query->where('id', '!=', Auth::id());
                }

                // Exclude record
                if ($excludeRecord !== false) {
                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    } elseif (is_callable($excludeRecord)) {
                        $excludeId = $excludeRecord($get);
                        if ($excludeId) {
                            $query->where('id', '!=', $excludeId);
                        }
                    }
                }

                return $query
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->limit($limit)
                    ->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => $user->name];
                    })
                    ->toArray();
            });
        } elseif ($preloadOptions) {
            // Preload all options when conditions are met (e.g., gender is selected)
            $preloadCondition = $options['preloadCondition'] ?? null;
            $component->options(function ($get, $record) use ($fieldName, $preloadCondition, $gender, $primaryLeaderOnly, $excludePrimaryLeader, $activeOnly, $excludeCurrentUser, $excludeRecord) {
                // Check if we should preload
                $shouldPreload = true;
                if ($preloadCondition && is_callable($preloadCondition)) {
                    $shouldPreload = $preloadCondition($get);
                }

                if (!$shouldPreload) {
                    // Only return selected value if editing
                    if ($record && $record->exists) {
                        $value = $record->getAttribute($fieldName);
                        if ($value) {
                            $user = User::find($value);
                            if ($user) {
                                return [$user->id => $user->name];
                            }
                        }
                    }
                    return [];
                }

                // Preload all matching options
                $query = User::query();

                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                if ($primaryLeaderOnly) {
                    $query->where('is_primary_leader', true);
                }

                if ($excludePrimaryLeader) {
                    $query->where('is_primary_leader', false);
                }

                // Apply gender filter
                $genderValue = $gender;
                if (is_callable($gender)) {
                    $genderValue = $gender($get);
                } elseif ($gender === null && $get) {
                    $genderValue = $get('gender');
                }

                if ($genderValue) {
                    $query->where('gender', $genderValue);
                }

                // Exclude current user
                if ($excludeCurrentUser && Auth::check()) {
                    $query->where('id', '!=', Auth::id());
                }

                // Exclude record
                if ($excludeRecord !== false) {
                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    } elseif (is_callable($excludeRecord)) {
                        $excludeId = $excludeRecord($get);
                        if ($excludeId) {
                            $query->where('id', '!=', $excludeId);
                        }
                    }
                }

                return $query
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => $user->name];
                    })
                    ->toArray();
            });
        } else {
            // Default: Only load selected option
            $component->options(function ($get, $record) use ($fieldName) {
                // Only return the selected value if editing
                if ($record && $record->exists) {
                    $value = $record->getAttribute($fieldName);
                    if ($value) {
                        $user = User::find($value);
                        if ($user) {
                            return [$user->id => $user->name];
                        }
                    }
                }
                return [];
            });
        }

        if ($reactive) {
            $component->reactive();
        }

        if ($nullable) {
            $component->nullable();
        }

        if ($helperText) {
            $component->helperText($helperText);
        }

        return $component;
    }

    /**
     * Create a Cell Leader select field
     */
    public static function cellLeader(string $name = 'mentor_id', array $options = []): static
    {
        return static::make($name, array_merge([
            'label' => 'Cell Leader',
            'helperText' => 'Select your mentor (cell leader). Only users with the same gender as you are shown. A disciple can only have one active mentor.',
            'nullable' => true,
            'reactive' => true,
        ], $options));
    }

    /**
     * Create a Network Leader select field
     */
    public static function networkLeader(string $name = 'primary_user_id', array $options = []): static
    {
        return static::make($name, array_merge([
            'label' => 'Network Leader',
            'primaryLeaderOnly' => true,
            'nullable' => true,
        ], $options));
    }
}

