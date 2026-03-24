<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-vue-next';
import UpdateProgramLevelBenefit from '@/components/Gratitude/UpdateProgramLevelBenefit.vue';
import AddProgramLevelBenefit from '@/components/Gratitude/AddProgramLevelBenefit.vue';
import ViewProgramLevelBenefit from '@/components/Gratitude/ViewProgramLevelBenefit.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Program Level Benefits', href: '/gratitude/program-level-benefits' },
];

const gridData = ref<any>({ levels: [], grid: [] });
const loading = ref(true);

const fetchProgramBenefits = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude/program-benefits');
        gridData.value = response.data;
    } catch (error) {
        console.error("Failed to load gratitude program benefits", error);
    } finally {
        loading.value = false;
    }
};

const deleteBenefit = async (id: number) => {
    if (confirm('Are you sure you want to delete this benefit? This will remove all its level assignments.')) {
        try {
            await axios.delete(`/internal-api/gratitude/benefits/${id}`);
            fetchProgramBenefits();
        } catch (error) {
            console.error("Failed to delete program level benefit", error);
        }
    }
};

onMounted(() => {
    fetchProgramBenefits();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Program Level Benefits" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Program Level Benefits</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Manage which benefits are assigned to each tier level and specify tier-specific values.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <AddProgramLevelBenefit @saved="fetchProgramBenefits" />
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Base Benefit</th>
                            <th v-for="level in gridData.levels" :key="level.id" class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider bg-muted/80 border-l border-border/50">
                                {{ level.name }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card" v-if="!loading">
                        <tr v-for="row in gridData.grid" :key="row.id" class="hover:bg-muted/10 transition-colors">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-foreground">
                                {{ row.name }}
                                <div class="text-xs text-muted-foreground truncate max-w-xs" v-if="row.description">{{ row.description }}</div>
                            </td>
                            <td v-for="level in gridData.levels" :key="level.id" class="whitespace-nowrap px-6 py-4 text-center border-l border-border/50">
                                <template v-if="row.levels[level.id]?.has_benefit">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                            {{ row.levels[level.id].value || 'Enabled' }}
                                        </span>
                                        <span class="text-[10px] text-muted-foreground mt-1" v-if="row.levels[level.id].description">
                                            {{ row.levels[level.id].description }}
                                        </span>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground/30 text-sm">-</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <ViewProgramLevelBenefit :benefit="row" :levels="gridData.levels" />
                                    <UpdateProgramLevelBenefit :benefit="row" :levels="gridData.levels" @saved="fetchProgramBenefits" />
                                    <Button variant="ghost" size="icon" @click="deleteBenefit(row.id)" class="text-destructive h-8 w-8 hover:bg-destructive/10 hover:text-destructive">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="gridData.grid.length === 0">
                            <td :colspan="gridData.levels.length + 2" class="px-6 py-4 text-center text-muted-foreground">No benefits established yet. Create Base Benefits first.</td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="100%" class="px-6 py-8 text-center text-muted-foreground">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
