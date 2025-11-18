<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Members</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $this->networkStats['total_members'] ?? 0 }}
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Direct Disciples</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $this->networkStats['direct_disciples'] ?? 0 }}
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Network Levels</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $this->networkStats['total_levels'] ?? 0 }}
                </div>
            </div>
        </div>

        <!-- Tree Visualization -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Discipleship Network</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Visual representation of your network structure</p>
            </div>

            <div id="tree-container" class="overflow-auto" style="min-height: 500px;">
                <div class="tree-wrapper">
                    @if(!empty($this->networkData))
                        <ul class="tree">
                            @include('filament.pages.partials.tree-node', ['node' => $this->networkData, 'isRoot' => true])
                        </ul>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">No network data available. Start by creating disciples!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .tree {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .tree ul {
            margin: 0;
            padding: 0;
            list-style: none;
            margin-left: 2rem;
            position: relative;
        }

        .tree ul:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }

        .tree li {
            margin: 0;
            padding: 0.5rem 1rem;
            position: relative;
        }

        .tree li:before {
            content: "";
            position: absolute;
            top: 0;
            left: -1rem;
            width: 1rem;
            height: 1.5rem;
            border-left: 2px solid #e5e7eb;
            border-bottom: 2px solid #e5e7eb;
        }

        .tree li:after {
            content: "";
            position: absolute;
            top: 1.5rem;
            left: -1rem;
            width: 1rem;
            height: 100%;
            border-left: 2px solid #e5e7eb;
        }

        .tree li:last-child:after {
            display: none;
        }

        .tree ul:before {
            background: #e5e7eb;
        }

        .dark .tree ul:before {
            background: #4b5563;
        }

        .dark .tree li:before,
        .dark .tree li:after {
            border-color: #4b5563;
        }

        .tree-node {
            display: inline-flex;
            flex-direction: column;
            padding: 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            min-width: 200px;
            transition: all 0.3s ease;
            cursor: default;
        }

        .tree-node > div {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .tree-node:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .tree-node.root {
            border-color: #a855f7;
        }

        .tree-node.level-1 {
            border-color: #3b82f6;
        }

        .tree-node.level-2 {
            border-color: #10b981;
        }

        .tree-node.level-3 {
            border-color: #f59e0b;
        }

        .tree-node.level-4 {
            border-color: #ef4444;
        }

        .tree-node.level-5 {
            border-color: #8b5cf6;
        }

        .dark .tree-node {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .dark .tree-node:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
        }

        .dark .tree-node.root {
            border-color: #9333ea;
        }

        .dark .tree-node.level-1 {
            border-color: #2563eb;
        }

        .dark .tree-node.level-2 {
            border-color: #059669;
        }

        .dark .tree-node.level-3 {
            border-color: #d97706;
        }

        .dark .tree-node.level-4 {
            border-color: #dc2626;
        }

        .dark .tree-node.level-5 {
            border-color: #7c3aed;
        }

    </style>
    @endpush
</x-filament-panels::page>

