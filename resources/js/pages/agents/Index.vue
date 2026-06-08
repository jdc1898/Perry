<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Server } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { Agent } from '@/types/agents';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Agents', href: '/agents' },
        ],
    },
});

defineProps<{
    agents: Agent[];
}>();

function timeAgo(date: string | null): string {
    if (!date) return 'Never';
    const diff = Date.now() - new Date(date).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
}
</script>

<template>
    <Head title="Agents" />

    <div class="p-4">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Agents</h1>
                <p class="text-sm text-muted-foreground">{{ agents.length }} registered agent{{ agents.length !== 1 ? 's' : '' }}</p>
            </div>
            <Button as-child>
                <Link href="/agents/create">
                    <Plus class="mr-2 size-4" />
                    Register Agent
                </Link>
            </Button>
        </div>

        <!-- Empty state -->
        <Card v-if="agents.length === 0">
            <CardContent class="flex flex-col items-center justify-center py-16 text-center">
                <Server class="mb-4 size-12 text-muted-foreground/40" />
                <h2 class="text-lg font-medium">No agents yet</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Install the agent on a server, then register its key here.
                </p>
                <Button class="mt-4" as-child>
                    <Link href="/agents/create">Register your first agent</Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Agents Table -->
        <Card v-else>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <th class="px-4 py-3">Agent</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 hidden md:table-cell">Hostname</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Reports</th>
                            <th class="px-4 py-3">Last Seen</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="agent in agents"
                            :key="agent.id"
                            class="hover:bg-muted/30 transition-colors cursor-pointer"
                            @click="router.visit(`/agents/${agent.id}`)"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div :class="['size-2 rounded-full flex-shrink-0', agent.is_online ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600']" />
                                    <span class="font-medium">{{ agent.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="agent.status" />
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground">
                                {{ agent.hostname ?? '—' }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground">
                                {{ agent.report_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ timeAgo(agent.last_seen_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="sm" as-child @click.stop>
                                    <Link :href="`/agents/${agent.id}`">View</Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
