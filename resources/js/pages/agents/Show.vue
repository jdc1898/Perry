<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Settings, Trash2, XCircle } from '@lucide/vue';
import { ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';
import type { Agent, AgentReport } from '@/types/agents';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Agents', href: '/agents' },
        ],
    },
});

const props = defineProps<{
    agent: Agent;
    reports: AgentReport[];
}>();

const activeTab = ref<'reports' | 'config'>('reports');
const expandedReport = ref<number | null>(null);

const configForm = useForm({
    name:                 props.agent.name,
    check_interval:       props.agent.check_interval,
    config_poll_interval: props.agent.config_poll_interval,
    php_config:           { ...props.agent.php_config },
    mysql_config:         { ...props.agent.mysql_config },
    reverb_config:        { ...props.agent.reverb_config },
    redis_config:         { ...props.agent.redis_config },
});

function saveConfig() {
    configForm.put(`/agents/${props.agent.id}`);
}

function revokeAgent() {
    if (confirm(`Revoke agent "${props.agent.name}"? It will stop receiving config and be blocked immediately.`)) {
        router.post(`/agents/${props.agent.id}/revoke`);
    }
}

function deleteAgent() {
    if (confirm(`Delete agent "${props.agent.name}" and all its reports? This cannot be undone.`)) {
        router.delete(`/agents/${props.agent.id}`);
    }
}

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

function formatDate(date: string): string {
    return new Date(date).toLocaleString();
}

const statusOrder = ['critical', 'warning', 'unknown', 'ok'];
function sortedChecks(checks: AgentReport['checks']) {
    return [...checks].sort((a, b) => statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status));
}
</script>

<template>
    <Head :title="agent.name" />

    <div class="p-4">

        <!-- Agent Header -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold">{{ agent.name }}</h1>
                    <StatusBadge :status="agent.status" />
                    <StatusBadge :status="agent.is_online ? 'online' : 'offline'" size="sm" />
                </div>
                <div class="mt-1 text-sm text-muted-foreground">
                    {{ agent.hostname ?? 'Not yet connected' }}
                    · Last seen {{ timeAgo(agent.last_seen_at) }}
                    · Config v{{ agent.config_version }}
                </div>
                <div class="mt-1 font-mono text-xs text-muted-foreground/60 break-all">
                    ID: {{ agent.id }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    v-if="agent.status !== 'revoked'"
                    variant="outline"
                    size="sm"
                    class="text-amber-600 hover:text-amber-700"
                    @click="revokeAgent"
                >
                    <XCircle class="mr-1.5 size-4" />
                    Revoke
                </Button>
                <Button variant="outline" size="sm" class="text-destructive hover:text-destructive" @click="deleteAgent">
                    <Trash2 class="mr-1.5 size-4" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-4 flex gap-1 border-b border-border">
            <button
                :class="['px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                    activeTab === 'reports'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground']"
                @click="activeTab = 'reports'"
            >
                Reports
            </button>
            <button
                :class="['px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px flex items-center gap-1.5',
                    activeTab === 'config'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground']"
                @click="activeTab = 'config'"
            >
                <Settings class="size-3.5" />
                Configuration
            </button>
        </div>

        <!-- Reports Tab -->
        <div v-if="activeTab === 'reports'">
            <div v-if="reports.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                No reports yet. The agent will send its first report after it connects and is registered.
            </div>
            <div v-else class="space-y-2">
                <Card
                    v-for="report in reports"
                    :key="report.id"
                    class="overflow-hidden"
                >
                    <!-- Report header (always visible) -->
                    <button
                        class="w-full flex items-center justify-between p-4 text-left hover:bg-muted/30 transition-colors"
                        @click="expandedReport = expandedReport === report.id ? null : report.id"
                    >
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="report.status" />
                            <span class="text-sm text-muted-foreground">{{ formatDate(report.reported_at) }}</span>
                            <!-- Mini check chips -->
                            <div class="hidden gap-1 sm:flex">
                                <span
                                    v-for="check in report.checks"
                                    :key="check.name"
                                    :class="[
                                        'rounded px-1.5 py-0.5 text-xs font-medium uppercase',
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
                        <ChevronDown v-if="expandedReport !== report.id" class="size-4 text-muted-foreground" />
                        <ChevronUp v-else class="size-4 text-muted-foreground" />
                    </button>

                    <!-- Expanded check details -->
                    <div v-if="expandedReport === report.id" class="border-t border-border">
                        <div
                            v-for="check in sortedChecks(report.checks)"
                            :key="check.name"
                            class="flex items-start gap-4 px-4 py-3 border-b border-border/50 last:border-0"
                        >
                            <div class="w-20 shrink-0">
                                <StatusBadge :status="check.status" size="sm" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium capitalize">{{ check.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ check.message }}</div>
                                <!-- Key metrics -->
                                <div v-if="check.metrics && Object.keys(check.metrics).length" class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                    <span
                                        v-for="(val, key) in check.metrics"
                                        :key="key"
                                        class="text-xs text-muted-foreground"
                                    >
                                        <span class="font-medium text-foreground/70">{{ key }}</span>: {{ val }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Configuration Tab -->
        <div v-if="activeTab === 'config'">
            <form @submit.prevent="saveConfig" class="space-y-6 max-w-2xl">

                <!-- General -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">General</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="name">Agent Name</Label>
                            <Input id="name" v-model="configForm.name" required />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label for="check_interval">Check Interval (seconds)</Label>
                                <Input id="check_interval" v-model.number="configForm.check_interval" type="number" min="10" max="3600" />
                                <p class="text-xs text-muted-foreground">How often the agent runs checks</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="config_poll_interval">Config Poll Interval (seconds)</Label>
                                <Input id="config_poll_interval" v-model.number="configForm.config_poll_interval" type="number" min="60" max="3600" />
                                <p class="text-xs text-muted-foreground">How often the agent fetches updated config</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- PHP -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">PHP-FPM</CardTitle>
                                <CardDescription>Monitor PHP-FPM process and pool status</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="php_enabled" v-model="configForm.php_config.enabled" />
                                <Label for="php_enabled" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="configForm.php_config.enabled" class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>FPM Socket Path</Label>
                            <Input v-model="configForm.php_config.fpm_socket" placeholder="/run/php/php8.2-fpm.sock" />
                            <p class="text-xs text-muted-foreground">Leave blank to skip socket check</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Status Page URL</Label>
                            <Input v-model="configForm.php_config.status_url" placeholder="http://127.0.0.1/fpm-status" />
                            <p class="text-xs text-muted-foreground">Optional — requires FPM status page configured in nginx/apache</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- MySQL -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">MySQL</CardTitle>
                                <CardDescription>Monitor MySQL connectivity and health</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="mysql_enabled" v-model="configForm.mysql_config.enabled" />
                                <Label for="mysql_enabled" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="configForm.mysql_config.enabled" class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>DSN</Label>
                            <Input v-model="configForm.mysql_config.dsn" placeholder="monitor:password@tcp(127.0.0.1:3306)/" />
                            <p class="text-xs text-muted-foreground">Go MySQL DSN format. Use a read-only monitoring user.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox id="check_replication" v-model="configForm.mysql_config.check_replication" />
                            <Label for="check_replication" class="cursor-pointer">Check replication status</Label>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reverb -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Laravel Reverb</CardTitle>
                                <CardDescription>Monitor the Reverb WebSocket server</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="reverb_enabled" v-model="configForm.reverb_config.enabled" />
                                <Label for="reverb_enabled" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="configForm.reverb_config.enabled" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2 space-y-1.5">
                                <Label>Host</Label>
                                <Input v-model="configForm.reverb_config.host" placeholder="127.0.0.1" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Port</Label>
                                <Input v-model.number="configForm.reverb_config.port" type="number" placeholder="8080" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Redis -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Redis</CardTitle>
                                <CardDescription>Monitor Redis connectivity and memory usage</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="redis_enabled" v-model="configForm.redis_config.enabled" />
                                <Label for="redis_enabled" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="configForm.redis_config.enabled" class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>Address</Label>
                            <Input v-model="configForm.redis_config.addr" placeholder="127.0.0.1:6379" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Password</Label>
                                <Input v-model="configForm.redis_config.password" type="password" placeholder="Leave blank if none" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Database</Label>
                                <Input v-model.number="configForm.redis_config.db" type="number" min="0" max="15" placeholder="0" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="configForm.processing">
                        {{ configForm.processing ? 'Saving…' : 'Save Configuration' }}
                    </Button>
                    <span v-if="configForm.recentlySuccessful" class="text-sm text-emerald-600">Saved!</span>
                </div>
            </form>
        </div>
    </div>
</template>
