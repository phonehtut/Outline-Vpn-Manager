<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as loginStore } from '@/routes/login';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(loginStore.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-muted/40 p-4">
        <Head title="Sign in" />

        <main class="flex flex-1 items-center justify-center">
            <div class="w-full max-w-sm">
                <div class="mb-6 text-center flex flex-col items-center gap-2">
                    <img src="/outline.webp" alt="Outline Manager" class="size-11 rounded-xl object-contain" />
                    <h1 class="text-xl font-bold tracking-tight">Outline Manager</h1>
                </div>

                <Card class="shadow-sm">
                    <CardHeader class="space-y-1 text-center">
                        <CardTitle class="text-lg">Sign in</CardTitle>
                        <CardDescription>Enter your credentials to access your account</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form class="flex flex-col gap-4" @submit.prevent="submit">
                        <!-- Email -->
                        <div class="flex flex-col gap-1.5">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                v-focus
                                type="email"
                                placeholder="name@example.com"
                                autocomplete="email"
                                :aria-invalid="!!form.errors.email"
                            />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="flex flex-col gap-1.5">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                :aria-invalid="!!form.errors.password"
                            />
                            <p v-if="form.errors.password" class="text-xs text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Remember me -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="remember"
                                :checked="form.remember"
                                @update:checked="(val: boolean) => form.remember = val"
                            />
                            <Label for="remember" class="text-xs font-normal text-muted-foreground cursor-pointer">
                                Remember me
                            </Label>
                        </div>

                        <Button type="submit" class="w-full mt-2" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            {{ form.processing ? 'Signing in…' : 'Sign in' }}
                        </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </main>

        <footer class="pt-4 text-center text-xs text-muted-foreground">
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
    </div>
</template>
