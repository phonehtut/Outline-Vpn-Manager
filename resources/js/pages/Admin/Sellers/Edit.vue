<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ChevronLeft, Loader2, Save, Trash2 } from '@lucide/vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    destroy as sellersDestroy,
    index as sellersIndex,
    update as sellersUpdate,
} from '@/routes/admin/sellers';
import type { Seller, Server } from '@/types';

const props = defineProps<{
    seller: Seller & { servers: Server[] };
    servers: Server[];
}>();

const form = useForm({
    name: props.seller.name,
    email: props.seller.email,
    password: '',
    password_confirmation: '',
    server_ids: props.seller.servers.map((s) => s.id),
});
const isDeleteDialogOpen = ref(false);

function save() {
    form.patch(sellersUpdate.url(props.seller.id));
}

function deleteSeller() {
    isDeleteDialogOpen.value = true;
}

function confirmDeleteSeller() {
    router.delete(sellersDestroy.url(props.seller.id), {
        onFinish: () => { isDeleteDialogOpen.value = false; },
    });
}

function toggleServer(id: number) {
    const idx = form.server_ids.indexOf(id);
    if (idx === -1) {
        form.server_ids.push(id);
    } else {
        form.server_ids.splice(idx, 1);
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Seller — ${seller.name}`" />

        <template #title>
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="icon" class="size-8">
                    <Link :href="sellersIndex.url()">
                        <ChevronLeft class="size-4" />
                    </Link>
                </Button>
                <span>Edit Seller</span>
                <span class="text-muted-foreground font-normal">/</span>
                <span class="font-normal text-muted-foreground">{{ seller.name }}</span>
            </div>
        </template>

        <template #actions>
            <Button variant="destructive" size="sm" class="gap-1.5" @click="deleteSeller">
                <Trash2 class="size-3.5" />
                Delete Seller
            </Button>
        </template>

        <div class="max-w-2xl">
            <Card class="shadow-xs">
                <CardHeader class="pb-3 border-b">
                    <CardTitle class="text-base font-semibold">Seller Profile & Assignments</CardTitle>
                    <CardDescription>Update profile information and manage assigned Outline servers.</CardDescription>
                </CardHeader>
                <CardContent class="pt-6">
                    <form class="flex flex-col gap-6" @submit.prevent="save">
                        <!-- Profile fields -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <Label for="name">Full Name</Label>
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
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    :aria-invalid="!!form.errors.email"
                                />
                                <p v-if="form.errors.email" class="text-xs text-destructive">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="password">New Password <span class="text-muted-foreground font-normal">(optional)</span></Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Leave blank to keep current"
                                    :aria-invalid="!!form.errors.password"
                                />
                                <p v-if="form.errors.password" class="text-xs text-destructive">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="password_confirmation">Confirm Password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Server assignments -->
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col">
                                <Label class="text-sm font-semibold">Assigned Servers</Label>
                                <span class="text-xs text-muted-foreground">Select the servers this seller is permitted to manage keys on.</span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="server in servers"
                                    :key="server.id"
                                    class="flex items-center gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-muted/50 cursor-pointer"
                                    @click="toggleServer(server.id)"
                                >
                                    <Checkbox
                                        :checked="form.server_ids.includes(server.id)"
                                        @update:checked="toggleServer(server.id)"
                                    />
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-sm font-medium leading-none">{{ server.name }}</span>
                                        <span class="text-xs text-muted-foreground truncate mt-1">{{ server.api_url }}</span>
                                    </div>
                                </div>
                                <p v-if="servers.length === 0" class="text-xs text-muted-foreground">
                                    No servers created yet. Add a server first.
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
            :title="`Delete ${seller.name}?`"
            description="Their local key records will also be deleted. Those keys may remain active on their Outline servers."
            @confirm="confirmDeleteSeller"
        />
    </AppLayout>
</template>
