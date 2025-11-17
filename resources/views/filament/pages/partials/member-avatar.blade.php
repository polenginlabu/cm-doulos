<div class="flex items-center gap-3">
    <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
        {{ $initials }}
    </div>
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $name }}</p>
        @if($email)
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $email }}</p>
        @endif
    </div>
</div>

