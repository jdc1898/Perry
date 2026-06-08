<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { edit } from '@/routes/notifications';

type ChannelConfig = {
    enabled: boolean;
    config: Record<string, unknown>;
};

const props = defineProps<{
    channels: {
        mail: ChannelConfig;
        slack: ChannelConfig;
        webhook: ChannelConfig;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notification settings', href: edit() }],
    },
});

const form = useForm({
    mail: {
        enabled: props.channels.mail.enabled,
        config: {
            addresses: ((props.channels.mail.config.addresses as string[]) ?? []).join('\n'),
        },
    },
    slack: {
        enabled: props.channels.slack.enabled,
        config: {
            webhook_url: (props.channels.slack.config.webhook_url as string) ?? '',
        },
    },
    webhook: {
        enabled: props.channels.webhook.enabled,
        config: {
            url: (props.channels.webhook.config.url as string) ?? '',
            secret: (props.channels.webhook.config.secret as string) ?? '',
        },
    },
});

function submit() {
    const data = {
        mail: {
            enabled: form.mail.enabled,
            config: {
                addresses: form.mail.config.addresses
                    .split('\n')
                    .map((a) => a.trim())
                    .filter(Boolean),
            },
        },
        slack: {
            enabled: form.slack.enabled,
            config: { webhook_url: form.slack.config.webhook_url },
        },
        webhook: {
            enabled: form.webhook.enabled,
            config: {
                url: form.webhook.config.url,
                secret: form.webhook.config.secret,
            },
        },
    };

    form.transform(() => data).put('/settings/notifications', {
        preserveScroll: true,
    });
}

const mailErrors = computed(() => ({
    addresses: (form.errors as Record<string, string>)['mail.config.addresses'] ?? '',
}));

const slackErrors = computed(() => ({
    webhook_url: (form.errors as Record<string, string>)['slack.config.webhook_url'] ?? '',
}));

const webhookErrors = computed(() => ({
    url: (form.errors as Record<string, string>)['webhook.config.url'] ?? '',
    secret: (form.errors as Record<string, string>)['webhook.config.secret'] ?? '',
}));
</script>

<template>
    <Head title="Notification settings" />
    <h1 class="sr-only">Notification settings</h1>

    <form @submit.prevent="submit" class="space-y-6">
        <Heading
            variant="small"
            title="Notifications"
            description="Configure how you receive alerts when agents go offline or recover"
        />

        <!-- Email -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Email</CardTitle>
                        <CardDescription class="mt-1">
                            Receive alerts by email via Mailgun
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="mail-enabled"
                            v-model="form.mail.enabled"
                        />
                        <Label for="mail-enabled">Enabled</Label>
                    </div>
                </div>
            </CardHeader>
            <CardContent v-if="form.mail.enabled" class="space-y-3">
                <Separator class="mb-4" />
                <div class="grid gap-2">
                    <Label for="mail-addresses">
                        Recipients
                        <span class="text-muted-foreground font-normal">(one per line)</span>
                    </Label>
                    <textarea
                        id="mail-addresses"
                        v-model="form.mail.config.addresses"
                        rows="3"
                        placeholder="alerts@example.com&#10;ops@example.com"
                        class="border-input placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="mailErrors.addresses" />
                </div>
            </CardContent>
        </Card>

        <!-- Slack -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Slack</CardTitle>
                        <CardDescription class="mt-1">
                            Post alerts to a Slack channel via incoming webhook
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="slack-enabled"
                            v-model="form.slack.enabled"
                        />
                        <Label for="slack-enabled">Enabled</Label>
                    </div>
                </div>
            </CardHeader>
            <CardContent v-if="form.slack.enabled" class="space-y-3">
                <Separator class="mb-4" />
                <div class="grid gap-2">
                    <Label for="slack-webhook">Webhook URL</Label>
                    <Input
                        id="slack-webhook"
                        v-model="form.slack.config.webhook_url"
                        type="url"
                        placeholder="https://hooks.slack.com/services/..."
                    />
                    <InputError :message="slackErrors.webhook_url" />
                </div>
            </CardContent>
        </Card>

        <!-- Webhook -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Webhook</CardTitle>
                        <CardDescription class="mt-1">
                            POST JSON payloads to your own HTTP endpoint
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="webhook-enabled"
                            v-model="form.webhook.enabled"
                        />
                        <Label for="webhook-enabled">Enabled</Label>
                    </div>
                </div>
            </CardHeader>
            <CardContent v-if="form.webhook.enabled" class="space-y-4">
                <Separator class="mb-4" />
                <div class="grid gap-2">
                    <Label for="webhook-url">URL</Label>
                    <Input
                        id="webhook-url"
                        v-model="form.webhook.config.url"
                        type="url"
                        placeholder="https://your-server.com/perry-webhook"
                    />
                    <InputError :message="webhookErrors.url" />
                </div>
                <div class="grid gap-2">
                    <Label for="webhook-secret">
                        Secret
                        <span class="text-muted-foreground font-normal">(optional)</span>
                    </Label>
                    <Input
                        id="webhook-secret"
                        v-model="form.webhook.config.secret"
                        type="password"
                        placeholder="Used to sign the request"
                    />
                    <InputError :message="webhookErrors.secret" />
                </div>
            </CardContent>
        </Card>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="form.processing">Save</Button>
        </div>
    </form>
</template>
