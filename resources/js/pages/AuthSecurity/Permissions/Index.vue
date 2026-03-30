<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

type PermissionItem = { id: number; name: string; action: string };

defineProps<{
    grouped_permissions: Record<string, PermissionItem[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Permissions', href: '/permissions' },
];

const expanded = ref<string | null>(null);

const toggleExpand = (group: string) => {
    expanded.value = expanded.value === group ? null : group;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Permissions" />

        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight">Permissions</h1>
                <Link
                    href="/permissions/create"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                    Create Permissions
                </Link>
            </div>

            <div class="rounded-md border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted text-muted-foreground border-b border-border">
                        <tr>
                            <th class="px-6 py-3 font-medium">Model / Resource</th>
                            <th class="px-6 py-3 font-medium">Actions</th>
                            <th class="px-6 py-3 font-medium text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(perms, group) in grouped_permissions" :key="group">
                            <!-- Summary row -->
                            <tr
                                class="border-b border-border hover:bg-muted/30 transition-colors cursor-pointer"
                                @click="toggleExpand(group as string)"
                            >
                                <td class="px-6 py-3 font-semibold align-middle whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <svg
                                            class="w-4 h-4 text-muted-foreground transition-transform"
                                            :class="expanded === group ? 'rotate-90' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        {{ group }}
                                        <span class="text-xs font-normal text-muted-foreground">({{ perms.length }})</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 align-middle">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="perm in perms"
                                            :key="perm.id"
                                            class="inline-flex items-center rounded-md bg-primary/10 border border-primary/20 px-2 py-0.5 text-xs font-semibold text-primary capitalize"
                                        >
                                            {{ perm.action }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right align-middle text-xs text-muted-foreground">
                                    {{ expanded === group ? 'Collapse' : 'Expand' }}
                                </td>
                            </tr>

                            <!-- Expanded detail rows -->
                            <tr
                                v-if="expanded === group"
                                v-for="perm in perms"
                                :key="perm.id"
                                class="border-b border-border bg-muted/20"
                            >
                                <td class="pl-14 pr-6 py-2 text-xs text-muted-foreground font-mono">
                                    {{ perm.name }}
                                </td>
                                <td class="px-6 py-2">
                                    <span class="inline-flex items-center rounded-md bg-primary/10 border border-primary/20 px-2 py-0.5 text-xs font-semibold text-primary capitalize">
                                        {{ perm.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-2 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <Link
                                            :href="`/permissions/${perm.id}/edit`"
                                            class="text-xs text-primary hover:underline"
                                            @click.stop
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            :href="`/permissions/${perm.id}`"
                                            method="delete"
                                            as="button"
                                            class="text-xs text-destructive hover:underline"
                                            @click.stop
                                        >
                                            Delete
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="Object.keys(grouped_permissions).length === 0">
                            <td colspan="3" class="px-6 py-8 text-center text-muted-foreground">
                                No permissions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
