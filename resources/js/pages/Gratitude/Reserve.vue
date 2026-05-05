<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/dashboard' },
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Gratitude Reserve', href: '/gratitude/reserve' },
];

const reserveColumns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'gratitudeNumber', label: 'Gratitude Number', sortable: true },
    { key: 'journey_id', label: 'Journey ID', sortable: true },
    { key: 'amount', label: 'Amount', sortable: true, align: 'right' as const },
    { key: 'date', label: 'Date', sortable: true },
    { key: 'description', label: 'Description', sortable: true },
    { key: 'status', label: 'Status', sortable: true, align: 'center' as const },
    { key: 'actions', label: 'Actions', align: 'center' as const, exportable: false },
];

const reserveList = ref<any[]>([]);
const loading = ref(true);

const fetchReserveData = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude/reserve');
        reserveList.value = response.data.reserves || [];
    } catch (error) {
        console.error("Failed to load reserve data", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchReserveData();
});

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
};

const getStatusBadge = (status: string) => {
    switch(status?.toLowerCase()) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'used': return 'bg-gray-100 text-gray-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-blue-100 text-blue-800';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Gratitude Reserve" />

        <div class="px-4 py-6 max-w-7xl mx-auto space-y-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Gratitude Reserve</h1>
                    <p class="mt-2 text-sm text-muted-foreground">View all reserved points, their journeys, and status.</p>
                </div>
            </div>

            <div class="bg-card rounded-xl border border-border shadow-sm p-4">
                <DataTable
                    title="Gratitude Reserve"
                    :columns="reserveColumns"
                    :rows="reserveList"
                    :busy="loading"
                >
                    <template #cell-date="{ row }">
                        {{ formatDate(row.date as string | null) }}
                    </template>
                    <template #cell-status="{ row }">
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', getStatusBadge(String(row.status || ''))]">
                            {{ row.status || 'Unknown' }}
                        </span>
                    </template>
                    <template #cell-actions="{ row }">
                        <Button variant="ghost" size="sm">View</Button>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
