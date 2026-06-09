<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Settings, Trash2, XCircle } from '@lucide/vue';
import { ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import type { Agent, AgentTimelineDay, CheckStatus, SystemConfig } from '@/types/agents';

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
    timeline: AgentTimelineDay[];
}>();

const activeTab = ref<'reports' | 'config'>('reports');

const sc = props.agent.system_config;
const configForm = useForm({
    name:                 props.agent.name,
    check_interval:       props.agent.check_interval,
    config_poll_interval: props.agent.config_poll_interval,
    auto_update:          props.agent.auto_update,
    php_config:           { ...props.agent.php_config },
    mysql_config:         { ...props.agent.mysql_config },
    reverb_config:        { ...props.agent.reverb_config },
    redis_config:         { ...props.agent.redis_config },
    system_config: {
        enabled:            sc.enabled,
        disk_paths:         sc.disk_paths.join(', ') || '/',
        network_interfaces: sc.network_interfaces.join(', '),
        cpu_warn_pct:       sc.cpu_warn_pct,
        ram_warn_pct:       sc.ram_warn_pct,
        disk_warn_pct:      sc.disk_warn_pct,
    },
});

function saveConfig() {
    configForm
        .transform((data: typeof configForm.data) => ({
            ...data,
            system_config: {
                ...data.system_config,
                disk_paths:         String(data.system_config.disk_paths).split(',').map((s: string) => s.trim()).filter(Boolean),
                network_interfaces: String(data.system_config.network_interfaces).split(',').map((s: string) => s.trim()).filter(Boolean),
            } as SystemConfig,
        }))
        .put(`/agents/${props.agent.id}`);
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

function slotToTime(slot: number): string {
    const h = Math.floor((slot * 5) / 60).toString().padStart(2, '0');
    const m = ((slot * 5) % 60).toString().padStart(2, '0');
    return `${h}:${m}`;
}

function formatDayLabel(date: string): string {
    const [year, month, day] = date.split('-').map(Number);
    const today = new Date();
    if (year === today.getFullYear() && month === today.getMonth() + 1 && day === today.getDate()) {
        return 'today';
    }
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (year === yesterday.getFullYear() && month === yesterday.getMonth() + 1 && day === yesterday.getDate()) {
        return 'yest.';
    }
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[month - 1]} ${String(day).padStart(2, ' ')}`;
}

function slotColor(status: CheckStatus | null): string {
    if (status === 'ok')       return 'bg-emerald-500 dark:bg-emerald-600';
    if (status === 'critical') return 'bg-red-500';
    if (status === 'warning')  return 'bg-amber-400';
    if (status === 'unknown')  return 'bg-zinc-400 dark:bg-zinc-600';
    return 'bg-zinc-100 dark:bg-zinc-800';
}

function hasAnyData(timeline: AgentTimelineDay[]): boolean {
    return timeline.some(day => day.slots.some(s => s !== null));
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
            <div v-if="!hasAnyData(timeline)" class="py-12 text-center text-sm text-muted-foreground">
                No reports yet. The agent will send its first report after it connects and is registered.
            </div>
            <div v-else>
                <!-- Legend -->
                <div class="mb-4 flex items-center gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm bg-emerald-500" /> OK</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm bg-amber-400" /> Warning</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm bg-red-500" /> Critical</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm bg-zinc-400 dark:bg-zinc-600" /> Unknown</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm bg-zinc-100 dark:bg-zinc-800 border border-border" /> No data</span>
                </div>

                <!-- Timeline rows -->
                <div class="space-y-1.5">
                    <div
                        v-for="day in timeline"
                        :key="day.date"
                        class="flex items-center gap-3 min-w-0"
                    >
                        <div class="w-11 shrink-0 text-right font-mono text-xs text-muted-foreground">
                            {{ formatDayLabel(day.date) }}
                        </div>
                        <div class="flex flex-1 gap-px overflow-hidden rounded-sm min-w-0">
                            <div
                                v-for="(status, i) in day.slots"
                                :key="i"
                                :class="slotColor(status)"
                                :title="`${day.date} ${slotToTime(i)} — ${status ?? 'no data'}`"
                                class="flex-1 h-7 min-w-0"
                            />
                        </div>
                    </div>
                </div>

                <!-- Time axis labels -->
                <div class="mt-1 flex pl-14 text-xs text-muted-foreground/60">
                    <span class="flex-1 text-left">00:00</span>
                    <span class="flex-1 text-center">06:00</span>
                    <span class="flex-1 text-center">12:00</span>
                    <span class="flex-1 text-center">18:00</span>
                    <span class="flex-1 text-right">24:00</span>
                </div>
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

                <!-- Auto-update -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Auto-update</CardTitle>
                                <CardDescription>
                                    Agent will automatically apply new binaries uploaded to
                                    <span class="font-mono text-xs">Settings → Agent Binary</span>
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="auto_update" v-model="configForm.auto_update" />
                                <Label for="auto_update" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
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

                <!-- System -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">System</CardTitle>
                                <CardDescription>Monitor CPU, RAM, disk, and network</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="system_enabled" v-model="configForm.system_config.enabled" />
                                <Label for="system_enabled" class="cursor-pointer">Enabled</Label>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="configForm.system_config.enabled" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <Label>CPU Warn %</Label>
                                <Input v-model.number="configForm.system_config.cpu_warn_pct" type="number" min="0" max="100" placeholder="0" />
                                <p class="text-xs text-muted-foreground">0 = disabled</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>RAM Warn %</Label>
                                <Input v-model.number="configForm.system_config.ram_warn_pct" type="number" min="0" max="100" placeholder="0" />
                                <p class="text-xs text-muted-foreground">0 = disabled</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Disk Warn %</Label>
                                <Input v-model.number="configForm.system_config.disk_warn_pct" type="number" min="0" max="100" placeholder="0" />
                                <p class="text-xs text-muted-foreground">0 = disabled</p>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Disk Paths</Label>
                            <Input v-model="configForm.system_config.disk_paths" placeholder="/, /data" />
                            <p class="text-xs text-muted-foreground">Comma-separated mount points to check. Defaults to /</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Network Interfaces</Label>
                            <Input v-model="configForm.system_config.network_interfaces" placeholder="eth0, ens3" />
                            <p class="text-xs text-muted-foreground">Comma-separated interfaces. Leave blank to report all non-loopback.</p>
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
