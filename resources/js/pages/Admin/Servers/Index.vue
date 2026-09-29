<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ClipboardPaste, Loader2, Pencil, Plus, Server as ServerIcon, Trash2 } from '@lucide/vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import { destroy as serversDestroy, edit as serversEdit, store as serversStore } from '@/routes/admin/servers';
import type { Server } from '@/types';

defineProps<{
    servers: Server[];
}>();

const pasteMode = ref(false);
const serverToDelete = ref<Server | null>(null);
const isDeleteDialogOpen = ref(false);

const form = useForm({
    name: '',
    api_url: '',
    cert_sha256: '',
    config_json: '',
});

function onPaste(event: ClipboardEvent) {
    const text = event.clipboardData?.getData('text') ?? '';
    try {
        const parsed = JSON.parse(text);
        if (parsed.apiUrl) {
            event.preventDefault();
            form.config_json = text;
            form.api_url = parsed.apiUrl;
            form.cert_sha256 = parsed.certSha256 ?? '';
            pasteMode.value = false;
        }
    } catch {
        // not JSON, let default paste happen
    }
}

function storeServer() {
    form.post(serversStore.url(), {
        onSuccess: () => {
            form.reset();
            pasteMode.value = false;
        },
    });
}

function requestDeleteServer(server: Server) {
    serverToDelete.value = server;
    isDeleteDialogOpen.value = true;
}

function deleteServer() {
    if (!serverToDelete.value) return;

    router.delete(serversDestroy.url(serverToDelete.value.id), {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            serverToDelete.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Servers" />

        <template #title>Outline Servers</template>

        <div class="grid gap-8 lg:grid-cols-5">
            <!-- Servers Table Card -->
            <div class="lg:col-span-3">
                <Card class="shadow-xs overflow-hidden">
                    <CardHeader class="pb-3 border-b">
                        <CardTitle class="text-base font-semibold">Connected Servers</CardTitle>
                        <CardDescription>Outline VPN servers managed by this application.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Server Name</TableHead>
                                    <TableHead>API Endpoint</TableHead>
                                    <TableHead>Keys</TableHead>
                                    <TableHead>Assigned Sellers</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="servers.length === 0" :colspan="5">
                                    No Outline servers connected yet. Add one using the form.
                                </TableEmpty>
                                <TableRow v-for="server in servers" :key="server.id">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <ServerIcon class="size-4 text-muted-foreground" />
                                            <span>{{ server.name }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="max-w-xs">
                                        <span class="block truncate text-xs text-muted-foreground font-mono">
                                            {{ server.api_url }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="font-normal text-xs">
                                            {{ server.access_keys_count ?? 0 }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" class="font-normal text-xs">
                                            {{ server.sellers?.length ?? 0 }} seller{{ (server.sellers?.length ?? 0) !== 1 ? 's' : '' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button as-child variant="ghost" size="sm" class="h-8 gap-1">
                                                <Link :href="serversEdit.url(server.id)">
                                                    <Pencil class="size-3.5" />
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                title="Delete server"
                                                @click="requestDeleteServer(server)"
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

            <!-- Add Server Card -->
            <div class="lg:col-span-2">
                <Card class="shadow-xs">
                    <CardHeader class="pb-3 border-b flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Plus class="size-4" />
                                Add Server
                            </CardTitle>
                            <CardDescription>Connect a new Outline server.</CardDescription>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs gap-1"
                            type="button"
                            @click="pasteMode = !pasteMode"
                        >
                            <ClipboardPaste class="size-3" />
                            {{ pasteMode ? 'Manual Form' : 'Paste Config' }}
                        </Button>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <form class="flex flex-col gap-4" @submit.prevent="storeServer">
                            <!-- JSON Paste Box -->
                            <div v-if="pasteMode" class="flex flex-col gap-1.5 rounded-lg border border-primary/20 bg-primary/5 p-3">
                                <Label for="config_json" class="text-xs font-semibold">
                                    Paste Outline Installation Output
                                </Label>
                                <Textarea
                                    id="config_json"
                                    v-model="form.config_json"
                                    placeholder='{"apiUrl":"https://…","certSha256":"…"}'
                                    class="font-mono text-xs"
                                    rows="4"
                                    @paste="onPaste"
                                />
                                <span class="text-[11px] text-muted-foreground">
                                    Copy the JSON from your Outline setup terminal and paste here.
                                </span>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="name">
                                    Server Name <span class="text-muted-foreground font-normal">(optional — auto-detected)</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g. Singapore VPS"
                                    :aria-invalid="!!form.errors.name"
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="api_url">Management API URL</Label>
                                <Input
                                    id="api_url"
                                    v-model="form.api_url"
                                    type="url"
                                    placeholder="https://200.141.5.144:30143/…"
                                    class="font-mono text-xs"
                                    :aria-invalid="!!form.errors.api_url"
                                />
                                <p v-if="form.errors.api_url" class="text-xs text-destructive">
                                    {{ form.errors.api_url }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="cert_sha256">
                                    Cert SHA256 Fingerprint <span class="text-muted-foreground font-normal">(optional)</span>
                                </Label>
                                <Input
                                    id="cert_sha256"
                                    v-model="form.cert_sha256"
                                    placeholder="DBE8C8F1…"
                                    class="font-mono text-xs"
                                />
                            </div>

                            <Button type="submit" class="w-full mt-2" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                                {{ form.processing ? 'Connecting Server…' : 'Add Server' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
        <ConfirmActionDialog
            v-model:open="isDeleteDialogOpen"
            :title="`Delete ${serverToDelete?.name ?? 'server'}?`"
            :description="serverToDelete ? `Its local key records will be deleted. Keys on the Outline server may remain active.` : ''"
            @confirm="deleteServer"
        />
    </AppLayout>
</template>
