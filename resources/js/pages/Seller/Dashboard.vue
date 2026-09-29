<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, ChevronRight, ClockAlert, KeyRound, Server as ServerIcon } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as keysIndex } from '@/routes/seller/servers/keys';
import type { KeyStats, Server } from '@/types';

defineProps<{
    servers: Server[];
    keyStats: KeyStats;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Seller Dashboard" />

        <template #title>My Dashboard</template>

        <!-- Stats Overview Cards -->
        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <Card class="shadow-xs">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Access Keys</CardTitle>
                    <KeyRound class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ keyStats.total }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Created by your account</p>
                </CardContent>
            </Card>

            <Card class="shadow-xs border-emerald-500/20 bg-emerald-500/5">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-emerald-800 dark:text-emerald-400">Active Keys</CardTitle>
                    <CheckCircle2 class="size-4 text-emerald-600 dark:text-emerald-400" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ keyStats.active }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Currently valid and active</p>
                </CardContent>
            </Card>

            <Card class="shadow-xs border-destructive/20 bg-destructive/5">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-destructive">Expired Keys</CardTitle>
                    <ClockAlert class="size-4 text-destructive" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-destructive">{{ keyStats.expired }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Past expiry threshold</p>
                </CardContent>
            </Card>
        </div>

        <!-- Assigned Servers Section -->
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold tracking-tight">Assigned Servers</h2>
                    <p class="text-xs text-muted-foreground">Select a server to manage, create, and sync its access keys.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-if="servers.length === 0"
                    class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-12 text-center"
                >
                    <ServerIcon class="size-8 text-muted-foreground mb-2" />
                    <p class="text-sm font-medium">No servers assigned yet</p>
                    <p class="text-xs text-muted-foreground mt-1">Please contact your administrator to assign an Outline server to your account.</p>
                </div>

                <Link
                    v-for="server in servers"
                    :key="server.id"
                    :href="keysIndex.url(server.id)"
                    class="group"
                >
                    <Card class="h-full transition-all duration-200 hover:border-primary/50 hover:shadow-md cursor-pointer">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <ServerIcon class="size-4" />
                            </div>
                            <ChevronRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                        </CardHeader>
                        <CardContent>
                            <h3 class="font-semibold text-base tracking-tight group-hover:text-primary transition-colors">
                                {{ server.name }}
                            </h3>
                            <div class="mt-3 flex items-center justify-between">
                                <Badge variant="secondary" class="font-normal text-xs">
                                    {{ server.access_keys_count ?? 0 }} key{{ (server.access_keys_count ?? 0) !== 1 ? 's' : '' }}
                                </Badge>
                                <span class="text-[11px] text-muted-foreground font-mono">
                                    Manage Keys →
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
