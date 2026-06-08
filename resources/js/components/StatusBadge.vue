<script setup lang="ts">
import type { AgentStatus, CheckStatus } from '@/types/agents';

defineProps<{
    status: AgentStatus | CheckStatus | 'online' | 'offline';
    size?: 'sm' | 'md';
}>();

const labels: Record<string, string> = {
    ok: 'OK',
    warning: 'Warning',
    critical: 'Critical',
    unknown: 'Unknown',
    pending: 'Pending',
    active: 'Active',
    revoked: 'Revoked',
    online: 'Online',
    offline: 'Offline',
};

const classes: Record<string, string> = {
    ok:       'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    warning:  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    unknown:  'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    pending:  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    active:   'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    revoked:  'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    online:   'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    offline:  'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
};

const dots: Record<string, string> = {
    ok:       'bg-emerald-500',
    warning:  'bg-amber-500',
    critical: 'bg-red-500',
    unknown:  'bg-zinc-400',
    pending:  'bg-blue-500',
    active:   'bg-emerald-500',
    revoked:  'bg-red-500',
    online:   'bg-emerald-500',
    offline:  'bg-zinc-400',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full font-medium',
            size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
            classes[status] ?? classes.unknown,
        ]"
    >
        <span :class="['size-1.5 rounded-full', dots[status] ?? dots.unknown]" />
        {{ labels[status] ?? status }}
    </span>
</template>
