<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AddEarnedPoints from '@/components/Gratitude/AddEarnedPoints.vue';
import AddBonusPoints from '@/components/Gratitude/AddBonusPoints.vue';
import CancelPoints from '@/components/Gratitude/CancelPoints.vue';
import ExpirePoints from '@/components/Gratitude/ExpirePoints.vue';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: {
        type: String,
        required: true
    }
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/dashboard' },
    { title: 'Gratitude Program', href: '/gratitude' },
    { title: `Gratitude #${props.gratitudeNumber}`, href: `/gratitude/${props.gratitudeNumber}` },
];

const data = ref<any>({
    gratitude: null,
    earned_points: [],
    bonus_points: [],
    cancellations: [],
    redemptions: [],
    next_level: null,
    points_to_next_level: 0,
    rolling_tier_points: 0
});

const fetchDetails = async () => {
    try {
        const response = await axios.get(`/internal-api/gratitude/${props.gratitudeNumber}`);
        data.value = response.data;
    } catch (error) {
        console.error("Failed to load gratitude details", error);
    }
};

onMounted(() => {
    fetchDetails();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Gratitude #${gratitudeNumber}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="flex items-center space-x-4 mb-8">
                <Link href="/gratitude">
                    <Button variant="outline" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div class="flex-auto">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Gratitude #{{ gratitudeNumber }}</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Detailed view of points and history.</p>
                </div>
                <div class="flex space-x-2">
                    <AddEarnedPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                    <AddBonusPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                    <CancelPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                    <ExpirePoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" v-if="data.gratitude">
                <div class="rounded-lg border border-border bg-card p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="text-sm font-medium text-muted-foreground">Current Level</div>
                        <div class="mt-2 text-3xl font-semibold text-foreground">{{ data.gratitude.level || 'Explorer' }}</div>
                    </div>
                    <div class="mt-4" v-if="data.next_level">
                        <p class="text-xs text-muted-foreground">
                            <span class="font-medium text-foreground">{{ data.points_to_next_level }}</span> points to {{ data.next_level }}
                        </p>
                    </div>
                    <div class="mt-4" v-else>
                        <p class="text-xs text-emerald-600 font-medium">Maximum Level Reached</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                    <div class="text-sm font-medium text-muted-foreground">Total Points</div>
                    <div class="mt-2 text-3xl font-semibold text-foreground">{{ data.gratitude.totalPoints || 0 }}</div>
                </div>
                <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                    <div class="text-sm font-medium text-muted-foreground">Usable Points</div>
                    <div class="mt-2 text-3xl font-semibold text-foreground text-primary">{{ data.gratitude.useablePoints || 0 }}</div>
                </div>
                <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                    <div class="text-sm font-medium text-muted-foreground">Status</div>
                    <div class="mt-2 text-xl font-semibold text-foreground uppercase pt-1">{{ data.gratitude.status || 'Active' }}</div>
                </div>
            </div>

            <!-- Tabs/Sections for Points -->
            <div class="space-y-8">
                <!-- Earned Points -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-foreground">Earned Points</h2>
                    <div class="overflow-hidden rounded-lg border border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Category</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="point in data.earned_points" :key="point.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground">{{ new Date(point.date).toLocaleDateString() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ point.category }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right font-medium text-green-600">+{{ point.points }}</td>
                                </tr>
                                <tr v-if="data.earned_points.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-muted-foreground">No earned points recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bonus Points -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-foreground">Bonus Points</h2>
                    <div class="overflow-hidden rounded-lg border border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="point in data.bonus_points" :key="point.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground">{{ new Date(point.date).toLocaleDateString() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ point.description || point.category }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right font-medium text-blue-600">+{{ point.points }}</td>
                                </tr>
                                <tr v-if="data.bonus_points.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-muted-foreground">No bonus points recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cancellations -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-foreground">Cancellations</h2>
                    <div class="overflow-hidden rounded-lg border border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Reason</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Points Taken</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="cancel in data.cancellations" :key="cancel.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground">{{ new Date(cancel.date).toLocaleDateString() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ cancel.cancellation_reason }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right font-medium text-red-600">-{{ cancel.cancellation_points }}</td>
                                </tr>
                                <tr v-if="data.cancellations.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-muted-foreground">No cancellations recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Redemptions -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-foreground">Redemptions</h2>
                    <div class="overflow-hidden rounded-lg border border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Reason</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Points Used</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="redemption in data.redemptions" :key="redemption.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground">{{ new Date(redemption.created_at).toLocaleDateString() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ redemption.reason }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right font-medium text-red-600">-{{ redemption.points }}</td>
                                </tr>
                                <tr v-if="!data.redemptions || data.redemptions.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-muted-foreground">No redemptions recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
