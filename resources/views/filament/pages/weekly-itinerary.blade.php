@php
    $weekStart = \Carbon\Carbon::parse($weekStart);
    $weekEnd = $weekStart->copy()->addDays(6);
    $canEdit = $this->canEdit();
    $activityList = $activityGroups[0]['activities'] ?? [];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Weekly Itinerary</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Organize your spiritual journey throughout the week
                </p>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-[minmax(220px,1fr)_auto_auto] sm:items-end lg:w-auto">
                @if(count($viewableUsers) > 1)
                    <div class="w-full sm:w-56">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            User
                        </label>
                        <select
                            wire:model="viewUserId"
                            class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach($viewableUsers as $userId => $userName)
                                <option value="{{ $userId }}">{{ $userName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:justify-center">
                    <button
                        type="button"
                        wire:click="previousWeek"
                        class="rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        aria-label="Previous week"
                    >
                        <x-heroicon-o-chevron-left class="h-4 w-4" />
                    </button>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                        {{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d') }}
                    </div>
                    <button
                        type="button"
                        wire:click="nextWeek"
                        class="rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        aria-label="Next week"
                    >
                        <x-heroicon-o-chevron-right class="h-4 w-4" />
                    </button>
                </div>

                {{-- <button
                    type="button"
                    wire:click="goToCurrentWeek"
                    class="h-[42px] rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    Current Week
                </button> --}}
            </div>
        </div>

        @if(!$canEdit)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Viewing only. Switch to your own profile to edit your weekly itinerary.
            </div>
        @endif

        <div class="itinerary-layout gap-6">
            <aside class="space-y-4 itinerary-sidebar">
                @if($canEdit)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Add Custom Activity</h4>
                        <form wire:submit.prevent="addFreeTextToSelectedDay" class="mt-3 space-y-2">
                            <input
                                type="text"
                                wire:model.defer="freeTextActivity"
                                placeholder="Type activity name"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('freeTextActivity')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <button
                                type="submit"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            >
                                Add Activity
                            </button>
                        </form>
                    </div>
                @endif

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Activities</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Drag to schedule</p>
                    </div>

                    <div class="activity-scroll mt-4 space-y-2 overflow-y-auto pr-1" data-activity-list>
                        @forelse($activityList as $activity)
                            <button
                                type="button"
                                data-activity-id="{{ $activity->id }}"
                                class="activity-card flex w-full items-center justify-between rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-left text-sm font-medium text-gray-800 shadow-sm transition hover:border-primary-400 dark:border-primary-900/40 dark:bg-primary-900/20 dark:text-gray-200"
                                @if($canEdit)
                                    wire:click="addActivityToSelectedDay({{ $activity->id }})"
                                @endif
                            >
                                <span>{{ $activity->name }}</span>
                                <x-heroicon-o-arrows-up-down class="h-4 w-4 text-gray-400" />
                            </button>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-200 px-2 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                No activities available yet.
                            </div>
                        @endforelse
                    </div>
                </div>

            </aside>

            <section class="min-w-0">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($weekDays as $day)
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between">
                                <button
                                    type="button"
                                    wire:click="selectDay({{ $day['index'] }})"
                                    class="flex w-full items-start justify-between text-left mb-2"
                                >
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $day['label'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $day['date'] }}
                                        </div>
                                    </div>
                                    <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full {{ $selectedDay === $day['index'] ? 'bg-primary-500' : 'bg-gray-200 dark:bg-gray-700' }}"></span>
                                </button>
                            </div>

                            <div
                                class="day-items mt-4 min-h-[140px] space-y-2 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-2 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900"
                                data-day-items
                                data-day-index="{{ $day['index'] }}"
                            >
                                @forelse($day['items'] as $item)
                                    <div
                                        class="itinerary-item flex items-center justify-between rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm text-gray-800 shadow-sm dark:border-primary-900/40 dark:bg-primary-900/20 dark:text-gray-200"
                                        data-item-id="{{ $item->id }}"
                                        wire:key="itinerary-item-{{ $item->id }}"
                                    >
                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ $item->custom_label ?? $item->activity->name ?? 'Untitled' }}
                                            </div>
                                        </div>
                                        @if($canEdit)
                                            <button
                                                type="button"
                                                wire:click="removeItem({{ $item->id }})"
                                                class="ml-2 rounded-md p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                                aria-label="Remove activity"
                                            >
                                                <x-heroicon-o-x-mark class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <div class="flex h-full items-center justify-center rounded-lg border border-dashed border-gray-200 px-2 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                        Drop activities here
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    @push('styles')
        <style>
            .drag-ghost {
                opacity: 0.5;
            }

            .drag-chosen {
                box-shadow: 0 8px 16px rgba(15, 23, 42, 0.15);
            }

            .drag-active {
                transform: rotate(1deg);
            }

            .activity-scroll {
                max-height: calc(100vh - 320px);
            }

            .itinerary-layout {
                display: grid;
                grid-template-columns: 260px minmax(0, 1fr);
                align-items: start;
            }

            .itinerary-sidebar {
                position: sticky;
                top: 1.5rem;
                align-self: start;
            }

            @media (max-width: 768px) {
                .itinerary-layout {
                    grid-template-columns: 1fr;
                }

                .itinerary-sidebar {
                    position: static;
                }

                .activity-scroll {
                    max-height: 50vh;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (() => {
                const canEdit = @js($canEdit);
                const componentId = @js($this->getId());
                const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

                const getComponent = () => window.Livewire?.find(componentId);

                const syncDay = (container) => {
                    if (!container) {
                        return;
                    }
                    const dayIndex = Number(container.dataset.dayIndex);
                    const itemIds = Array.from(container.querySelectorAll('[data-item-id]'))
                        .map((item) => item.dataset.itemId);
                    const component = getComponent();
                    if (component) {
                        component.call('syncDayItems', dayIndex, itemIds);
                    }
                };

                const initSortable = () => {
                    if (!canEdit) {
                        return;
                    }

                    document.querySelectorAll('[data-activity-list]').forEach((list) => {
                        if (list.dataset.sortableInit) {
                            return;
                        }
                        list.dataset.sortableInit = 'true';
                        new Sortable(list, {
                            group: { name: 'itinerary', pull: 'clone', put: false },
                            sort: false,
                            animation: 150,
                            draggable: '.activity-card',
                            ghostClass: 'drag-ghost',
                            chosenClass: 'drag-chosen',
                            dragClass: 'drag-active',
                            fallbackOnBody: true,
                            forceFallback: isTouch,
                        });
                    });

                    document.querySelectorAll('[data-day-items]').forEach((container) => {
                        if (container.dataset.sortableInit) {
                            return;
                        }
                        container.dataset.sortableInit = 'true';
                        new Sortable(container, {
                            group: 'itinerary',
                            animation: 150,
                            draggable: '.itinerary-item',
                            ghostClass: 'drag-ghost',
                            chosenClass: 'drag-chosen',
                            dragClass: 'drag-active',
                            onAdd: (event) => {
                                const activityId = event.item?.dataset?.activityId;
                                const itemId = event.item?.dataset?.itemId;
                                const dayIndex = Number(container.dataset.dayIndex);
                                const component = getComponent();

                                if (activityId && !itemId && component) {
                                    component.call('addActivityToDay', Number(activityId), dayIndex);
                                    event.item?.remove();
                                    return;
                                }

                                syncDay(container);
                            },
                            onUpdate: () => syncDay(container),
                            onRemove: (event) => syncDay(event.from),
                            onEnd: (event) => {
                                if (event.from && event.from !== event.to) {
                                    syncDay(event.from);
                                }
                                if (event.to && event.from !== event.to) {
                                    syncDay(event.to);
                                }
                            },
                        });
                    });
                };

                const bootstrap = () => {
                    initSortable();
                    if (window.Livewire) {
                        window.Livewire.hook('message.processed', initSortable);
                    }
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
                } else {
                    bootstrap();
                }
            })();
        </script>
    @endpush
</x-filament-panels::page>
