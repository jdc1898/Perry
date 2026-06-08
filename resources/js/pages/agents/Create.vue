<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Agents', href: '/agents' },
            { title: 'Register Agent', href: '/agents/create' },
        ],
    },
});

const form = useForm({
    name: '',
    public_key: '',
    fingerprint: '',
});

function submit() {
    form.post('/agents');
}
</script>

<template>
    <Head title="Register Agent" />

    <div class="p-4">
        <div class="mx-auto max-w-xl">
            <div class="mb-6">
                <h1 class="text-xl font-semibold">Register Agent</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Add a new server to monitor. Run the agent installer on your server first to generate credentials.
                </p>
            </div>

            <!-- Install instructions -->
            <Card class="mb-6 border-dashed">
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm">Step 1 — Install the agent on your server</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>SSH into your server and run:</p>
                    <pre class="rounded-md bg-muted px-4 py-3 text-xs font-mono overflow-x-auto">curl -fsSL https://your-server/install.sh | sudo bash -s https://{{ $page.props.ziggy?.url ?? window?.location?.origin ?? 'your-management-url.com' }}</pre>
                    <p class="pt-1">Then get your credentials with:</p>
                    <pre class="rounded-md bg-muted px-4 py-3 text-xs font-mono">sudo monitoring-agent fingerprint</pre>
                </CardContent>
            </Card>

            <!-- Registration form -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Step 2 — Register the agent here</CardTitle>
                    <CardDescription>
                        Paste the output from <code class="text-xs bg-muted px-1 py-0.5 rounded">monitoring-agent fingerprint</code>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-1.5">
                            <Label for="name">Agent Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Production Web Server"
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="public_key">Public Key</Label>
                            <Input
                                id="public_key"
                                v-model="form.public_key"
                                placeholder="Base64 encoded Ed25519 public key"
                                :class="{ 'border-destructive': form.errors.public_key }"
                                required
                            />
                            <p v-if="form.errors.public_key" class="text-xs text-destructive">{{ form.errors.public_key }}</p>
                            <p class="text-xs text-muted-foreground">The "Public Key" line from <code>monitoring-agent fingerprint</code></p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="fingerprint">Fingerprint</Label>
                            <Input
                                id="fingerprint"
                                v-model="form.fingerprint"
                                placeholder="SHA-256 fingerprint"
                                :class="{ 'border-destructive': form.errors.fingerprint }"
                                required
                            />
                            <p v-if="form.errors.fingerprint" class="text-xs text-destructive">{{ form.errors.fingerprint }}</p>
                            <p class="text-xs text-muted-foreground">The "Fingerprint" line from <code>monitoring-agent fingerprint</code></p>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Registering…' : 'Register Agent' }}
                            </Button>
                            <Button variant="ghost" type="button" as-child>
                                <a href="/agents">Cancel</a>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
