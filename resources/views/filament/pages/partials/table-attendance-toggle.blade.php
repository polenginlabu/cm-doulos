<div class="flex items-center gap-3">
    <span class="text-sm font-medium {{ $statusColor }}">{{ $statusText }}</span>
    <div wire:click="toggleAttendance({{ $record->id }}, 'sunday_service')" class="cursor-pointer">
        <x-filament::input.checkbox
            :checked="$isPresent"
            :attributes="new \Illuminate\View\ComponentAttributeBag(['onclick' => 'return false;', 'wire:key' => 'toggle-' . $record->id])"
        />
    </div>
</div>

