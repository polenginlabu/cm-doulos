<li>
    <div class="tree-node {{ $isRoot ? 'root' : 'level-' . min($node['level'], 5) }}">
        <div class="font-semibold">{{ $node['name'] }}</div>
        <div class="node-info">
            @if($node['email'])
                <div>{{ $node['email'] }}</div>
            @endif
            @if(!empty($node['children']) && count($node['children']) > 0)
                <div class="mt-1 font-medium">Number of Disciples: {{ count($node['children']) }}</div>
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

