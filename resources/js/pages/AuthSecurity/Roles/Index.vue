<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';

import CreateRole from '@/components/security/CreateRole.vue';
import UpdateRole from '@/components/security/UpdateRole.vue';
import { ref } from 'vue';

defineProps<{
    roles: Array<{ id: number; name: string; permissions: Array<{ name: string }> }>;
    permissions: Record<string, Array<{ id: number; name: string; action: string }>>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Roles', href: '/roles' },
];

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const selectedRole = ref<any>(null);

const handleEditClick = (role: any) => {
    selectedRole.value = role;
    showUpdateModal.value = true;
};

const handleReload = () => {
    router.reload({ only: ['roles'] });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Roles" />

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight">Roles</h1>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                    Create Role
                </button>
            </div>

            <div class="rounded-md border bg-card text-card-foreground shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-muted text-muted-foreground border-b border-border">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Name</th>
                                <th scope="col" class="px-6 py-3 font-medium">Permissions</th>
                                <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="role in roles" :key="role.id" class="border-b border-border hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ role.name }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="perm in role.permissions"
                                            :key="perm.name"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80"
                                        >
                                            {{ perm.name }}
                                        </span>
                                        <span v-if="role.permissions.length === 0" class="text-muted-foreground italic text-xs">No permissions</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="handleEditClick(role)"
                                        class="text-primary hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <Link
                                        :href="`/roles/${role.id}`"
                                        method="delete"
                                        as="button"
                                        class="ml-4 text-destructive hover:underline"
                                    >
                                        Delete
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="roles.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-muted-foreground">
                                    No roles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateRole 
            v-model:open="showCreateModal" 
            :permissions="permissions" 
            @created="handleReload" 
        />

        <UpdateRole 
            v-model:open="showUpdateModal" 
            :role="selectedRole"
            :permissions="permissions" 
            @updated="handleReload" 
        />
    </AppLayout>
</template>
