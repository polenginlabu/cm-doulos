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

        .tree-node {
            display: inline-block;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .tree-node:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .tree-node.root {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .tree-node.level-1 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .tree-node.level-2 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .tree-node.level-3 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .tree-node.level-4 {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .tree-node.level-5 {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .node-info {
            font-size: 0.75rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .node-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.25rem;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .dark .tree ul:before,
        .dark .tree li:before,
        .dark .tree li:after {
            border-color: #4b5563;
        }
    </style>
    @endpush
</x-filament-panels::page>

