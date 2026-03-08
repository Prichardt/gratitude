<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';

defineProps<{
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

            <div class="rounded-md border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-muted text-muted-foreground border-b border-border">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Name</th>
                                <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="permission in permissions" :key="permission.id" class="border-b border-border hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ permission.name }}</td>
                                <td class="px-6 py-4 text-right">
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
                                        class="ml-4 text-destructive hover:underline"
                                    >
                                        Delete
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="permissions.length === 0">
                                <td colspan="2" class="px-6 py-4 text-center text-muted-foreground">
                                    No permissions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
