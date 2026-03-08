<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Permissions', href: '/permissions' },
    { title: 'Create', href: '/permissions/create' },
];

const form = useForm({
    name: '',
});

const submit = () => {
    form.post('/permissions');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Permission" />

        <div class="p-6 max-w-xl mx-auto space-y-6">
            <h1 class="text-2xl font-bold tracking-tight">Create Permission</h1>

            <div class="rounded-md border bg-card shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label for="name" class="text-sm font-medium leading-none">Permission Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="e.g. edit articles"
                            autofocus
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <Link href="/permissions" class="text-sm font-medium text-muted-foreground hover:underline">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Create Permission</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
