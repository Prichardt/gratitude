<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

const props = defineProps<{
    existing_models: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auth & Security', href: '/roles' },
    { title: 'Permissions', href: '/permissions' },
    { title: 'Create', href: '/permissions/create' },
];

const STANDARD_ACTIONS = ['view', 'create', 'update', 'delete'];

const form = useForm({
    model: '',
    actions: [] as string[],
});

const customAction = ref('');
const showSuggestions = ref(false);

const filteredSuggestions = computed(() => {
    if (!form.model) return [];
    return props.existing_models.filter(m =>
        m.toLowerCase().includes(form.model.toLowerCase()) && m !== form.model
    );
});

const selectSuggestion = (model: string) => {
    form.model = model;
    showSuggestions.value = false;
};

const addCustomAction = () => {
    const action = customAction.value.trim().toLowerCase().replace(/\s+/g, '-');
    if (action && !form.actions.includes(action)) {
        form.actions.push(action);
    }
    customAction.value = '';
};

const removeAction = (action: string) => {
    form.actions = form.actions.filter(a => a !== action);
};

const toggleAll = () => {
    if (STANDARD_ACTIONS.every(a => form.actions.includes(a))) {
        form.actions = form.actions.filter(a => !STANDARD_ACTIONS.includes(a));
    } else {
        const toAdd = STANDARD_ACTIONS.filter(a => !form.actions.includes(a));
        form.actions.push(...toAdd);
    }
};

const allStandardSelected = computed(() =>
    STANDARD_ACTIONS.every(a => form.actions.includes(a))
);

const preview = computed(() => {
    if (!form.model || form.actions.length === 0) return [];
    return form.actions.map(a => `${form.model.toLowerCase()}:${a}`);
});

const submit = () => {
    form.post('/permissions');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Permission" />

        <div class="p-6 max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">Create Permissions</h1>
                <Link href="/permissions" class="text-sm text-muted-foreground hover:underline">
                    Back to Permissions
                </Link>
            </div>

            <div class="rounded-md border bg-card shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Model name -->
                    <div class="space-y-2">
                        <label for="model" class="text-sm font-medium leading-none">
                            Model / Resource Name
                        </label>
                        <p class="text-xs text-muted-foreground">
                            Use lowercase with hyphens. e.g. <code class="bg-muted px-1 rounded">product</code>, <code class="bg-muted px-1 rounded">blog-post</code>, <code class="bg-muted px-1 rounded">gratitude.earned</code>
                        </p>
                        <div class="relative">
                            <input
                                id="model"
                                v-model="form.model"
                                type="text"
                                autocomplete="off"
                                class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                placeholder="e.g. product"
                                @focus="showSuggestions = true"
                                @blur="showSuggestions = false"
                                autofocus
                            />
                            <!-- Autocomplete suggestions -->
                            <div
                                v-if="showSuggestions && filteredSuggestions.length > 0"
                                class="absolute z-10 top-full mt-1 w-full rounded-md border border-border bg-card shadow-md"
                            >
                                <button
                                    v-for="s in filteredSuggestions"
                                    :key="s"
                                    type="button"
                                    @mousedown="selectSuggestion(s)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-muted transition-colors font-mono"
                                >
                                    {{ s }}
                                </button>
                            </div>
                        </div>
                        <InputError :message="form.errors.model" class="mt-1" />
                    </div>

                    <!-- Standard actions -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium leading-none">Actions</label>
                            <button
                                type="button"
                                @click="toggleAll"
                                class="text-xs text-primary hover:underline"
                            >
                                {{ allStandardSelected ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label
                                v-for="action in STANDARD_ACTIONS"
                                :key="action"
                                class="flex items-center gap-2 rounded-md border px-3 py-2.5 cursor-pointer transition-colors"
                                :class="form.actions.includes(action)
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-border hover:bg-muted/50'"
                            >
                                <input
                                    type="checkbox"
                                    :value="action"
                                    v-model="form.actions"
                                    class="h-4 w-4 rounded-sm border border-primary shrink-0"
                                />
                                <span class="text-sm font-medium capitalize">{{ action }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.actions" class="mt-1" />
                    </div>

                    <!-- Custom action -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none">Custom Action (optional)</label>
                        <div class="flex gap-2">
                            <input
                                v-model="customAction"
                                type="text"
                                class="flex h-9 flex-1 rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                placeholder="e.g. approve, export"
                                @keydown.enter.prevent="addCustomAction"
                            />
                            <button
                                type="button"
                                @click="addCustomAction"
                                class="inline-flex items-center justify-center rounded-md border border-input bg-background hover:bg-muted text-sm h-9 px-3"
                            >
                                Add
                            </button>
                        </div>
                        <!-- Extra action chips -->
                        <div v-if="form.actions.some(a => !STANDARD_ACTIONS.includes(a))" class="flex flex-wrap gap-1.5 mt-1">
                            <span
                                v-for="action in form.actions.filter(a => !STANDARD_ACTIONS.includes(a))"
                                :key="action"
                                class="inline-flex items-center gap-1 rounded-full border border-primary/30 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                            >
                                {{ action }}
                                <button type="button" @click="removeAction(action)" class="hover:text-destructive">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div v-if="preview.length > 0" class="rounded-md border border-border bg-muted/30 p-4 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Permissions to be created</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="p in preview"
                                :key="p"
                                class="inline-flex items-center rounded-md border border-border bg-background px-2.5 py-0.5 font-mono text-xs text-foreground"
                            >
                                {{ p }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <Link href="/permissions" class="text-sm font-medium text-muted-foreground hover:underline">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.model || form.actions.length === 0"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create {{ form.actions.length > 1 ? `${form.actions.length} Permissions` : 'Permission' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
