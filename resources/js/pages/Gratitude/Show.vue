<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import AddEarnedPoints from '@/components/Gratitude/AddEarnedPoints.vue';
import AddBonusPoints from '@/components/Gratitude/AddBonusPoints.vue';
import CancelPoints from '@/components/Gratitude/CancelPoints.vue';
import ExpirePoints from '@/components/Gratitude/ExpirePoints.vue';
import { Button } from '@/components/ui/button';
import { ArrowLeft, ChevronDown, ChevronRight } from 'lucide-vue-next';

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
        const response = await axios.get(`/internal-api/gratitude/account/show/${props.gratitudeNumber}`);
        data.value = response.data;
    } catch (error) {
        console.error("Failed to load gratitude details", error);
    }
};

onMounted(() => {
    fetchDetails();
});

// Computed Values for Summary
const tierPointsSum = computed(() => {
    return data.value.earned_points.reduce((sum: number, p: any) => sum + Number(p.points || 0), 0);
});
const bonusPointsSum = computed(() => {
    return data.value.bonus_points.reduce((sum: number, p: any) => sum + Number(p.points || 0), 0);
});
const cancellationsSum = computed(() => {
    return data.value.cancellations.reduce((sum: number, p: any) => sum + Number(p.cancellation_points || 0), 0);
});
const redemptionsSum = computed(() => {
    return data.value.redemptions.reduce((sum: number, p: any) => sum + Number(p.points || 0), 0);
});
const totalPoints = computed(() => {
    return data.value.gratitude?.totalPoints || 0;
});
const usablePoints = computed(() => {
    return data.value.gratitude?.useablePoints || 0;
});

// Combine Tier Points + Cancellations
const combinedTierPoints = computed(() => {
    const earned = data.value.earned_points.map((p: any) => ({
        ...p,
        rowType: 'earned',
        displayDate: p.date,
        displayProject: p.category, 
        displayPoints: p.points,
        displayExpiresOn: p.expires_at || '',
        displayDescription: p.description || 'Tier Points earned',
    }));

    const cancels = data.value.cancellations.map((c: any) => ({
        ...c,
        rowType: 'cancel',
        displayDate: '', 
        displayProject: '', 
        displayPoints: -c.cancellation_points,
        displayExpiresOn: '',
        displayDescription: c.cancellation_reason || 'Cancellation',
        sortDate: c.date
    }));

    return [...earned, ...cancels].sort((a, b) => {
        const dateA = new Date(a.displayDate || a.sortDate || 0).getTime();
        const dateB = new Date(b.displayDate || b.sortDate || 0).getTime();
        return dateA - dateB;
    });
});

// Collapsible UI State
const isTierOpen = ref(true);
const isBonusOpen = ref(true);
const isRedeemOpen = ref(true);

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Gratitude #${gratitudeNumber}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-[1600px] space-y-6 pb-20">
            <!-- Header Row -->
            <div class="flex items-center justify-between border-b border-border pb-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-light text-foreground flex items-center gap-2">
                        Gratitude Number <span class="text-muted-foreground/30 px-2">|</span> <span class="font-medium text-foreground">{{ gratitudeNumber }}</span>
                    </h1>
                </div>
                <div>
                     <Link href="/gratitude">
                        <Button variant="outline" size="sm" class="flex items-center gap-2 text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="w-4 h-4" /> Home / Gratitude / {{ gratitudeNumber }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Dashboard Row 1: Guests & Actions -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6" v-if="data.gratitude">
                <!-- Guests -->
                <div class="flex flex-wrap items-center gap-[2px] rounded-md overflow-hidden bg-background">
                    <div class="bg-[#0082b4] text-white px-5 py-2 flex flex-col items-center justify-center min-w-[140px] gap-1 shadow-sm">
                        <span class="text-[0.65rem] uppercase tracking-wider opacity-90 font-medium">Primary</span>
                        <span class="font-bold text-sm">Brian King</span>
                    </div>
                    <div class="bg-[#2ebc15] text-white px-5 py-2 flex flex-col items-center justify-center min-w-[140px] gap-1 shadow-sm">
                        <span class="text-[0.65rem] uppercase tracking-wider text-green-900 font-bold">Secondary</span>
                        <span class="font-bold text-sm text-green-50">Sara King</span>
                    </div>
                    <div class="bg-[#2ebc15] text-white px-5 py-2 flex flex-col items-center justify-center min-w-[140px] gap-1 shadow-sm">
                        <span class="text-[0.65rem] uppercase tracking-wider text-green-900 font-bold">Secondary</span>
                        <span class="font-bold text-sm text-green-50">Brendan King</span>
                    </div>
                    <div class="bg-[#2ebc15] text-white px-5 py-2 flex flex-col items-center justify-center min-w-[140px] gap-1 shadow-sm">
                        <span class="text-[0.65rem] uppercase tracking-wider text-green-900 font-bold">Secondary</span>
                        <span class="font-bold text-sm text-green-50">Alysa Hansen</span>
                    </div>
                </div>

                <!-- Actions & Level -->
                <div class="flex items-center gap-6">
                    <div class="flex items-center space-x-2">
                        <Button variant="default" class="bg-[#1f2937] hover:bg-[#111827] text-[0.7rem] font-bold px-4 tracking-wider uppercase h-7 rounded-md transition-all">
                            Update Gratitude Status
                        </Button>
                        <Button variant="destructive" class="bg-[#dc2626] hover:bg-[#b91c1c] text-[0.7rem] font-bold px-4 tracking-wider uppercase h-7 rounded-md transition-all">
                            Delete
                        </Button>
                    </div>
                    <!-- Level -->
                    <div class="text-right pl-4 pr-2">
                        <div class="text-[0.65rem] text-muted-foreground capitalize font-bold">{{ data.gratitude.level || 'Explorer' }}</div>
                        <div class="text-xl font-bold leading-none mt-1 text-foreground">{{ data.gratitude.level || 'Explorer' }}</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Row 2: Equation Summary -->
            <div class="flex flex-col lg:flex-row lg:items-stretch gap-0 bg-background overflow-hidden shadow-sm mt-4 transition-all duration-300 w-full mb-8 rounded-sm">
                <div class="bg-[#0f2e4a] text-white p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider opacity-80 mb-1 leading-tight">Tier Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(tierPointsSum) }}</span>
                </div>
                
                <div class="bg-[#1a202c] text-white px-6 border-r border-l border-white/10 flex items-center justify-center text-xl font-light py-2 lg:py-0">
                    +
                </div>

                <div class="bg-background text-foreground p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider text-muted-foreground mb-1 leading-tight">Bonus Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(bonusPointsSum) }}</span>
                </div>

                <div class="bg-[#1a202c] text-white px-6 border-r border-l border-white/10 flex items-center justify-center text-xl font-light py-2 lg:py-0">
                    -
                </div>

                <div class="bg-[#ffb81c] text-neutral-900 p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider opacity-90 mb-1 leading-tight font-medium">Redeemed Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(redemptionsSum) }}</span>
                </div>

                <div class="bg-[#1a202c] text-white px-6 border-r border-l border-white/10 flex items-center justify-center text-xl font-light py-2 lg:py-0">
                    -
                </div>

                <div class="bg-[#e53e3e] text-white p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider opacity-95 mb-1 font-medium leading-tight">Cancellation Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(cancellationsSum) }}</span>
                </div>

                <div class="bg-[#1a202c] text-white px-6 border-r border-l border-white/10 flex items-center justify-center text-xl font-light py-2 lg:py-0">
                    =
                </div>

                <div class="bg-[#17a2b8] text-white p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider opacity-95 mb-1 leading-tight">Total Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(totalPoints) }}</span>
                </div>

                <div class="bg-[#38a169] text-white p-4 flex-1 flex flex-col justify-center">
                    <span class="text-xs tracking-wider font-semibold opacity-100 mb-1 leading-tight">Usable Points</span>
                    <span class="font-bold text-xl leading-tight">{{ formatNumber(usablePoints) }}</span>
                </div>
            </div>

            <!-- Collapsible Sections -->
            <div class="space-y-4 pt-4">
                
                <!-- Tier Points Section -->
                <div class="border border-border rounded-sm overflow-hidden bg-card transition-all duration-300">
                    <div class="bg-[#0f2e4a] hover:bg-[#0b2238] transition-colors text-white px-4 py-3 xl:py-2.5 flex items-center justify-between cursor-pointer" @click="isTierOpen = !isTierOpen">
                        <div class="flex items-center gap-6">
                            <h2 class="text-sm font-semibold tracking-wider text-white/90">Tier Points</h2>
                        </div>
                        <div class="flex items-center gap-6">
                            <div @click.stop class="flex space-x-2">
                                <AddEarnedPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                            </div>
                            <span class="text-white text-xl">{{ isTierOpen ? '-' : '+' }}</span>
                        </div>
                    </div>
                    <div v-show="isTierOpen" class="p-0 border-t border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-card">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Effective Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Project</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Points</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Expires On</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize w-1/3">Description</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-foreground capitalize">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="item in combinedTierPoints" :key="item.rowType + '-' + item.id" class="hover:bg-muted/10 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ item.displayDate ? new Date(item.displayDate).toISOString().split('T')[0] : '' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground font-medium">{{ item.displayProject }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium" :class="item.rowType === 'cancel' ? 'text-red-500' : 'text-foreground'">{{ formatNumber(item.displayPoints) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground font-medium">{{ item.displayExpiresOn ? new Date(item.displayExpiresOn).toISOString().split('T')[0] : '' }}</td>
                                    <td class="px-6 py-4 text-sm text-foreground font-medium">{{ item.displayDescription }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-1 flex items-center justify-end">
                                        <template v-if="item.rowType === 'earned'">
                                            <button class="bg-[#1f2937] hover:bg-black text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Update</button>
                                            <button class="bg-[#ffb81c] hover:bg-[#d69e18] text-black text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Cancel</button>
                                            <button class="bg-[#dc2626] hover:bg-[#b91c1c] text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Delete</button>
                                        </template>
                                        <template v-else>
                                            <button class="bg-[#dc2626] hover:bg-[#b91c1c] text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Delete</button>
                                        </template>
                                    </td>
                                </tr>
                                <tr v-if="combinedTierPoints.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-muted-foreground font-medium">No tier points recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bonus Points Section -->
                <div class="border border-border rounded-sm overflow-hidden bg-card transition-all duration-300">
                    <div class="bg-[#0f2e4a] hover:bg-[#0b2238] transition-colors text-white px-4 py-3 xl:py-2.5 flex items-center justify-between cursor-pointer" @click="isBonusOpen = !isBonusOpen">
                        <div class="flex items-center gap-6">
                            <h2 class="text-sm font-semibold tracking-wider text-white/90">Bonus Points</h2>
                        </div>
                        <div class="flex items-center gap-6">
                            <div @click.stop class="flex space-x-2">
                                <AddBonusPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                            </div>
                            <span class="text-white text-xl">{{ isBonusOpen ? '-' : '+' }}</span>
                        </div>
                    </div>
                    <div v-show="isBonusOpen" class="p-0 border-t border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-card">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Description</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Points</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-foreground capitalize">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="point in data.bonus_points" :key="point.id" class="hover:bg-muted/10 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ new Date(point.date).toISOString().split('T')[0] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground font-medium">{{ point.description || point.category }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ formatNumber(point.points) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-1 flex items-center justify-end">
                                        <button class="bg-[#1f2937] hover:bg-black text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Update</button>
                                        <button class="bg-[#ffb81c] hover:bg-[#d69e18] text-black text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Cancel</button>
                                        <button class="bg-[#dc2626] hover:bg-[#b91c1c] text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="data.bonus_points.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-muted-foreground font-medium">No bonus points recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Redeemed Points Section -->
                <div class="border border-border rounded-sm overflow-hidden bg-card transition-all duration-300">
                    <div class="bg-[#0f2e4a] hover:bg-[#0b2238] transition-colors text-white px-4 py-3 xl:py-2.5 flex items-center justify-between cursor-pointer" @click="isRedeemOpen = !isRedeemOpen">
                        <div class="flex items-center gap-6">
                            <h2 class="text-sm font-semibold tracking-wider text-white/90">Redeemed Points</h2>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="text-white text-xl">{{ isRedeemOpen ? '-' : '+' }}</span>
                        </div>
                    </div>
                    <div v-show="isRedeemOpen" class="p-0 border-t border-border bg-card">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-card">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Reason</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-foreground capitalize">Points Used</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-foreground capitalize">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="redemption in data.redemptions" :key="redemption.id" class="hover:bg-muted/10 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ redemption.created_at ? new Date(redemption.created_at).toISOString().split('T')[0] : 'N/A' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground font-medium">{{ redemption.reason }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ formatNumber(redemption.points) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-1 flex items-center justify-end">
                                        <button class="bg-[#1f2937] hover:bg-black text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Update</button>
                                        <button class="bg-[#dc2626] hover:bg-[#b91c1c] text-white text-[0.65rem] font-bold px-2 py-1 rounded tracking-wider uppercase">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="!data.redemptions || data.redemptions.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-muted-foreground font-medium">No redemptions recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
