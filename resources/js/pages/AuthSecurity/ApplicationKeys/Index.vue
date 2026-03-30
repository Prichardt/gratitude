<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import CreateApplicationKey from '@/components/security/CreateApplicationKey.vue';
import UpdateApplicationKey from '@/components/security/UpdateApplicationKey.vue';
import ViewApplicationKey from '@/components/security/ViewApplicationKey.vue';

type AppKey = {
    id: number;
    name: string;
    url: string;
    status: string;
    token: string;
    roles: Array<{ name: string }>;
    created_at?: string;
    updated_at?: string;
};

defineProps<{
    application_keys: AppKey[];
    roles: Array<{ id: number; name: string }>;
}>();

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const showViewModal = ref(false);
const selectedKey = ref<AppKey | null>(null);

const handleEditClick = (appKey: AppKey) => {
    selectedKey.value = appKey;
    showUpdateModal.value = true;
};

const handleViewClick = (appKey: AppKey) => {
    selectedKey.value = appKey;
    showViewModal.value = true;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Application Keys', href: '/application-keys' },
];

const page = usePage();
const modalToken = ref<string | null>(null);
const plainTextToken = computed(() => modalToken.value || (page.props.flash as any)?.plainTextToken);

const handleCreated = (response: any) => {
    if (response?.plainTextToken) {
        modalToken.value = response.plainTextToken;
    }
    router.reload({ only: ['application_keys'] });
};

const handleUpdated = () => {
    router.reload({ only: ['application_keys'] });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Application Keys" />

        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight">Application Keys</h1>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                >
                    Create Key
                </button>
            </div>

            <div v-if="plainTextToken" class="rounded-md border border-green-200 bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Application Key Generated Successfully</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Here is your new API token. <strong>Please copy it now. For your security, it won't be shown again.</strong></p>
                            <div class="mt-3 font-mono bg-white p-3 rounded border border-green-200 break-all select-all">
                                {{ plainTextToken }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-md border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-muted text-muted-foreground border-b border-border">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">Name</th>
                                <th scope="col" class="px-6 py-3 font-medium">URL</th>
                                <th scope="col" class="px-6 py-3 font-medium">Status</th>
                                <th scope="col" class="px-6 py-3 font-medium">Roles</th>
                                <th scope="col" class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="app in application_keys"
                                :key="app.id"
                                class="border-b border-border hover:bg-muted/50 transition-colors"
                                :class="app.status === 'inactive' ? 'opacity-60' : ''"
                            >
                                <td class="px-6 py-4 font-medium">{{ app.name }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ app.url || 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                        :class="app.status === 'active'
                                            ? 'bg-green-100 text-green-800 border-green-200'
                                            : 'bg-red-100 text-red-800 border-red-200'"
                                    >
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in app.roles"
                                            :key="role.name"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="app.roles.length === 0" class="text-muted-foreground italic text-xs">No roles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <button
                                        @click="handleViewClick(app)"
                                        class="text-primary hover:underline text-sm"
                                    >
                                        View
                                    </button>
                                    <button
                                        @click="handleEditClick(app)"
                                        class="text-muted-foreground hover:underline text-sm"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="application_keys.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                    No application keys found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateApplicationKey
            v-model:open="showCreateModal"
            :roles="roles"
            @created="handleCreated"
        />

        <UpdateApplicationKey
            v-model:open="showUpdateModal"
            :application_key="selectedKey"
            :roles="roles"
            @updated="handleUpdated"
        />

        <ViewApplicationKey
            v-model:open="showViewModal"
            :application_key="selectedKey"
            @updated="handleUpdated"
        />
    </AppLayout>
</template>
