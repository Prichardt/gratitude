<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AddLevel from '@/components/Gratitude/AddLevel.vue';
import UpdateLevel from '@/components/Gratitude/UpdateLevel.vue';
import ViewLevel from '@/components/Gratitude/ViewLevel.vue';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-vue-next';

// ...
const deleteLevel = async (id: number) => {
    if (confirm('Are you sure you want to delete this level?')) {
        try {
            await axios.delete(`/internal-api/gratitude/levels/${id}`);
            fetchLevels();
        } catch (error) {
            console.error("Failed to delete level", error);
        }
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: 'Levels', href: '/gratitude/levels' },
];

const levels = ref<any[]>([]);

const fetchLevels = async () => {
    try {
        const response = await axios.get('/internal-api/gratitude/levels');
        levels.value = response.data;
    } catch (error) {
        console.error("Failed to load gratitude levels", error);
    }
};

onMounted(() => {
    fetchLevels();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Gratitude Levels" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Gratitude Levels</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Manage the different tiers and point thresholds for the gratitude program.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <AddLevel @saved="fetchLevels" />
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Min Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Max Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Earned Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Bonus Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="level in levels" :key="level.id">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-foreground">{{ level.name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">{{ level.min_points }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">{{ level.max_points || '∞' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">{{ level.earned_expire_days || 730 }} days</td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">{{ level.bonus_expire_days || 730 }} days</td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                <span v-if="level.status" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                                <span v-else class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Inactive</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <ViewLevel :level="level" />
                                    <UpdateLevel :level="level" @saved="fetchLevels" />
                                    <Button variant="ghost" size="icon" @click="deleteLevel(level.id)" class="text-destructive h-8 w-8 hover:bg-destructive/10 hover:text-destructive">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="levels.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-muted-foreground">No levels established yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
