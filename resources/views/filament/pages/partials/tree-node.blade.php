<li>
    @php
        $hasChildren = !empty($node['children'] ?? []);
        $roleLabel = $isRoot
            ? 'Senior Leader'
            : ($hasChildren ? 'Group Leader' : 'Disciple');

        $roleClasses = match ($roleLabel) {
            'Senior Leader' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
            'Group Leader' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            default => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        };
    @endphp

    <div class="tree-node {{ $isRoot ? 'root' : 'level-' . min($node['level'], 5) }}">
        <div class="flex flex-col h-full">
            <div class="flex items-start gap-3 mb-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0 flex flex-col justify-center text-left">
                    <div class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1 leading-snug">
                        {{ $node['name'] }}
                    </div>
                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full {{ $roleClasses }}">
                        {{ $roleLabel }}
                    </span>
                </div>
            </div>

            @if(isset($node['disciple_count']) && $node['disciple_count'] > 0)
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-50 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                        {{ $node['disciple_count'] }} {{ $node['disciple_count'] === 1 ? 'disciple' : 'disciples' }}
                    </span>
                </div>
            @endif

            @if($hasChildren)
                <div class="expand-button" data-node-id="{{ $node['id'] }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            @endif
        </div>
    </div>

    @if($hasChildren)
        <ul>
            @foreach($node['children'] as $child)
                @include('filament.pages.partials.tree-node', ['node' => $child, 'isRoot' => false])
            @endforeach
        </ul>
    @endif
</li>
