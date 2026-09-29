<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { KeyRound, Loader2, Pencil, Plus, Trash2 } from '@lucide/vue';
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
import { destroy as keysDestroy, store as keysStore, update as keysUpdate } from '@/routes/admin/keys';
import type { AccessKey, Server } from '@/types';

type AdminAccessKey = AccessKey & {
    creator: { id: number; name: string; email: string };
    server: { id: number; name: string };
};

defineProps<{
    keys: {
        data: AdminAccessKey[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
        current_page: number;
        last_page: number;
    };
    servers: Pick<Server, 'id' | 'name'>[];
}>();

const createForm = useForm({
    server_id: '',
    name: '',
    data_limit_bytes: '',
    data_limit_gb: '',
    expires_at: '',
});

function createKey() {
    createForm
        .transform((data) => ({
            server_id: Number(data.server_id),
            name: data.name,
            data_limit_bytes: data.data_limit_gb ? Math.round(parseFloat(data.data_limit_gb) * 1024 ** 3) : null,
            expires_at: data.expires_at || null,
        }))
        .post(keysStore.url(), { onSuccess: () => createForm.reset() });
}

const isEditDialogOpen = ref(false);
const editingKey = ref<AdminAccessKey | null>(null);
const isDeleteDialogOpen = ref(false);
const keyToDelete = ref<AdminAccessKey | null>(null);
const editForm = useForm({ name: '', data_limit_bytes: '', data_limit_gb: '', expires_at: '' });

function openEdit(key: AdminAccessKey) {
    editingKey.value = key;
    editForm.name = key.name;
    editForm.data_limit_gb = key.data_limit_bytes
        ? String(Math.round((key.data_limit_bytes / 1024 ** 3) * 100) / 100)
        : '';
    editForm.expires_at = key.expires_at ? key.expires_at.substring(0, 10) : '';
    isEditDialogOpen.value = true;
}

function updateKey() {
    if (!editingKey.value) return;

    editForm
        .transform((data) => ({
            name: data.name,
            data_limit_bytes: data.data_limit_gb ? Math.round(parseFloat(data.data_limit_gb) * 1024 ** 3) : null,
            expires_at: data.expires_at || null,
        }))
        .patch(keysUpdate.url(editingKey.value.id), {
            onSuccess: () => {
                isEditDialogOpen.value = false;
                editingKey.value = null;
            },
        });
}

function requestDeleteKey(key: AdminAccessKey) {
    keyToDelete.value = key;
    isDeleteDialogOpen.value = true;
}

function deleteKey() {
    if (!keyToDelete.value) return;

    router.delete(keysDestroy.url(keyToDelete.value.id), {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            keyToDelete.value = null;
        },
    });
}

function formatLimit(bytes: number | null): string {
    if (!bytes) return 'Unlimited';
    if (bytes >= 1024 ** 3) return `${Math.round((bytes / 1024 ** 3) * 10) / 10} GB`;
    return `${Math.round(bytes / 1024 ** 2)} MB`;
}

function formatBytes(bytes: number | null): string {
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
        <Head title="Access Keys" />

        <template #title>Access Keys</template>

        <div class="grid gap-8 xl:grid-cols-5">
            <Card class="overflow-hidden shadow-xs xl:col-span-3">
                <CardHeader class="border-b pb-3">
                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                        <KeyRound class="size-4" /> All Access Keys
                        <Badge variant="secondary" class="ml-1 text-xs font-normal">{{ keys.total }}</Badge>
                    </CardTitle>
                    <CardDescription>Keys across all Outline servers, including their creator.</CardDescription>
                </CardHeader>
                <CardContent class="overflow-x-auto p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Key</TableHead>
                                <TableHead>Server</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead>Limit</TableHead>
                                <TableHead>Last active</TableHead>
                                <TableHead>Usage (last 30 days)</TableHead>
                                <TableHead title="Most devices connected simultaneously with this key during the last 30 days.">
                                    Peak devices (last 30 days)
                                </TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="keys.data.length === 0" :colspan="8">No access keys found.</TableEmpty>
                            <TableRow v-for="key in keys.data" :key="key.id">
                                <TableCell>
                                    <div class="flex min-w-32 flex-col gap-1">
                                        <span class="font-medium">{{ key.name }}</span>
                                        <span class="font-mono text-[11px] text-muted-foreground">{{ key.outline_key_id }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap">{{ key.server.name }}</TableCell>
                                <TableCell>
                                    <div class="flex min-w-32 flex-col">
                                        <span class="font-medium">{{ key.creator.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ key.creator.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-xs text-muted-foreground">
                                    <div>{{ formatLimit(key.data_limit_bytes) }}</div>
                                    <div v-if="key.expires_at" class="mt-1">Expires {{ key.expires_at.substring(0, 10) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-xs">
                                    {{ formatDateTime(key.last_active_at) }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-xs">
                                    {{ formatBytes(key.usage_bytes) }}
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
                                        <Button variant="ghost" size="icon" class="size-8" title="Edit key" @click="openEdit(key)">
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            title="Delete key"
                                            @click="requestDeleteKey(key)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="keys.last_page > 1" class="flex flex-wrap justify-end gap-1 border-t p-3">
                        <Button
                            v-for="link in keys.links"
                            :key="link.label"
                            as-child
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            class="min-w-8"
                        >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label" />
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-xs xl:col-span-2">
                <CardHeader class="border-b pb-3">
                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                        <Plus class="size-4" /> Create Access Key
                    </CardTitle>
                    <CardDescription>Create a key on any connected Outline server. Admin will be its creator.</CardDescription>
                </CardHeader>
                <CardContent class="pt-6">
                    <form class="flex flex-col gap-4" @submit.prevent="createKey">
                        <div class="flex flex-col gap-1.5">
                            <Label for="server_id">Server</Label>
                            <select
                                id="server_id"
                                v-model="createForm.server_id"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                                :aria-invalid="!!createForm.errors.server_id"
                            >
                                <option value="" disabled>Select a server</option>
                                <option v-for="server in servers" :key="server.id" :value="String(server.id)">
                                    {{ server.name }}
                                </option>
                            </select>
                            <p v-if="createForm.errors.server_id" class="text-xs text-destructive">{{ createForm.errors.server_id }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="key_name">Key Name</Label>
                            <Input id="key_name" v-model="createForm.name" placeholder="e.g. Admin device" :aria-invalid="!!createForm.errors.name" />
                            <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="data_limit_gb">Data Limit (GB) <span class="font-normal text-muted-foreground">(optional)</span></Label>
                            <Input id="data_limit_gb" v-model="createForm.data_limit_gb" type="number" min="0.1" step="0.1" placeholder="Blank for unlimited" />
                            <p v-if="createForm.errors.data_limit_bytes" class="text-xs text-destructive">{{ createForm.errors.data_limit_bytes }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="expires_at">Expiry Date <span class="font-normal text-muted-foreground">(optional)</span></Label>
                            <Input id="expires_at" v-model="createForm.expires_at" type="date" />
                            <p v-if="createForm.errors.expires_at" class="text-xs text-destructive">{{ createForm.errors.expires_at }}</p>
                        </div>
                        <Button type="submit" class="mt-2 w-full" :disabled="createForm.processing || servers.length === 0">
                            <Loader2 v-if="createForm.processing" class="mr-2 size-4 animate-spin" />
                            {{ createForm.processing ? 'Creating Key…' : 'Generate Key' }}
                        </Button>
                        <p v-if="servers.length === 0" class="text-xs text-muted-foreground">Add an Outline server before creating keys.</p>
                    </form>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="isEditDialogOpen" @update:open="(value: boolean) => isEditDialogOpen = value">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Access Key</DialogTitle>
                    <DialogDescription>Update {{ editingKey?.name }}. Its creator will remain unchanged.</DialogDescription>
                </DialogHeader>
                <form class="flex flex-col gap-4 py-2" @submit.prevent="updateKey">
                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_key_name">Key Name</Label>
                        <Input id="edit_key_name" v-model="editForm.name" :aria-invalid="!!editForm.errors.name" />
                        <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_data_limit_gb">Data Limit (GB)</Label>
                        <Input id="edit_data_limit_gb" v-model="editForm.data_limit_gb" type="number" min="0.1" step="0.1" placeholder="Blank for unlimited" />
                        <p v-if="editForm.errors.data_limit_bytes" class="text-xs text-destructive">{{ editForm.errors.data_limit_bytes }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="edit_expires_at">Expiry Date</Label>
                        <Input id="edit_expires_at" v-model="editForm.expires_at" type="date" />
                        <p v-if="editForm.errors.expires_at" class="text-xs text-destructive">{{ editForm.errors.expires_at }}</p>
                    </div>
                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing">
                            <Loader2 v-if="editForm.processing" class="mr-2 size-4 animate-spin" />
                            {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <ConfirmActionDialog
            v-model:open="isDeleteDialogOpen"
            :title="`Delete ${keyToDelete?.name ?? 'access key'}?`"
            :description="keyToDelete ? `This will also delete it from ${keyToDelete.server.name} on the Outline server.` : ''"
            @confirm="deleteKey"
        />
    </AppLayout>
</template>
