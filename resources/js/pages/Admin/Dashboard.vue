<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, ClockAlert, KeyRound, Server, Users } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as sellersIndex } from '@/routes/admin/sellers';
import { index as serversIndex } from '@/routes/admin/servers';
import type { AdminStats } from '@/types';

defineProps<{
    stats: AdminStats;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Admin Dashboard" />

        <template #title>Dashboard Overview</template>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Sellers Card -->
            <Card class="shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Sellers</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ stats.seller_count }}</div>
                    <div class="mt-3">
                        <Button as-child variant="outline" size="sm" class="h-7 text-xs gap-1">
                            <Link :href="sellersIndex.url()">
                                Manage Sellers
                                <ArrowUpRight class="size-3" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Servers Card -->
            <Card class="shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Active Servers</CardTitle>
                    <Server class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ stats.server_count }}</div>
                    <div class="mt-3">
                        <Button as-child variant="outline" size="sm" class="h-7 text-xs gap-1">
                            <Link :href="serversIndex.url()">
                                Manage Servers
                                <ArrowUpRight class="size-3" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Total Keys Card -->
            <Card class="shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Keys</CardTitle>
                    <KeyRound class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ stats.key_count }}</div>
                    <p class="mt-3 text-xs text-muted-foreground">Managed across all servers</p>
                </CardContent>
            </Card>

            <!-- Expired Keys Card -->
            <Card class="shadow-xs border-destructive/20 bg-destructive/5">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-destructive">Expired Keys</CardTitle>
                    <ClockAlert class="size-4 text-destructive" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-destructive">{{ stats.expired_key_count }}</div>
                    <p class="mt-3 text-xs text-muted-foreground">Automatically pruned daily</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
