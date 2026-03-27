<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';

type PermissionItem = { id: number; name: string; action: string };

defineProps<{
    grouped_permissions: Record<string, PermissionItem[]>;
    permissions: Array<{ id: number; name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Permissions', href: '/permissions' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Permissions" />

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight">Permissions</h1>
                <Link
                    href="/permissions/create"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                    Create Permission
                </Link>
            </div>

            <!-- Grouped permission cards -->
            <div class="space-y-4">
                <div
                    v-for="(perms, group) in grouped_permissions"
                    :key="group"
                    class="rounded-md border bg-card shadow-sm overflow-hidden"
                >
                    <div class="px-5 py-3 bg-muted/40 border-b border-border flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ group }}</span>
                        <span class="text-xs text-muted-foreground/60">({{ perms.length }})</span>
                    </div>
                    <div class="divide-y divide-border">
                        <div
                            v-for="permission in perms"
                            :key="permission.id"
                            class="flex items-center justify-between px-5 py-3 hover:bg-muted/30 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary border border-primary/20 capitalize">
                                    {{ permission.action }}
                                </span>
                                <span class="text-sm text-muted-foreground font-mono">{{ permission.name }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <Link
                                    :href="`/permissions/${permission.id}/edit`"
                                    class="text-primary hover:underline"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="`/permissions/${permission.id}`"
                                    method="delete"
                                    as="button"
                                    class="text-destructive hover:underline"
                                >
                                    Delete
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="Object.keys(grouped_permissions).length === 0" class="rounded-md border bg-card shadow-sm p-8 text-center text-sm text-muted-foreground">
                    No permissions found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
