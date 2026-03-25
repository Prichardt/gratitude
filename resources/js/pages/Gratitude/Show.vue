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
import { Card } from '@/components/ui/card';
import { ArrowLeft, ChevronDown, ChevronRight, Award, History, Gift, ShieldAlert, Zap } from 'lucide-vue-next';

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
    rolling_tier_points: 0,
    level_benefits: []
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
const isTierOpen = ref(false);
const isBonusOpen = ref(false);
const isRedeemOpen = ref(false);
const isBenefitsOpen = ref(false);

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Gratitude #${gratitudeNumber}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-[1600px] space-y-6 pb-20">
            <!-- Header Row -->
            <div class="flex items-center justify-between pb-6 border-b border-border/50 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-foreground tracking-tight flex items-center gap-3">
                        Gratitude <span class="text-muted-foreground/30 font-light text-4xl leading-none">|</span> <span class="text-primary">{{ gratitudeNumber }}</span>
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage points, benefits, and guest status for this account</p>
                </div>
                <div>
                     <Link href="/gratitude">
                        <Button variant="outline" size="sm" class="flex items-center gap-2 text-muted-foreground hover:text-foreground h-9 px-4 rounded-full border-border/50 shadow-sm transition-all hover:bg-muted/50">
                            <ArrowLeft class="w-4 h-4" /> Back to Program
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Dashboard Row 1: Guests & Actions in a Card -->
            <Card v-if="data.gratitude" class="shadow-sm border-border overflow-hidden mb-8 bg-gradient-to-r from-card to-muted/10">
                <div class="px-6 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                    <!-- Guests -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="bg-blue-600/10 border border-blue-600/20 text-blue-700 dark:text-blue-400 px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm transition-colors hover:bg-blue-600/20">
                            <span class="text-[0.65rem] uppercase tracking-wider opacity-90 font-bold">Primary</span>
                            <span class="font-bold text-sm">Brian King</span>
                        </div>
                        <div class="bg-emerald-600/10 border border-emerald-600/20 text-emerald-700 dark:text-emerald-400 px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm transition-colors hover:bg-emerald-600/20">
                            <span class="text-[0.65rem] uppercase tracking-wider font-bold opacity-90">Secondary</span>
                            <span class="font-bold text-sm">Sara King</span>
                        </div>
                        <div class="bg-emerald-600/10 border border-emerald-600/20 text-emerald-700 dark:text-emerald-400 px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm transition-colors hover:bg-emerald-600/20">
                            <span class="text-[0.65rem] uppercase tracking-wider font-bold opacity-90">Secondary</span>
                            <span class="font-bold text-sm">Brendan King</span>
                        </div>
                        <div class="bg-emerald-600/10 border border-emerald-600/20 text-emerald-700 dark:text-emerald-400 px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm transition-colors hover:bg-emerald-600/20">
                            <span class="text-[0.65rem] uppercase tracking-wider font-bold opacity-90">Secondary</span>
                            <span class="font-bold text-sm">Alysa Hansen</span>
                        </div>
                    </div>

                    <!-- Actions & Level -->
                    <div class="flex items-center gap-6">
                        <div class="flex items-center space-x-3">
                            <Button class="bg-blue-600 hover:bg-blue-700 text-white shadow-md transition-all h-10 px-6 text-xs font-bold tracking-wider uppercase rounded-lg">
                                Update Status
                            </Button>
                            <Button variant="destructive" class="shadow-md transition-all h-10 px-6 text-xs font-bold tracking-wider uppercase rounded-lg">
                                Delete
                            </Button>
                        </div>
                        <!-- Level only icon -->
                        <div class="pl-6 border-l border-border/50 flex items-center justify-center min-w-[100px]">
                            <img v-if="data.level_info?.level_icon" :src="`/storage/${data.level_info.level_icon}`" class="w-20 h-20 xl:w-24 xl:h-24 object-contain drop-shadow-xl" :alt="data.gratitude.level" />
                            <img v-else-if="data.level_info?.level_image" :src="`/storage/${data.level_info.level_image}`" class="w-20 h-20 xl:w-24 xl:h-24 object-contain drop-shadow-xl" :alt="data.gratitude.level" />
                            <Award v-else class="w-20 h-20 xl:w-24 xl:h-24 text-amber-500 drop-shadow-xl" />
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Dashboard Row 2: Equation Summary -->
            <Card class="shadow-sm border-border overflow-hidden mb-8">
                <div class="flex flex-col lg:flex-row lg:items-stretch w-full divide-y lg:divide-y-0 lg:divide-x divide-border">
                    <div class="p-4 flex-1 flex flex-col justify-center bg-card hover:bg-muted/30 transition-colors">
                        <span class="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1 flex items-center gap-2"><Zap class="w-3.5 h-3.5 text-blue-500"/> Tier Points</span>
                        <span class="font-bold text-2xl text-foreground">{{ formatNumber(tierPointsSum) }}</span>
                    </div>
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">+</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-card hover:bg-muted/30 transition-colors">
                        <span class="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1 flex items-center gap-2"><Gift class="w-3.5 h-3.5 text-green-500"/> Bonus Points</span>
                        <span class="font-bold text-2xl text-foreground">{{ formatNumber(bonusPointsSum) }}</span>
                    </div>
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">-</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-card hover:bg-muted/30 transition-colors">
                        <span class="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1 flex items-center gap-2"><History class="w-3.5 h-3.5 text-amber-500"/> Redeemed</span>
                        <span class="font-bold text-2xl text-foreground">{{ formatNumber(redemptionsSum) }}</span>
                    </div>
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">-</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-card hover:bg-muted/30 transition-colors">
                        <span class="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1 flex items-center gap-2"><ShieldAlert class="w-3.5 h-3.5 text-destructive"/> Cancelled</span>
                        <span class="font-bold text-2xl text-foreground">{{ formatNumber(cancellationsSum) }}</span>
                    </div>
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">=</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-blue-50/50 dark:bg-blue-950/20">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider mb-1">Total Points</span>
                        <span class="font-bold text-2xl text-blue-700 dark:text-blue-300">{{ formatNumber(totalPoints) }}</span>
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-green-50/50 dark:bg-green-950/20 shadow-[inset_4px_0_0_0_rgba(34,197,94,0.2)]">
                        <span class="text-xs text-green-600 dark:text-green-400 font-bold uppercase tracking-wider mb-1">Usable Points</span>
                        <span class="font-bold text-2xl text-green-700 dark:text-green-300">{{ formatNumber(usablePoints) }}</span>
                    </div>
                </div>
            </Card>

            <!-- Collapsible Sections -->
            <div class="space-y-4 pt-4">

                <!-- Level Benefits Card -->
                <Card class="shadow-sm border-border overflow-hidden transition-all duration-300">
                    <div class="bg-card hover:bg-muted/30 transition-colors px-6 py-4 flex items-center justify-between cursor-pointer border-b border-transparent" :class="{'border-border': isBenefitsOpen}" @click="isBenefitsOpen = !isBenefitsOpen">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 p-2 rounded-md">
                                <Award class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground tracking-tight">My Tiers Benefits</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">View active benefits for your current program level</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isBenefitsOpen}" />
                        </div>
                    </div>
                    <div v-show="isBenefitsOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Benefit Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Value</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="benefit in data.level_benefits" :key="benefit.id" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-foreground">{{ benefit.name }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                            <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary border border-primary/20">
                                                {{ benefit.value || 'Included' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-foreground/80">{{ benefit.level_description || benefit.benefit_description }}</td>
                                    </tr>
                                    <tr v-if="!data.level_benefits || data.level_benefits.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-sm text-muted-foreground">No benefits mapped to this tier.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

                <!-- Tier Points Card -->
                <Card class="shadow-sm border-border overflow-hidden transition-all duration-300">
                    <div class="bg-card hover:bg-muted/30 transition-colors px-6 py-4 flex items-center justify-between cursor-pointer border-b border-transparent" :class="{'border-border': isTierOpen}" @click="isTierOpen = !isTierOpen">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-500/10 dark:bg-blue-500/20 p-2 rounded-md">
                                <Zap class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground tracking-tight">Tier Points</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">Base point earnings from interactions</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div @click.stop class="flex space-x-2">
                                <AddEarnedPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                            </div>
                            <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isTierOpen}" />
                        </div>
                    </div>
                    <div v-show="isTierOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Effective Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Project</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Points</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Expires On</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider w-1/3">Description</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="item in combinedTierPoints" :key="item.rowType + '-' + item.id" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ item.displayDate ? new Date(item.displayDate).toISOString().split('T')[0] : '' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/90 font-medium">{{ item.displayProject }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold" :class="item.rowType === 'cancel' ? 'text-destructive' : 'text-foreground'">{{ formatNumber(item.displayPoints) }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/80">{{ item.displayExpiresOn ? new Date(item.displayExpiresOn).toISOString().split('T')[0] : '' }}</td>
                                        <td class="px-6 py-4 text-sm text-foreground/80">{{ item.displayDescription }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                            <template v-if="item.rowType === 'earned'">
                                                <Button variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Update</Button>
                                                <Button variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950">Cancel</Button>
                                                <Button variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Delete</Button>
                                            </template>
                                            <template v-else>
                                                <Button variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Delete</Button>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr v-if="combinedTierPoints.length === 0">
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted-foreground">No tier points recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

                <!-- Bonus Points Card -->
                <Card class="shadow-sm border-border overflow-hidden transition-all duration-300">
                    <div class="bg-card hover:bg-muted/30 transition-colors px-6 py-4 flex items-center justify-between cursor-pointer border-b border-transparent" :class="{'border-border': isBonusOpen}" @click="isBonusOpen = !isBonusOpen">
                        <div class="flex items-center gap-3">
                            <div class="bg-green-500/10 dark:bg-green-500/20 p-2 rounded-md">
                                <Gift class="w-5 h-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground tracking-tight">Bonus Points</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">Promotional or extra points awarded</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div @click.stop class="flex space-x-2">
                                <AddBonusPoints :gratitudeNumber="gratitudeNumber" @saved="fetchDetails" />
                            </div>
                            <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isBonusOpen}" />
                        </div>
                    </div>
                    <div v-show="isBonusOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Points</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="point in data.bonus_points" :key="point.id" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ new Date(point.date).toISOString().split('T')[0] }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/80">{{ point.description || point.category }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-foreground">{{ formatNumber(point.points) }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                            <Button variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Update</Button>
                                            <Button variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950">Cancel</Button>
                                            <Button variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Delete</Button>
                                        </td>
                                    </tr>
                                    <tr v-if="data.bonus_points.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted-foreground">No bonus points recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

                <!-- Redeemed Points Card -->
                <Card class="shadow-sm border-border overflow-hidden transition-all duration-300">
                    <div class="bg-card hover:bg-muted/30 transition-colors px-6 py-4 flex items-center justify-between cursor-pointer border-b border-transparent" :class="{'border-border': isRedeemOpen}" @click="isRedeemOpen = !isRedeemOpen">
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-500/10 dark:bg-amber-500/20 p-2 rounded-md">
                                <History class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground tracking-tight">Redeemed Points</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">Points you have used / redeemed</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isRedeemOpen}" />
                        </div>
                    </div>
                    <div v-show="isRedeemOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Points Used</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="redemption in data.redemptions" :key="redemption.id" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ redemption.created_at ? new Date(redemption.created_at).toISOString().split('T')[0] : 'N/A' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/80">{{ redemption.reason }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-foreground">{{ formatNumber(redemption.points) }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                            <Button variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Update</Button>
                                            <Button variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">Delete</Button>
                                        </td>
                                    </tr>
                                    <tr v-if="!data.redemptions || data.redemptions.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted-foreground">No redemptions recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
