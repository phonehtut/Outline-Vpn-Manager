<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ChevronLeft, Loader2, Save, Trash2, Users } from '@lucide/vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    destroy as serversDestroy,
    index as serversIndex,
    update as serversUpdate,
} from '@/routes/admin/servers';
import type { Server, Seller } from '@/types';

const props = defineProps<{
    server: Server & { sellers: Seller[] };
    sellers: Seller[];
}>();

const form = useForm({
    name: props.server.name,
    api_url: props.server.api_url,
    cert_sha256: props.server.cert_sha256 ?? '',
    seller_ids: props.server.sellers.map((s) => s.id),
});
const isDeleteDialogOpen = ref(false);

function save() {
    form.patch(serversUpdate.url(props.server.id));
}

function deleteServer() {
    isDeleteDialogOpen.value = true;
}

function confirmDeleteServer() {
    router.delete(serversDestroy.url(props.server.id), {
        onFinish: () => { isDeleteDialogOpen.value = false; },
    });
}

function toggleSeller(id: number) {
    const idx = form.seller_ids.indexOf(id);
    if (idx === -1) {
        form.seller_ids.push(id);
    } else {
        form.seller_ids.splice(idx, 1);
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Server — ${server.name}`" />

        <template #title>
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="icon" class="size-8">
                    <Link :href="serversIndex.url()">
                        <ChevronLeft class="size-4" />
                    </Link>
                </Button>
                <span>Edit Server</span>
                <span class="text-muted-foreground font-normal">/</span>
                <span class="font-normal text-muted-foreground">{{ server.name }}</span>
            </div>
        </template>

        <template #actions>
            <Button variant="destructive" size="sm" class="gap-1.5" @click="deleteServer">
                <Trash2 class="size-3.5" />
                Delete Server
            </Button>
        </template>

        <div class="max-w-2xl">
            <Card class="shadow-xs">
                <CardHeader class="pb-3 border-b">
                    <CardTitle class="text-base font-semibold">Server Configuration</CardTitle>
                    <CardDescription>Update connection details and seller assignments.</CardDescription>
                </CardHeader>
                <CardContent class="pt-6">
                    <form class="flex flex-col gap-6" @submit.prevent="save">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <Label for="name">Server Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :aria-invalid="!!form.errors.name"
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="api_url">API Endpoint URL</Label>
                                <Input
                                    id="api_url"
                                    v-model="form.api_url"
                                    type="url"
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
                                    class="font-mono text-xs"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Seller assignment -->
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col">
                                <Label class="text-sm font-semibold flex items-center gap-1.5">
                                    <Users class="size-4" />
                                    Assigned Sellers
                                </Label>
                                <span class="text-xs text-muted-foreground">Select which sellers can manage access keys on this server.</span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="seller in sellers"
                                    :key="seller.id"
                                    class="flex items-center gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-muted/50 cursor-pointer"
                                    @click="toggleSeller(seller.id)"
                                >
                                    <Checkbox
                                        :checked="form.seller_ids.includes(seller.id)"
                                        @update:checked="toggleSeller(seller.id)"
                                    />
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-sm font-medium leading-none">{{ seller.name }}</span>
                                        <span class="text-xs text-muted-foreground truncate mt-1">{{ seller.email }}</span>
                                    </div>
                                </div>
                                <p v-if="sellers.length === 0" class="text-xs text-muted-foreground">
                                    No sellers registered yet.
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button type="submit" class="gap-1.5" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                                <Save v-else class="size-4" />
                                {{ form.processing ? 'Saving Changes…' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
        <ConfirmActionDialog
            v-model:open="isDeleteDialogOpen"
            :title="`Delete ${server.name}?`"
            description="Its local key records will be deleted. Keys on the Outline server may remain active."
            @confirm="confirmDeleteServer"
        />
    </AppLayout>
</template>
