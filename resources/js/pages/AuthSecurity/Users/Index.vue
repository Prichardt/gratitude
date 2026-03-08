<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';
import CreateUser from '@/components/users/CreateUser.vue';
import UpdateUser from '@/components/users/UpdateUser.vue';

defineProps<{
    users: Array<{ id: number; name: string; first_name: string; last_name: string; email: string; status: string; roles: Array<{ name: string }> }>;
    roles: Array<{ id: number; name: string }>;
}>();

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const selectedUser = ref<any>(null);

const handleEditClick = (user: any) => {
    selectedUser.value = user;
    showUpdateModal.value = true;
};

const handleCreated = () => {
    router.reload({ only: ['users'] });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Users', href: '/users' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight">Users</h1>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                    Create User
                </button>
            </div>

            <div class="rounded-md border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-muted text-muted-foreground border-b border-border">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Name</th>
                                <th scope="col" class="px-6 py-3 font-medium">Email</th>
                                <th scope="col" class="px-6 py-3 font-medium">Status</th>
                                <th scope="col" class="px-6 py-3 font-medium">Roles</th>
                                <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="border-b border-border hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 font-medium">
                                    <div class="flex flex-col">
                                        <span>{{ user.first_name }} {{ user.last_name }}</span>
                                        <span class="text-xs text-muted-foreground" v-if="user.name !== `${user.first_name} ${user.last_name}` && user.name">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ user.email }}</td>
                                <td class="px-6 py-4">
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                        :class="user.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.name"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-muted-foreground italic text-xs">No roles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="handleEditClick(user)"
                                        class="text-primary hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <Link
                                        :href="`/users/${user.id}`"
                                        method="delete"
                                        as="button"
                                        class="ml-4 text-destructive hover:underline"
                                    >
                                        Delete
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-muted-foreground">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateUser 
            v-model:open="showCreateModal" 
            :roles="roles" 
            @created="handleCreated" 
        />

        <UpdateUser 
            v-model:open="showUpdateModal" 
            :user="selectedUser"
            :roles="roles" 
            @updated="handleCreated" 
        />
    </AppLayout>
</template>
