<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AddBenefits from '@/components/Gratitude/AddBenefits.vue';
import UpdateBenefits from '@/components/Gratitude/UpdateBenefits.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Benefits', href: '/gratitude/benefits' },
];

const gridData = ref<any>({ levels: [], grid: [] });

const fetchBenefits = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude/benefits');
        gridData.value = response.data;
    } catch (error) {
        console.error("Failed to load gratitude benefits", error);
    }
};

onMounted(() => {
    fetchBenefits();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Gratitude Benefits" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Gratitude Benefits</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Manage the benefits associated with each tier of the program.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <AddBenefits @saved="fetchBenefits" :levels="gridData.levels" />
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Benefit</th>
                            <th v-for="level in gridData.levels" :key="level.id" class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ level.name }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="row in gridData.grid" :key="row.id">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-foreground">
                                {{ row.name }}
                                <div class="text-xs text-muted-foreground truncate max-w-xs" v-if="row.description">{{ row.description }}</div>
                            </td>
                            <td v-for="level in gridData.levels" :key="level.id" class="whitespace-nowrap px-6 py-4 text-center text-muted-foreground">
                                <span v-if="row.levels[level.id]?.has_benefit">
                                    {{ row.levels[level.id].value || '✓' }}
                                </span>
                                <span v-else class="text-muted-foreground/30">-</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <UpdateBenefits :benefit="row" :levels="gridData.levels" @saved="fetchBenefits" />
                            </td>
                        </tr>
                        <tr v-if="gridData.grid.length === 0">
                            <td :colspan="gridData.levels.length + 2" class="px-6 py-4 text-center text-muted-foreground">No benefits established yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
