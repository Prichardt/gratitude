<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Upload, RefreshCw } from 'lucide-vue-next';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/dashboard' },
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Accounts', href: '/gratitude/accounts' },
];

const columns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'gratitudeNumber', label: 'Gratitude Number', sortable: true },
    { key: 'level', label: 'Level', sortable: true },
    { key: 'totalPoints', label: 'Total Points', sortable: true, align: 'right' as const },
    { key: 'useablePoints', label: 'Usable Points', sortable: true, align: 'right' as const },
    { key: 'pending_points', label: 'Pending Points', sortable: true, align: 'right' as const },
    { key: 'totalExpiredPoints', label: 'Expired Points', sortable: true, align: 'right' as const },
    { key: 'last_activity_at', label: 'Last Activity', sortable: true },
    { key: 'status', label: 'Status', sortable: true, align: 'center' as const },
    { key: 'actions', label: 'Actions', align: 'center' as const },
];


const gratitudePoints = ref<any[]>([]);
const loading = ref(true);
const importing = ref(false);

const fetchAccountsData = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude');
        gratitudePoints.value = response.data.points || [];
    } catch (error) {
        console.error("Failed to load gratitude accounts", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAccountsData();
});

const handleApiImport = async () => {
    if (!window.confirm('Are you sure you want to pull the latest data from the API?')) return;
    
    importing.value = true;
    try {
        await axios.get('/internal-api/gratitude/migrate-data');
        await fetchAccountsData();
        window.alert('Data imported successfully!');
    } catch (e) {
        console.error(e);
        window.alert('Failed to import data from API.');
    } finally {
        importing.value = false;
    }
};

const getStatusBadge = (status: string) => {
    switch(status?.toLowerCase()) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'expired': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getLevelBadge = (level: string) => {
    switch(level?.toLowerCase()) {
        case 'jetsetter': return 'bg-amber-100 text-amber-800 border border-amber-300';
        case 'globetrotter': return 'bg-blue-100 text-blue-800 border border-blue-300';
        case 'wanderer': return 'bg-gray-100 text-gray-700 border border-gray-300';
        default: return 'bg-gray-100 text-gray-600 border border-gray-200';
    }
};

const formatNumber = (val: any) => {
    const n = Number(val || 0);
    return new Intl.NumberFormat('en-US').format(n);
};

const formatDate = (val: any) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getAccountRoute = (gratitudeNumber: any): string => {
    return route('gratitude.account.show', gratitudeNumber) as any as string;
};

const syncingRows = ref<Set<string>>(new Set());
const syncBalance = async (gratitudeNumber: string) => {
    syncingRows.value = new Set([...syncingRows.value, gratitudeNumber]);
    try {
        await axios.post(`/internal-api/gratitude/${gratitudeNumber}/sync-balance`);
        await fetchAccountsData();
    } catch (error) {
        console.error('Failed to sync balance', error);
    } finally {
        const next = new Set(syncingRows.value);
        next.delete(gratitudeNumber);
        syncingRows.value = next;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Gratitude Accounts" />

        <div class="px-4 py-6 space-y-8 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Gratitude Accounts</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Manage user gratitudes and point balances.</p>
                </div>
                <div class="flex space-x-3">
                    <Button variant="default" @click="handleApiImport" :disabled="importing">
                        <Upload class="mr-2 h-4 w-4" />
                        {{ importing ? 'Importing from API...' : 'Import Data from API' }}
                    </Button>
                </div>
            </div>

            <!-- Gratitudes Table -->
            <div class="bg-card rounded-xl border border-border shadow-sm p-3">
                <DataTable
                    title="Gratitude Accounts"
                    :columns="columns"
                    :rows="gratitudePoints"
                    :busy="loading"
                >
                    <template #cell-level="{ row }">
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', getLevelBadge(String(row.level || ''))]">
                            {{ row.level || '—' }}
                        </span>
                    </template>
                    <template #cell-totalPoints="{ row }">
                        {{ formatNumber(row.totalPoints) }}
                    </template>
                    <template #cell-useablePoints="{ row }">
                        {{ formatNumber(row.useablePoints) }}
                    </template>
                    <template #cell-pending_points="{ row }">
                        {{ formatNumber(row.pending_points) }}
                    </template>
                    <template #cell-totalExpiredPoints="{ row }">
                        {{ formatNumber(row.totalExpiredPoints) }}
                    </template>
                    <template #cell-last_activity_at="{ row }">
                        {{ formatDate(row.last_activity_at) }}
                    </template>
                    <template #cell-status="{ row }">
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', getStatusBadge(String(row.status || ''))]">
                            {{ row.status || 'Unknown' }}
                        </span>
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <Button
                                variant="ghost"
                                size="sm"
                                :disabled="syncingRows.has((row as any).gratitudeNumber)"
                                @click="syncBalance((row as any).gratitudeNumber)"
                                title="Sync balance"
                            >
                                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': syncingRows.has((row as any).gratitudeNumber) }" />
                            </Button>
                            <Link :href="getAccountRoute((row as any).gratitudeNumber)">
                                <Button variant="ghost" size="sm">View</Button>
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
