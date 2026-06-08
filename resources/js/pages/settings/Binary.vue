<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/routes/binary';

const props = defineProps<{
    binary: {
        exists: boolean;
        size: number | null;
        modified_at: string | null;
        hash: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agent binary', href: edit() }],
    },
});

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({ binary: null as File | null });

function onFileChange(e: Event) {
    form.binary = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.post('/settings/binary', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function formatSize(bytes: number): string {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
    <Head title="Agent binary" />
    <h1 class="sr-only">Agent binary</h1>

    <form @submit.prevent="submit" class="space-y-6">
        <Heading
            variant="small"
            title="Agent Binary"
            description="Upload the compiled perry binary that agents download when running sudo perry update"
        />

        <Card>
            <CardHeader>
                <CardTitle>Current binary</CardTitle>
                <CardDescription>Served publicly at <code class="text-xs">/perry</code></CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="binary.exists" class="rounded-md border border-border bg-muted/40 px-4 py-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">perry</span>
                        <span class="text-muted-foreground">{{ formatSize(binary.size!) }}</span>
                    </div>
                    <div class="mt-1 text-xs text-muted-foreground">Last updated {{ binary.modified_at }}</div>
                    <div v-if="binary.hash" class="mt-1 font-mono text-xs text-muted-foreground/60 break-all">
                        SHA-256: {{ binary.hash }}
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No binary uploaded yet. Agents will receive a 404 until a binary is uploaded.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Upload new binary</CardTitle>
                <CardDescription>
                    Build the agent with <code class="text-xs">GOOS=linux GOARCH=amd64 go build -o perry ./cmd/agent/</code>
                    then upload the resulting binary here.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <input
                    ref="fileInput"
                    type="file"
                    accept="application/octet-stream,.bin"
                    class="block w-full text-sm text-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer"
                    @change="onFileChange"
                />
                <p v-if="form.errors.binary" class="text-sm text-destructive">{{ form.errors.binary }}</p>
                <p class="text-xs text-muted-foreground">Max 100 MB. Replaces any existing binary immediately.</p>
            </CardContent>
        </Card>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="form.processing || !form.binary">
                {{ form.processing ? 'Uploading…' : 'Upload Binary' }}
            </Button>
        </div>
    </form>
</template>
