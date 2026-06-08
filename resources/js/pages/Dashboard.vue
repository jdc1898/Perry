<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, AlertTriangle, CheckCircle, Server, Wifi } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { AgentSummary, CheckName, DashboardStats } from '@/types/agents';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

defineProps<{
    stats: DashboardStats;
    agentStatuses: AgentSummary[];
    recentIssues: Array<{
        agent_name: string;
        agent_id: string;
        check: CheckName;
        status: string;
        message: string;
        checked_at: string;
    }>;
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

const checkLabels: Record<string, string> = {
    php: 'PHP', mysql: 'MySQL', reverb: 'Reverb', redis: 'Redis',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Agents</CardTitle>
                    <Server class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats.pending }} pending registration</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Online Now</CardTitle>
                    <Wifi class="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.online }}</div>
                    <p class="text-xs text-muted-foreground">of {{ stats.active }} active agents</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Healthy</CardTitle>
                    <CheckCircle class="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ agentStatuses.filter(a => a.last_report?.status === 'ok').length }}
                    </div>
                    <p class="text-xs text-muted-foreground">all checks passing</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Issues</CardTitle>
                    <AlertTriangle class="size-4 text-amber-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-amber-600">
                        {{ agentStatuses.filter(a => a.last_report && ['warning','critical'].includes(a.last_report.status)).length }}
                    </div>
                    <p class="text-xs text-muted-foreground">warning or critical</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Agent Status Grid -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Agents</CardTitle>
                            <Link href="/agents" class="text-sm text-primary hover:underline">View all</Link>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="agentStatuses.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                            No agents registered yet.
                            <Link href="/agents/create" class="text-primary hover:underline">Register your first agent →</Link>
                        </div>
                        <div v-else class="divide-y divide-border">
                            <Link
                                v-for="agent in agentStatuses"
                                :key="agent.id"
                                :href="`/agents/${agent.id}`"
                                class="flex items-center justify-between p-4 hover:bg-muted/40 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['size-2 rounded-full flex-shrink-0', agent.is_online ? 'bg-emerald-500' : 'bg-red-500']" />
                                    <div>
                                        <div class="text-sm font-medium">{{ agent.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ agent.hostname ?? 'Not yet connected' }} · {{ timeAgo(agent.last_seen_at) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div v-if="agent.last_report" class="hidden gap-1 sm:flex">
                                        <span
                                            v-for="check in agent.last_report.checks.filter(c => c.status !== 'unknown')"
                                            :key="check.name"
                                            :title="`${check.name}: ${check.message}`"
                                            :class="[
                                                'rounded px-1.5 py-0.5 text-xs font-medium uppercase tracking-wide',
                                                check.status === 'ok'       ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                check.status === 'warning'  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' :
                                                check.status === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' :
                                                                              'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                                            ]"
                                        >
                                            {{ check.name }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Issues -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Activity class="size-4 text-muted-foreground" />
                        <CardTitle>Recent Issues</CardTitle>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="recentIssues.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                        No issues detected. All clear.
                    </div>
                    <div v-else class="divide-y divide-border">
                        <Link
                            v-for="(issue, i) in recentIssues"
                            :key="i"
                            :href="`/agents/${issue.agent_id}`"
                            class="block p-4 hover:bg-muted/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <StatusBadge :status="(issue.status as any)" size="sm" />
                                        <span class="text-xs font-medium uppercase text-muted-foreground">
                                            {{ checkLabels[issue.check] ?? issue.check }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs font-medium truncate">{{ issue.agent_name }}</div>
                                    <div class="text-xs text-muted-foreground truncate">{{ issue.message }}</div>
                                </div>
                                <div class="shrink-0 text-xs text-muted-foreground">{{ timeAgo(issue.checked_at) }}</div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
