<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/dashboard' },
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Program Level Benefits', href: '/gratitude/program-level-benefits' },
];

const benefitColumns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'description', label: 'Description', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true, align: 'center' as const },
    { key: 'actions', label: 'Actions', align: 'center' as const },
];

const benefitsList = ref<any[]>([]);
const loading = ref(true);

const fetchBenefitsData = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude');
        benefitsList.value = response.data.benefits || [];
    } catch (error) {
        console.error("Failed to load benefits data", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchBenefitsData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Program Level Benefits" />

        <div class="px-4 py-6 max-w-7xl mx-auto space-y-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Program Level Benefits</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Manage gratitude benefits associated with program levels.</p>
                </div>
                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <Button variant="default">
                        <Plus class="mr-2 h-4 w-4" />
                        Add New Level Benefit
                    </Button>
                </div>
            </div>

            <!-- Benefits Table -->
            <div class="bg-card rounded-xl border border-border shadow-sm p-4">
                <DataTable
                    title="Program Benefits"
                    :columns="benefitColumns"
                    :rows="benefitsList"
                    :busy="loading"
                >
                     <template #cell-is_active="{ row }">
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', row.is_active === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                            {{ row.is_active }}
                        </span>
                    </template>
                    <template #cell-actions="{ row }">
                        <Button variant="ghost" size="sm">Edit</Button>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
