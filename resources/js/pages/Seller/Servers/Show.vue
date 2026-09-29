<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Check,
    ChevronLeft,
    Copy,
    KeyRound,
    Loader2,
    Pencil,
    Plus,
    RefreshCw,
    Trash2,
} from '@lucide/vue';
import KeyBadge from '@/components/KeyBadge.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard as sellerDashboard } from '@/routes/seller';
import {
    destroy as keysDestroy,
    store as keysStore,
    sync as keysSync,
    update as keysUpdate,
} from '@/routes/seller/servers/keys';
import { copyToClipboard } from '@/lib/utils';
import { toast } from 'vue-sonner';
import type { AccessKey } from '@/types';

const props = defineProps<{
    server: { id: number; name: string };
    keys: AccessKey[];
}>();

// Sync from Outline server
const syncing = ref(false);
function syncKeys() {
    syncing.value = true;
    router.post(
        keysSync.url(props.server.id),
        {},
        { onFinish: () => { syncing.value = false; } },
    );
}

// Copy access URL with feedback — appends #KeyName so Shadowsocks clients label the key
const copiedKeyId = ref<number | null>(null);
async function copyAccessUrl(key: AccessKey) {
    if (!key.access_url) {
        toast.warning(`No Access URL for "${key.name}"`, {
            description: 'This key has no ss:// URL stored yet. Click "Sync from Server" to retrieve the latest keys and URLs from Outline.',
        });
        return;
    }

    // Build labeled URL: strip existing fragment, then append #EncodedKeyName
    const baseUrl = key.access_url.split('#')[0];
    const labeledUrl = `${baseUrl}#${encodeURIComponent(key.name)}`;

    const success = await copyToClipboard(labeledUrl);
    if (success) {
        copiedKeyId.value = key.id;
        toast.success(`Copied "${key.name}" key!`, {
            description: `ss://…#${key.name} has been copied to your clipboard.`,
        });
        setTimeout(() => {
            if (copiedKeyId.value === key.id) {
                copiedKeyId.value = null;
            }
        }, 3000);
    } else {
        toast.error('Failed to copy', {
            description: 'Clipboard access was blocked. Please try copying manually.',
        });
    }
}

// Create key form
const createForm = useForm({
    name: '',
    data_limit_bytes: '',
    data_limit_gb: '',
    expires_at: '',
});

function storeKey() {
    createForm
        .transform((data) => ({
            name: data.name,
            data_limit_bytes: data.data_limit_gb ? Math.round(parseFloat(data.data_limit_gb) * 1024 * 1024 * 1024) : null,
            expires_at: data.expires_at || null,
        }))
        .post(keysStore.url(props.server.id), {
            onSuccess: () => createForm.reset(),
        });
}

// Edit modal state using shadcn Dialog
const isEditDialogOpen = ref(false);
const editingKey = ref<AccessKey | null>(null);
const deletingKey = ref<AccessKey | null>(null);
const isDeleteDialogOpen = ref(false);
const editForm = useForm({
    name: '',
    data_limit_bytes: '',
    data_limit_gb: '',
    expires_at: '',
});

function openEdit(key: AccessKey) {
    editingKey.value = key;
    editForm.name = key.name;
    editForm.data_limit_gb = key.data_limit_bytes
        ? String(Math.round((key.data_limit_bytes / (1024 * 1024 * 1024)) * 100) / 100)
        : '';
    editForm.expires_at = key.expires_at ? key.expires_at.substring(0, 10) : '';
    isEditDialogOpen.value = true;
}

function updateKey() {
    if (!editingKey.value) return;
    editForm
        .transform((data) => ({
            name: data.name,
            data_limit_bytes: data.data_limit_gb ? Math.round(parseFloat(data.data_limit_gb) * 1024 * 1024 * 1024) : null,
            expires_at: data.expires_at || null,
        }))
        .patch(
            keysUpdate.url({ server: props.server.id, accessKey: editingKey.value.id }),
            {
                onSuccess: () => {
                    isEditDialogOpen.value = false;
                    editingKey.value = null;
                },
            },
        );
}

function deleteKey(key: AccessKey) {
    deletingKey.value = key;
    isDeleteDialogOpen.value = true;
}

function confirmDeleteKey() {
    if (!deletingKey.value) return;

    router.delete(keysDestroy.url({ server: props.server.id, accessKey: deletingKey.value.id }), {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            deletingKey.value = null;
        },
    });
}

function isExpired(expiresAt: string | null): boolean {
    if (!expiresAt) return false;
    return new Date(expiresAt) <= new Date();
}

function formatBytes(bytes: number | null): string {
    if (!bytes) return 'Unlimited';
    if (bytes >= 1024 * 1024 * 1024) {
        return `${Math.round(bytes / (1024 * 1024 * 1024) * 10) / 10} GB`;
    }
    return `${Math.round(bytes / (1024 * 1024))} MB`;
}

function formatUsage(bytes: number | null): string {
    if (bytes === null) return '—';
    if (bytes < 1024) return `${bytes} B`;
    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unitIndex = -1;
    do {
        value /= 1024;
        unitIndex++;
    } while (value >= 1024 && unitIndex < units.length - 1);
    return `${Math.round(value * 10) / 10} ${units[unitIndex]}`;
}

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}
</script>

<template>
    <AppLayout>
        <Head :title="`${server.name} — Keys`" />

        <template #title>
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="icon" class="size-8">
                    <Link :href="sellerDashboard.url()">
                        <ChevronLeft class="size-4" />
                    </Link>
                </Button>
                <span>Dashboard</span>
                <span class="text-muted-foreground font-normal">/</span>
                <span class="font-normal text-muted-foreground">{{ server.name }}</span>
            </div>
        </template>

        <template #actions>
            <Button
                variant="outline"
                size="sm"
                class="gap-1.5"
                :disabled="syncing"
                @click="syncKeys"
            >
                <RefreshCw class="size-3.5" :class="{ 'animate-spin': syncing }" />
                {{ syncing ? 'Syncing Server…' : 'Sync from Server' }}
            </Button>
        </template>

        <div class="grid gap-8 lg:grid-cols-5">
            <!-- Access Keys Table Card -->
            <div class="lg:col-span-3">
                <Card class="shadow-xs overflow-hidden">
                    <CardHeader class="pb-3 border-b flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                Access Keys
                                <Badge variant="secondary" class="font-normal text-xs ml-1">
                                    {{ keys.length }}
                                </Badge>
                            </CardTitle>
                            <CardDescription>All keys generated on this Outline server.</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Key Name & Access URL</TableHead>
                                    <TableHead>Data Limit</TableHead>
                                    <TableHead>Expiry Status</TableHead>
                                    <TableHead>Last active</TableHead>
                                    <TableHead>Usage (last 30 days)</TableHead>
                                    <TableHead title="Most devices connected simultaneously with this key during the last 30 days.">
                                        Peak devices (last 30 days)
                                    </TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="keys.length === 0" :colspan="7">
                                    No access keys found. Create a key or use "Sync from Server".
                                </TableEmpty>
                                <TableRow v-for="key in keys" :key="key.id">
                                    <TableCell>
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium leading-none">{{ key.name }}</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    class="h-6 text-[11px] px-2 gap-1.5 transition-colors"
                                                    :class="copiedKeyId === key.id ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                                                    :title="key.access_url ? 'Click to copy Shadowsocks ss:// key' : 'No access URL stored (Click to sync)'"
                                                    @click="copyAccessUrl(key)"
                                                >
                                                    <Check v-if="copiedKeyId === key.id" class="size-3" />
                                                    <Copy v-else class="size-3" />
                                                    {{ copiedKeyId === key.id ? 'Copied!' : 'Copy Key' }}
                                                </Button>
                                                <span class="text-[11px] font-mono text-muted-foreground">
                                                    ID: {{ key.outline_key_id }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-xs">
                                        <span v-if="isExpired(key.expires_at)" class="text-destructive font-medium">
                                            Blocked (0 B)
                                        </span>
                                        <span v-else class="text-muted-foreground">
                                            {{ formatBytes(key.data_limit_bytes) }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <KeyBadge :expires-at="key.expires_at" />
                                    </TableCell>
                                    <TableCell class="whitespace-nowrap text-xs">
                                        {{ formatDateTime(key.last_active_at) }}
                                    </TableCell>
                                    <TableCell class="whitespace-nowrap text-xs">
                                        {{ formatUsage(key.usage_bytes) }}
                                    </TableCell>
                                    <TableCell class="whitespace-nowrap text-xs">
                                        <template v-if="key.detailed_metrics_supported === false">Not supported by server</template>
                                        <template v-else-if="key.peak_device_count !== null">
                                            <div>{{ key.peak_device_count }}</div>
                                            <div class="text-muted-foreground">{{ formatDateTime(key.peak_device_at) }}</div>
                                        </template>
                                        <template v-else>—</template>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                title="Edit key"
                                                @click="openEdit(key)"
                                            >
                                                <Pencil class="size-3.5" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                title="Delete key"
                                                @click="deleteKey(key)"
                                            >
                                                <Trash2 class="size-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Create Key Form Card -->
            <div class="lg:col-span-2">
                <Card class="shadow-xs">
                    <CardHeader class="pb-3 border-b">
                        <CardTitle class="text-base font-semibold flex items-center gap-2">
                            <Plus class="size-4" />
                            Create Access Key
                        </CardTitle>
                        <CardDescription>Generate a new VPN access key on this Outline server.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <form class="flex flex-col gap-4" @submit.prevent="storeKey">
                            <div class="flex flex-col gap-1.5">
                                <Label for="name">Key Name</Label>
                                <Input
                                    id="name"
                                    v-model="createForm.name"
                                    placeholder="e.g. John's iPhone"
                                    :aria-invalid="!!createForm.errors.name"
                                />
                                <p v-if="createForm.errors.name" class="text-xs text-destructive">
                                    {{ createForm.errors.name }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="data_limit_gb">
                                    Data Limit (GB) <span class="text-muted-foreground font-normal">(optional)</span>
                                </Label>
                                <Input
                                    id="data_limit_gb"
                                    v-model="createForm.data_limit_gb"
                                    type="number"
                                    min="0.1"
                                    step="0.1"
                                    placeholder="e.g. 10 (blank for unlimited)"
                                    :aria-invalid="!!createForm.errors.data_limit_bytes"
                                />
                                <p v-if="createForm.errors.data_limit_bytes" class="text-xs text-destructive">
                                    {{ createForm.errors.data_limit_bytes }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="expires_at">
                                    Expiry Date <span class="text-muted-foreground font-normal">(optional)</span>
                                </Label>
                                <Input
                                    id="expires_at"
                                    v-model="createForm.expires_at"
                                    type="date"
                                    :aria-invalid="!!createForm.errors.expires_at"
                                />
                                <p v-if="createForm.errors.expires_at" class="text-xs text-destructive">
                                    {{ createForm.errors.expires_at }}
                                </p>
                            </div>

                            <Button type="submit" class="w-full mt-2" :disabled="createForm.processing">
                                <Loader2 v-if="createForm.processing" class="mr-2 size-4 animate-spin" />
                                {{ createForm.processing ? 'Creating Key…' : 'Generate Key' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Edit Key Dialog (shadcn modal) -->
        <Dialog :open="isEditDialogOpen" @update:open="(val: boolean) => isEditDialogOpen = val">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <KeyRound class="size-4" />
                        Edit Access Key
                    </DialogTitle>
                    <DialogDescription>
                        Update name, data limit, or expiration for key "{{ editingKey?.name }}".
                    </DialogDescription>
                </DialogHeader>

                <form class="flex flex-col gap-4 py-2" @submit.prevent="updateKey">
                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_name">Key Name</Label>
                        <Input
                            id="edit_name"
                            v-model="editForm.name"
                            :aria-invalid="!!editForm.errors.name"
                        />
                        <p v-if="editForm.errors.name" class="text-xs text-destructive">
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_data_limit_gb">Data Limit (GB)</Label>
                        <Input
                            id="edit_data_limit_gb"
                            v-model="editForm.data_limit_gb"
                            type="number"
                            min="0.1"
                            step="0.1"
                            placeholder="Blank for unlimited"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_expires_at">Expiry Date</Label>
                        <Input
                            id="edit_expires_at"
                            v-model="editForm.expires_at"
                            type="date"
                        />
                    </div>

                    <DialogFooter class="pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            <Loader2 v-if="editForm.processing" class="mr-2 size-4 animate-spin" />
                            {{ editForm.processing ? 'Saving Changes…' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <ConfirmActionDialog
            v-model:open="isDeleteDialogOpen"
            :title="`Delete ${deletingKey?.name ?? 'access key'}?`"
            description="This removes the key from both the Outline server and the database."
            @confirm="confirmDeleteKey"
        />
    </AppLayout>
</template>
