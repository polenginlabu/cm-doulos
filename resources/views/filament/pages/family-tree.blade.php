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
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Visual family tree of your discipleship network, grouped by leaders and disciples.
                </p>
            </div>

            <div id="tree-container" class="overflow-x-auto overflow-y-hidden w-full" style="min-height: 500px;">
                <div class="tree-wrapper inline-block">
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
        /* --- Org-chart style connectors (matching sample layout) --- */
        .tree,
        .tree ul {
            padding-top: 1.5rem;
            position: relative;
            list-style: none;
            margin: 0;
        }

        .tree {
            display: inline-block;
            padding-top: 1.5rem;
            position: relative;
            list-style: none;
            margin: 0;
        }

        .tree ul {
            padding-top: 24px; /* gap between parent node and children bar */
            position: relative;
            display: flex;
            justify-content: center;
        }

        .tree li {
            list-style: none;
            text-align: center;
            position: relative;
            padding: 24px 24px 0 24px; /* gap between bar and node */
        }

        /* Horizontal connectors between siblings + short vertical down from bar */
        .tree li::before,
        .tree li::after {
            content: "";
            position: absolute;
            top: 0;
            border-top: 1px solid #e5e7eb;
            width: 50%;
            height: 20px;
        }

        .tree li::before {
            right: 50%;
            border-right: 1px solid #e5e7eb;
        }

        .tree li::after {
            left: 50%;
            border-left: 1px solid #e5e7eb;
        }

        /* Remove extra horizontal for outer siblings */
        .tree li:first-child::before {
            border-top: none;
            border-right: none;
        }

        .tree li:last-child::after {
            border-top: none;
            border-left: none;
        }

        /* When only one child, no horizontal line */
        .tree li:only-child::before,
        .tree li:only-child::after {
            border-top: none;
            border-left: none;
            border-right: none;
        }

        /* Vertical connector from parent node down to the horizontal bar */
        .tree ul ul::before {
            content: "";
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 1px solid #e5e7eb;
            width: 0;
            height: 24px;
            transform: translateX(-50%);
        }

        .dark .tree li::before,
        .dark .tree li::after,
        .dark .tree ul ul::before {
            border-color: #4b5563;
        }

        /* --- Node cards --- */
        .tree-node {
            display: inline-flex;
            flex-direction: column;
            padding: 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08);
            min-width: 220px;
            margin: 0 1.5rem; /* visual spacing between cards without breaking line */
            transition: all 0.2s ease;
            cursor: default;
            position: relative;
        }

        .tree-node > div {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .tree-node:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.1);
        }

        .tree-node.root {
            border-color: #6366f1;
            background: linear-gradient(to bottom, #eef2ff, #ffffff);
        }

        .tree-node.level-1 {
            border-color: #38bdf8;
        }

        .tree-node.level-2 {
            border-color: #22c55e;
        }

        .tree-node.level-3 {
            border-color: #eab308;
        }

        .tree-node.level-4,
        .tree-node.level-5 {
            border-color: #a855f7;
        }

        .dark .tree-node {
            background: #0f172a;
            border-color: #1f2937;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
        }

        .dark .tree-node.root {
            border-color: #6366f1;
            background: linear-gradient(to bottom, #111827, #020617);
        }

        /* Expand / collapse button (similar to Network Overview) */
        .expand-button {
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .dark .expand-button {
            background: #0f172a;
            border-color: #1f2937;
        }

        .expand-button:hover {
            background: #f3f4f6;
        }

        .dark .expand-button:hover {
            background: #1f2937;
        }

        .expand-button.expanded {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                const button = e.target.closest('.expand-button');
                if (!button) return;

                const li = button.closest('li');
                if (!li) return;

                const ul = li.querySelector('ul');
                if (!ul) {
                    // No children – hide button
                    button.style.display = 'none';
                    return;
                }

                const isExpanded = button.classList.contains('expanded');

                if (isExpanded) {
                    ul.style.display = 'none';
                    button.classList.remove('expanded');
                    button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>';
                } else {
                    ul.style.display = 'flex';
                    button.classList.add('expanded');
                    button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>';
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>

