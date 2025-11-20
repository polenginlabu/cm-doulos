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

            <div id="tree-container" class="w-full overflow-x-auto">
                <div
                    id="orgchart"
                    class="min-h-[600px] w-full"
                    role="presentation"
                ></div>
            </div>

            <div
                id="tree-empty-state"
                class="{{ empty($this->networkData) ? 'block' : 'hidden' }} text-center py-12"
            >
                <p class="text-gray-500 dark:text-gray-400">
                    No network data available. Start by creating disciples!
                </p>
            </div>
        </div>
    </div>

    @push('styles')
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.8.0/css/jquery.orgchart.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <style>
        #tree-container {
            min-height: 500px;
        }

        #orgchart {
            min-height: 600px;
        }

        .orgchart {
            background-color: transparent;
        }

        .orgchart .node {
            width: 240px;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        .orgchart .node .node-card {
            background: #fff;
            border-radius: 1.25rem;
            border: 2px solid #e2e8f0;
            padding: 0.8rem 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }

        .orgchart .node .node-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .orgchart .node .avatar {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            border: 2px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
            color: #475569;
        }

        .orgchart .node .name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.15rem;
            text-align: left;
        }

        .orgchart .node .role {
            font-size: 0.85rem;
            color: #475569;
            text-align: left;
        }

        .orgchart .node .meta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: #475569;
            background: #f8fafc;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
        }

        .orgchart .node .meta-dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: currentColor;
        }

        .orgchart .lines .topLine,
        .orgchart .lines .rightLine,
        .orgchart .lines .leftLine,
        .orgchart .lines .downLine {
            border-color: #e5e7eb !important;
            background-color: #e5e7eb !important;
        }

        .dark .orgchart .lines .topLine,
        .dark .orgchart .lines .rightLine,
        .dark .orgchart .lines .leftLine,
        .dark .orgchart .lines .downLine {
            border-color: #475569 !important;
            background-color: #475569 !important;
        }

        .orgchart .node .node-card.border-root {
            border-color: #7367ff;
        }

        .orgchart .node .node-card.border-leader {
            border-color: #38bdf8;
        }

        .orgchart .node .node-card.border-disciple {
            border-color: #22c55e;
        }

        .orgchart .lines .topLine,
        .orgchart .lines .rightLine,
        .orgchart .lines .leftLine,
        .orgchart .lines .downLine {
            border-color: #e5e7eb;
            background-color: #e5e7eb;
        }

        .dark .orgchart .node .node-card {
            background-color: #0f172a;
            border-color: #1f2937;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.5);
        }

        .dark .orgchart .node .name {
            color: #f1f5f9;
        }

        .dark .orgchart .node .role {
            color: #cbd5f5;
        }

        .dark .orgchart .node .meta {
            color: #cbd5f5;
            background: rgba(148, 163, 184, 0.15);
        }

        .orgchart .hierarchy::before{
            border-top: 2px solid rgba(148, 163, 184, 0.50) !important;
        }

        .orgchart>ul>li>ul li>.node::before, .orgchart .node:not(:only-child)::after {
            background-color: rgba(148, 163, 184, 0.50);
        }


    </style>
    @endpush

    @push('scripts')
    <script
        src="https://code.jquery.com/jquery-3.7.1.min.js"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.8.0/js/jquery.orgchart.min.js"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <script>
        (() => {
            const centerViewport = () => {
                const wrapper = document.getElementById('tree-container');
                if (!wrapper) {
                    return;
                }

                requestAnimationFrame(() => {
                    const adjustment = Math.max(0, (wrapper.scrollWidth - wrapper.clientWidth) / 2);
                    wrapper.scrollLeft = adjustment;
                });
            };

            const computeRole = (parentId, hasChildren) => {
                if (parentId === null) {
                    return 'Senior Leader';
                }

                return hasChildren ? 'Group Leader' : 'Disciple';
            };

            const formatDisciples = (count) => {
                if (!count) {
                    return 'No disciples yet';
                }

                return `${count} ${count === 1 ? 'disciple' : 'disciples'}`;
            };

            const roleClass = (role) => {
                if (role === 'Senior Leader') {
                    return 'root';
                }

                if (role === 'Group Leader') {
                    return 'leader';
                }

                return 'disciple';
            };

            const levelLabel = (level) => {
                if (level === 0) {
                    return 'Leader';
                }

                const multiplier = Math.pow(12, level);
                return `${multiplier.toLocaleString('en-US')} disciples`;
            };

            const normalizeNode = (node, parentId = null, level = 0) => {
                if (!node || !node.id) {
                    return null;
                }

                const children = Array.isArray(node.children)
                    ? node.children
                        .map((child) => normalizeNode(child, node.id, level + 1))
                        .filter(Boolean)
                    : [];

                const role = computeRole(parentId, children.length > 0);

                return {
                    id: node.id,
                    name: node.name ?? 'Unknown',
                    children,
                    role,
                    discipleLabel: formatDisciples(Number(node.disciple_count ?? 0)),
                    roleClass: roleClass(role),
                    levelLabel: levelLabel(level),
                };
            };


            const nodeTemplate = (data) => `
                <div class="node-card border-${data.roleClass}">
                    <div class="node-header">
                        <div class="avatar">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="name">${data.name}</div>
                            <div class="role">${data.levelLabel}</div>
                        </div>
                    </div>
                    <div class="meta">
                        <span class="meta-dot"></span>
                        ${data.discipleLabel}
                    </div>
                </div>
            `;

            const toggleEmptyState = (isEmpty) => {
                const emptyState = document.getElementById('tree-empty-state');
                if (!emptyState) {
                    return;
                }
                emptyState.classList.toggle('hidden', !isEmpty);
                emptyState.classList.toggle('block', isEmpty);
            };

            const renderChart = (data) => {
                const $container = window.jQuery?.('#orgchart');

                if (!$container || !$container.length) {
                    return;
                }

                if (!data || !Object.keys(data).length) {
                    $container.empty();
                    toggleEmptyState(true);
                    return;
                }

                const normalized = normalizeNode(data);

                if (!normalized) {
                    $container.empty();
                    toggleEmptyState(true);
                    return;
                }

                toggleEmptyState(false);

                $container.empty();

                $container.orgchart({
                    data: normalized,
                    pan: false,
                    zoom: false,
                    toggleSiblingsResp: true,
                    nodeTemplate,
                    createNode: ($node, data) => {
                        $node.find('.node-card').addClass(`role-${data.roleClass}`);
                    },
                });

                centerViewport();
            };

            const bootstrap = () => {
                renderChart(@js($this->networkData));

                window.addEventListener('family-tree-data', (event) => {
                    renderChart(event.detail?.data ?? null);
                });

                window.addEventListener('tree-refreshed', centerViewport);
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

