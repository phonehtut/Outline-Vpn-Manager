<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    expiresAt: string | null;
}>();

type BadgeState = 'none' | 'active' | 'expiring' | 'expired';

const state = computed<BadgeState>(() => {
    if (!props.expiresAt) return 'none';
    const expiry = new Date(props.expiresAt);
    const now = new Date();
    if (expiry <= now) return 'expired';
    const threeDays = new Date(now.getTime() + 3 * 24 * 60 * 60 * 1000);
    if (expiry <= threeDays) return 'expiring';
    return 'active';
});

const label = computed(() => {
    if (!props.expiresAt) return 'No expiry';
    const expiry = new Date(props.expiresAt);
    const formatted = expiry.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
    if (state.value === 'expired') return `Expired ${formatted}`;
    if (state.value === 'expiring') return `Expires ${formatted}`;
    return `Until ${formatted}`;
});

const variant = computed(() => {
    switch (state.value) {
        case 'expired':
            return 'destructive';
        case 'expiring':
            return 'outline';
        case 'active':
            return 'secondary';
        case 'none':
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant" class="gap-1.5 font-normal">
        <span
            class="size-1.5 rounded-full"
            :class="{
                'bg-muted-foreground': state === 'none',
                'bg-emerald-500': state === 'active',
                'bg-amber-500': state === 'expiring',
                'bg-destructive-foreground': state === 'expired',
            }"
        />
        {{ label }}
    </Badge>
</template>
