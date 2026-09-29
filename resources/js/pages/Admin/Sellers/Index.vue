<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Loader2, Pencil, Trash2, UserPlus } from '@lucide/vue';
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
import { destroy as sellersDestroy, edit as sellersEdit, store as sellersStore } from '@/routes/admin/sellers';
import type { Seller } from '@/types';

defineProps<{
    sellers: Seller[];
}>();

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const sellerToDelete = ref<Seller | null>(null);
const isDeleteDialogOpen = ref(false);

function createSeller() {
    createForm.post(sellersStore.url(), {
        onSuccess: () => createForm.reset(),
    });
}

function requestDeleteSeller(seller: Seller) {
    sellerToDelete.value = seller;
    isDeleteDialogOpen.value = true;
}

function deleteSeller() {
    if (!sellerToDelete.value) return;

    router.delete(sellersDestroy.url(sellerToDelete.value.id), {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            sellerToDelete.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Sellers" />

        <template #title>Seller Management</template>

        <div class="grid gap-8 lg:grid-cols-5">
            <!-- Sellers Table Card -->
            <div class="lg:col-span-3">
                <Card class="shadow-xs overflow-hidden">
                    <CardHeader class="pb-3 border-b">
                        <CardTitle class="text-base font-semibold">Registered Sellers</CardTitle>
                        <CardDescription>Sellers can only manage access keys on their assigned servers.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Assigned Servers</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="sellers.length === 0" :colspan="4">
                                    No sellers registered yet. Add one using the form.
                                </TableEmpty>
                                <TableRow v-for="seller in sellers" :key="seller.id">
                                    <TableCell class="font-medium">{{ seller.name }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{ seller.email }}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" class="font-normal text-xs">
                                            {{ seller.servers?.length ?? 0 }} server{{ (seller.servers?.length ?? 0) !== 1 ? 's' : '' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button as-child variant="ghost" size="sm" class="h-8 gap-1">
                                                <Link :href="sellersEdit.url(seller.id)">
                                                    <Pencil class="size-3.5" />
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                title="Delete seller"
                                                @click="requestDeleteSeller(seller)"
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

            <!-- Create Seller Card -->
            <div class="lg:col-span-2">
                <Card class="shadow-xs">
                    <CardHeader class="pb-3 border-b">
                        <CardTitle class="text-base font-semibold flex items-center gap-2">
                            <UserPlus class="size-4" />
                            Add Seller
                        </CardTitle>
                        <CardDescription>Create a new seller account with access credentials.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <form class="flex flex-col gap-4" @submit.prevent="createSeller">
                            <div class="flex flex-col gap-1.5">
                                <Label for="name">Full Name</Label>
                                <Input
                                    id="name"
                                    v-model="createForm.name"
                                    placeholder="Seller Name"
                                    :aria-invalid="!!createForm.errors.name"
                                />
                                <p v-if="createForm.errors.name" class="text-xs text-destructive">
                                    {{ createForm.errors.name }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="seller@example.com"
                                    :aria-invalid="!!createForm.errors.email"
                                />
                                <p v-if="createForm.errors.email" class="text-xs text-destructive">
                                    {{ createForm.errors.email }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    placeholder="••••••••"
                                    :aria-invalid="!!createForm.errors.password"
                                />
                                <p v-if="createForm.errors.password" class="text-xs text-destructive">
                                    {{ createForm.errors.password }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="password_confirmation">Confirm Password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                />
                            </div>

                            <Button type="submit" class="w-full mt-2" :disabled="createForm.processing">
                                <Loader2 v-if="createForm.processing" class="mr-2 size-4 animate-spin" />
                                {{ createForm.processing ? 'Creating…' : 'Create Seller' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
        <ConfirmActionDialog
            v-model:open="isDeleteDialogOpen"
            :title="`Delete ${sellerToDelete?.name ?? 'seller'}?`"
            :description="sellerToDelete ? `Their local key records will also be deleted. Those keys may remain active on their Outline servers.` : ''"
            @confirm="deleteSeller"
        />
    </AppLayout>
</template>
