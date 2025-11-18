<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Tree Visualization -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Network Overview</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Starting from the primary leader</p>
            </div>

            <div id="tree-container" class="overflow-auto" style="min-height: 500px;">
                <div class="tree-wrapper">
                    @php
                        $primaryUser = $this->getPrimaryUser();
                    @endphp
                    @if($primaryUser)
                        <ul class="tree">
                            @include('filament.resources.network-overview-resource.partials.tree-node', [
                                'node' => [
                                    'id' => $primaryUser->id,
                                    'name' => $primaryUser->name,
                                    'email' => $primaryUser->email,
                                    'attendance_status' => $primaryUser->attendance_status,
                                    'total_attendances' => $primaryUser->total_attendances,
                                    'is_primary_leader' => $primaryUser->is_primary_leader,
                                    'is_network_admin' => $primaryUser->is_network_admin,
                                    'is_equipping_admin' => $primaryUser->is_equipping_admin,
                                    'disciple_count' => $primaryUser->disciple_count ?? 0,
                                    'has_children' => $primaryUser->has_children ?? false,
                                ],
                                'isRoot' => true,
                                'expanded' => false
                            ])
                        </ul>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">No primary leader found. Please set a primary leader first.</p>
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

        .dark .tree ul:before {
            background: #374151;
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

        .dark .tree li:before {
            border-left-color: #374151;
            border-bottom-color: #374151;
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

        .dark .tree li:after {
            border-left-color: #374151;
        }

        .tree li:last-child:after {
            display: none;
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .tree-node {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .dark .tree-node:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
        }

        .tree-node.primary-leader {
            border-color: #9333ea;
        }

        .dark .tree-node.primary-leader {
            border-color: #9333ea;
        }

        .tree-node.network-admin {
            border-color: #3b82f6;
        }

        .dark .tree-node.network-admin {
            border-color: #2563eb;
        }

        .tree-node.equipping-admin {
            border-color: #10b981;
        }

        .dark .tree-node.equipping-admin {
            border-color: #059669;
        }

        .tree-node.member {
            border-color: #6b7280;
        }

        .dark .tree-node.member {
            border-color: #6b7280;
        }

        .expand-button {
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .dark .expand-button {
            background: #1f2937;
            border-color: #374151;
        }

        .expand-button:hover {
            background: #f3f4f6;
        }

        .dark .expand-button:hover {
            background: #374151;
        }

        .expand-button.expanded {
            background: #9333ea;
            border-color: #9333ea;
            color: white;
        }

        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate initial disciple counts
            calculateDiscipleCounts();

            // Handle expand/collapse
            document.addEventListener('click', function(e) {
                if (e.target.closest('.expand-button')) {
                    const button = e.target.closest('.expand-button');
                    const nodeId = button.dataset.nodeId;
                    const li = button.closest('li');
                    const ul = li.querySelector('ul');

                    if (button.classList.contains('expanded')) {
                        // Collapse
                        if (ul) {
                            ul.remove();
                        }
                        button.classList.remove('expanded');
                        button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>';
                    } else {
                        // Expand - load children
                        if (!ul) {
                            loadChildren(nodeId, li);
                        } else {
                            ul.style.display = 'block';
                        }
                        button.classList.add('expanded');
                        button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>';
                    }
                }
            });

            function loadChildren(userId, parentLi) {
                const button = parentLi.querySelector('.expand-button');
                button.classList.add('loading');
                button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

                fetch(`{{ route('filament.cells.network-overview.get-disciples', ['userId' => '__USER_ID__']) }}`.replace('__USER_ID__', userId))
                    .then(response => response.json())
                    .then(data => {
                        button.classList.remove('loading');

                        if (data.length === 0) {
                            button.style.display = 'none';
                            return;
                        }

                        const ul = document.createElement('ul');
                        data.forEach(disciple => {
                            const li = document.createElement('li');
                            li.innerHTML = getNodeHTML(disciple);
                            ul.appendChild(li);
                        });

                        parentLi.appendChild(ul);
                        button.classList.add('expanded');
                        button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>';
                    })
                    .catch(error => {
                        console.error('Error loading disciples:', error);
                        button.classList.remove('loading');
                    });
            }

            function getNodeHTML(node) {
                const borderColor = node.is_primary_leader ? 'primary-leader' :
                                   node.is_network_admin ? 'network-admin' :
                                   node.is_equipping_admin ? 'equipping-admin' : 'member';

                const roleBadge = node.is_primary_leader ?
                    '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">Primary Leader</span>' :
                    node.is_network_admin ?
                    '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">Network Admin</span>' :
                    node.is_equipping_admin ?
                    '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Equipping Admin</span>' :
                    '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Member</span>';

                const expandButton = node.has_children ?
                    `<div class="expand-button" data-node-id="${node.id}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>` : '';

                return `
                    <div class="tree-node ${borderColor}">
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
                                    <div class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1">${node.name}</div>
                                    ${roleBadge}
                                </div>
                            </div>
                            ${node.disciple_count > 0 ? `<div class="text-xs text-gray-600 dark:text-gray-400 mb-3">${node.disciple_count} ${node.disciple_count === 1 ? 'disciple' : 'disciples'}</div>` : ''}
                            ${expandButton}
                        </div>
                    </div>
                `;
            }

            function calculateDiscipleCounts() {
                // This will be calculated server-side, but we can update the display
                const nodes = document.querySelectorAll('.tree-node');
                nodes.forEach(node => {
                    // Count is already set from server
                });
            }
        });
    </script>
    @endpush
</x-filament-panels::page>

