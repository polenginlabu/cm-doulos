<li>
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
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1">{{ $node['name'] }}</div>
                    @if(isset($node['is_primary_leader']) && $node['is_primary_leader'])
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                            Primary Leader
                        </span>
                    @elseif(isset($node['is_network_admin']) && $node['is_network_admin'])
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                            Network Admin
                        </span>
                    @else
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Member
                        </span>
                    @endif
                </div>
            </div>
            @if(isset($node['disciple_count']) && $node['disciple_count'] > 0)
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                    {{ $node['disciple_count'] }} {{ $node['disciple_count'] === 1 ? 'disciple' : 'disciples' }}
                </div>
            @endif
        </div>
    </div>

    @if(!empty($node['children']))
        <ul>
            @foreach($node['children'] as $child)
                @include('filament.pages.partials.tree-node', ['node' => $child, 'isRoot' => false])
            @endforeach
        </ul>
    @endif
</li>
