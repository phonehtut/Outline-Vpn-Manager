<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { AlertCircle, CheckCircle2, KeyRound, LayoutDashboard, LogOut, Server, Users } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as sellersIndex } from '@/routes/admin/sellers';
import { index as serversIndex } from '@/routes/admin/servers';
import { index as adminKeysIndex } from '@/routes/admin/keys';
import { logout as logoutRoute } from '@/routes/index';
import { dashboard as sellerDashboard } from '@/routes/seller';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value.role === 'admin');

function logout() {
    router.post(logoutRoute.url());
}
</script>

<template>
    <div class="flex min-h-screen bg-background text-foreground">
        <!-- Sidebar -->
        <aside class="flex w-64 shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground">
            <!-- App Header -->
            <div class="flex h-16 items-center gap-2.5 px-6">
                <img src="/outline.webp" alt="Outline Manager" class="size-9 rounded-lg object-contain" />
                <div class="flex flex-col">
                    <span class="text-sm font-semibold tracking-tight">Outline Manager</span>
                    <span class="text-[11px] text-muted-foreground">Admin & Seller Portal</span>
                </div>
            </div>

            <Separator class="bg-sidebar-border" />

            <!-- Nav Links -->
            <nav class="flex flex-1 flex-col gap-1 p-3">
                <template v-if="isAdmin">
                    <Link
                        :href="adminDashboard.url()"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            $page.url.startsWith('/admin/dashboard')
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold shadow-xs'
                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                        "
                    >
                        <LayoutDashboard class="size-4" />
                        Dashboard
                    </Link>
                    <Link
                        :href="sellersIndex.url()"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            $page.url.startsWith('/admin/sellers')
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold shadow-xs'
                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                        "
                    >
                        <Users class="size-4" />
                        Sellers
                    </Link>
                    <Link
                        :href="serversIndex.url()"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            $page.url.startsWith('/admin/servers')
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold shadow-xs'
                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                        "
                    >
                        <Server class="size-4" />
                        Servers
                    </Link>
                    <Link
                        :href="adminKeysIndex.url()"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            $page.url.startsWith('/admin/keys')
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold shadow-xs'
                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                        "
                    >
                        <KeyRound class="size-4" />
                        Access Keys
                    </Link>
                </template>

                <template v-else>
                    <Link
                        :href="sellerDashboard.url()"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            $page.url.startsWith('/seller/dashboard')
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold shadow-xs'
                                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'
                        "
                    >
                        <LayoutDashboard class="size-4" />
                        Dashboard
                    </Link>
                </template>
            </nav>

            <Separator class="bg-sidebar-border" />

            <!-- User profile footer -->
            <div class="flex items-center gap-3 p-4">
                <Avatar class="size-9 border border-sidebar-border">
                    <AvatarFallback class="bg-sidebar-accent font-semibold text-sidebar-accent-foreground text-xs uppercase">
                        {{ user.name.charAt(0) }}
                    </AvatarFallback>
                </Avatar>
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-xs font-medium">{{ user.name }}</span>
                    <Badge variant="outline" class="w-fit text-[10px] capitalize py-0 px-1.5 h-4 mt-0.5">
                        {{ user.role }}
                    </Badge>
                </div>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent"
                    title="Sign out"
                    @click="logout"
                >
                    <LogOut class="size-4" />
                </Button>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header bar -->
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-border bg-card px-8">
                <h1 class="text-base font-semibold tracking-tight">
                    <slot name="title">Outline Manager</slot>
                </h1>
                <div class="flex items-center gap-2">
                    <slot name="actions" />
                </div>
            </header>

            <main class="flex flex-1 flex-col p-8">
                <div class="flex-1">
                    <!-- Flash messages using shadcn Alert -->
                    <div v-if="$page.props.flash?.success" class="mb-6">
                        <Alert class="border-emerald-500/30 bg-emerald-50/50 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-200">
                            <CheckCircle2 class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <AlertTitle>Success</AlertTitle>
                            <AlertDescription>{{ $page.props.flash.success }}</AlertDescription>
                        </Alert>
                    </div>

                    <div v-if="$page.props.flash?.error" class="mb-6">
                        <Alert variant="destructive">
                            <AlertCircle class="size-4" />
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>{{ $page.props.flash.error }}</AlertDescription>
                        </Alert>
                    </div>

                    <slot />
                </div>

                <footer class="mt-8 border-t border-border pt-4 text-center text-xs text-muted-foreground">
                    Develop by
                    <a
                        href="https://yamm.newway-solutions.tech"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-foreground underline-offset-4 hover:underline"
                    >
                        Phone Htut Khaung
                    </a>
                </footer>
            </main>
        </div>

        <Toaster position="top-right" richColors />
    </div>
</template>
