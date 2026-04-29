<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import AddEarnedPoints from '@/components/Gratitude/AddEarnedPoints.vue';
import AddBonusPoints from '@/components/Gratitude/AddBonusPoints.vue';
import UpdateEarnedPoints from '@/components/Gratitude/UpdateEarnedPoints.vue';
import UpdateBonusPoints from '@/components/Gratitude/UpdateBonusPoints.vue';
import CancelPointEntry from '@/components/Gratitude/CancelPointEntry.vue';
import DeletePointEntry from '@/components/Gratitude/DeletePointEntry.vue';
import DeleteCancellation from '@/components/Gratitude/DeleteCancellation.vue';
import AddRedemption from '@/components/Gratitude/AddRedemption.vue';
import DeleteRedemption from '@/components/Gratitude/DeleteRedemption.vue';
import UpdateRedemption from '@/components/Gratitude/UpdateRedemption.vue';
import ViewEntryDetails from '@/components/Gratitude/ViewEntryDetails.vue';
import ViewRedemptionDetails from '@/components/Gratitude/ViewRedemptionDetails.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ArrowLeft, ChevronDown, Award, History, Gift, ShieldAlert, Zap, Clock, RefreshCw, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';

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
    guests: [],
    earned_points: [],
    bonus_points: [],
    cancellations: [],
    redemptions: [],
    next_level: null,
    points_to_next_level: 0,
    rolling_tier_points: 0,
    level_benefits: [],
    points_per_dollar: 35,
    partner_points_per_dollar: 35,
    points_history: [],
    interval_start: null,
    interval_end: null,
    interval_years: 2,
    current_level_min: 0,
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
    return data.value.cancellations.reduce((sum: number, p: any) => sum + Number(p.points || 0), 0);
});
const redemptionsSum = computed(() => {
    return data.value.redemptions.reduce((sum: number, p: any) => sum + Number(p.points || 0), 0);
});
const totalPoints = computed(() => {
    return data.value.gratitude?.totalRemainingPoints ?? Math.max(0, tierPointsSum.value + bonusPointsSum.value - cancellationsSum.value - redemptionsSum.value - expiredPointsSum.value);
});
const usablePoints = computed(() => {
    return data.value.gratitude?.useablePoints || 0;
});
const earnedExpireDays = computed(() => Number(data.value.level_info?.earned_expire_days || 730));
const bonusExpireDays = computed(() => Number(data.value.level_info?.bonus_expire_days || 730));
const expiredPointsSum = computed(() => {
    const now = new Date();
    const earnedExpired = data.value.earned_points
        .filter((p: any) => !p.cancel_id && p.expires_at && new Date(p.expires_at) <= now)
        .reduce((sum: number, p: any) => sum + pointRemaining(p), 0);
    const bonusExpired = data.value.bonus_points
        .filter((p: any) => !p.cancel_id && p.expires_at && new Date(p.expires_at) <= now)
        .reduce((sum: number, p: any) => sum + pointRemaining(p), 0);
    return earnedExpired + bonusExpired;
});

const pointCancelled = (p: any) => Number(p.cancelled_points || 0);
const pointRemaining = (p: any) => p.remaining_points !== undefined && p.remaining_points !== null
    ? Math.max(0, Number(p.remaining_points || 0))
    : Math.max(0, Number(p.points || 0) - Number(p.redeemed_points || 0) - pointCancelled(p));
const pointIsFullyCancelled = (p: any) => pointCancelled(p) > 0 && pointRemaining(p) === 0;
const cancellationList = (p: any) => p.cancellations_list || [];
const hasAllocatedCancellation = (c: any) => Array.isArray(c.points_breakdown) && c.points_breakdown.length > 0;

// Combine Tier Points + Cancellations
const combinedTierPoints = computed(() => {
    const now = new Date();
    const earned = data.value.earned_points.map((p: any) => {
        let isCancelled = p.cancel_id && p.cancellation;
        let hasCancellation = isCancelled || pointCancelled(p) > 0;
        let isExpired = !isCancelled && !!p.expires_at && new Date(p.expires_at) <= now;
        return {
            ...p,
            rowType: 'earned',
            isExpired,
            displayDate: p.date,
            displayProject: p.project_data ? `${p.project_data.projectNumber || p.project_data.number || ''} - ${p.project_data.name || p.project_data.title || p.category}` : p.category, 
            displaySubtitle: p.project_data ? (p.project_data.subtitle || p.project_data.type || '') : '',
            displayPoints: p.points,
            displayExpiresOn: p.expires_at || '',
            displayDescription: p.description || 'Tier Points earned',
            hasCancellation,
            isFullyCancelled: pointIsFullyCancelled(p),
            cancellationData: p.cancellation || null,
            cancellationsList: cancellationList(p),
            redemptionsList: buildRedemptionsList(p),
            sortDate: p.date
        };
    });

    const linkedCancelIds = new Set(data.value.earned_points.map((p: any) => p.cancel_id).filter(Boolean));
    const standaloneCancels = data.value.cancellations
        .filter((c: any) => !linkedCancelIds.has(c.id) && !hasAllocatedCancellation(c))
        .map((c: any) => ({
            ...c,
            rowType: 'cancel',
            displayDate: c.date, 
            displayProject: 'System / Generic', 
            displaySubtitle: '',
            displayPoints: -c.points,
            displayExpiresOn: '',
            displayDescription: c.description || 'Cancellation',
            sortDate: c.date,
            redemptionsList: []
        }));

    return [...earned, ...standaloneCancels].sort((a, b) => {
        const dateA = new Date(a.sortDate || a.displayDate || 0).getTime();
        const dateB = new Date(b.sortDate || b.displayDate || 0).getTime();
        return dateB - dateA;
    });
});

const combinedBonusPoints = computed(() => {
    const now = new Date();
    return data.value.bonus_points.map((p: any) => {
        let isCancelled = p.cancel_id && p.cancellation;
        let hasCancellation = isCancelled || pointCancelled(p) > 0;
        let isExpired = !isCancelled && !!p.expires_at && new Date(p.expires_at) <= now;
        return {
            ...p,
            rowType: 'bonus',
            isExpired,
            hasCancellation,
            isFullyCancelled: pointIsFullyCancelled(p),
            cancellationData: p.cancellation || null,
            cancellationsList: cancellationList(p),
            redemptionsList: buildRedemptionsList(p)
        };
    }).sort((a: any, b: any) => new Date(b.date).getTime() - new Date(a.date).getTime());
});

// Normalize redemption history from both RedeemPointsDetails records and the legacy redemption_history JSON column
const buildRedemptionsList = (p: any) => {
    const fromDetails = (p.redemptions || []).map((r: any) => ({
        _key: `detail-${r.id}`,
        points: r.points,
        reason: r.redeem_point?.reason ?? 'Redemption',
        date: r.created_at ? new Date(r.created_at).toISOString().split('T')[0] : '',
        redeem_id: r.redeem_id,
    }));

    const existingRedeemIds = new Set(fromDetails.map((r: any) => r.redeem_id).filter(Boolean));

    const fromHistory = (p.redemption_history || [])
        .filter((h: any) => !existingRedeemIds.has(h.redemption_id))
        .map((h: any, i: number) => ({
            _key: `history-${i}`,
            points: h.points,
            reason: h.reason ?? 'Redemption',
            date: h.date ?? '',
        }));

    return [...fromDetails, ...fromHistory];
};

// Collapsible UI State
const isTierOpen = ref(false);
const isBonusOpen = ref(false);
const isRedeemOpen = ref(false);
const isBenefitsOpen = ref(false);
const isPointsHistoryOpen = ref(false);
const isLevelHistoryOpen = ref(false);

// Level progress helpers
const levelProgressPct = computed(() => {
    const rolling = data.value.rolling_tier_points ?? 0;
    const nextMin = data.value.next_level
        ? (rolling + (data.value.points_to_next_level ?? 0))
        : rolling;
    const currentMin = data.value.current_level_min ?? 0;
    const range = nextMin - currentMin;
    if (range <= 0) return 100;
    return Math.min(100, Math.round(((rolling - currentMin) / range) * 100));
});

const changeTypeIcon = (type: string) => {
    if (type === 'upgrade')   return TrendingUp;
    if (type === 'downgrade') return TrendingDown;
    return Minus;
};
const changeTypeClass = (type: string) => {
    if (type === 'upgrade')   return 'text-green-600 dark:text-green-400';
    if (type === 'downgrade') return 'text-red-600 dark:text-red-400';
    return 'text-muted-foreground';
};

const syncing = ref(false);
const syncBalance = async () => {
    syncing.value = true;
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/sync-balance`);
        await fetchDetails();
    } catch (error) {
        console.error('Failed to sync balance', error);
    } finally {
        syncing.value = false;
    }
};

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
                        <template v-if="data.guests && data.guests.length">
                            <div
                                v-for="guest in data.guests"
                                :key="guest.guest_id"
                                :class="guest.gratitude_ownership === 'primary'
                                    ? 'bg-blue-600/10 border-blue-600/20 text-blue-700 dark:text-blue-400 hover:bg-blue-600/20'
                                    : 'bg-emerald-600/10 border-emerald-600/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-600/20'"
                                class="border px-4 py-1.5 rounded-full flex items-center gap-2 shadow-sm transition-colors"
                            >
                                <span class="text-[0.65rem] uppercase tracking-wider opacity-90 font-bold">{{ guest.gratitude_ownership }}</span>
                                <span class="font-bold text-sm">{{ guest.preferred_name || guest.first_name }} {{ guest.last_name }}</span>
                            </div>
                        </template>
                        <span v-else class="text-sm text-muted-foreground italic">No guests found</span>
                    </div>

                    <!-- Actions & Level -->
                    <div class="flex items-center gap-6">
                        <div class="flex items-center space-x-3">
                            <Button
                                variant="outline"
                                class="shadow-md transition-all h-10 px-4 text-xs font-bold tracking-wider uppercase rounded-lg flex items-center gap-2"
                                :disabled="syncing"
                                @click="syncBalance"
                            >
                                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': syncing }" />
                                {{ syncing ? 'Syncing...' : 'Sync Balance' }}
                            </Button>
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

            <!-- Dashboard Row 1b: Level Status Card -->
            <Card v-if="data.gratitude" class="shadow-sm border-border overflow-hidden mb-8">
                <!-- Header / toggle -->
                <div
                    class="px-6 py-4 flex items-center justify-between cursor-pointer hover:bg-muted/30 transition-colors border-b border-transparent"
                    :class="{ 'border-border': isLevelHistoryOpen }"
                    @click="isLevelHistoryOpen = !isLevelHistoryOpen"
                >
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <!-- Level icon / badge -->
                        <div class="shrink-0">
                            <img v-if="data.level_info?.level_icon" :src="`/storage/${data.level_info.level_icon}`" class="w-10 h-10 object-contain drop-shadow" :alt="data.gratitude.level" />
                            <img v-else-if="data.level_info?.level_image" :src="`/storage/${data.level_info.level_image}`" class="w-10 h-10 object-contain drop-shadow" :alt="data.gratitude.level" />
                            <Award v-else class="w-10 h-10 text-amber-500" />
                        </div>
                        <!-- Level name + window label -->
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-bold text-foreground">{{ data.gratitude.level }}</span>
                                <span v-if="data.gratitude.statusChange" :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                    data.gratitude.statusChange === 'upgrade'   ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' :
                                    data.gratitude.statusChange === 'downgrade' ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' :
                                    'bg-muted text-muted-foreground']">
                                    <component :is="changeTypeIcon(data.gratitude.statusChange)" class="w-3 h-3" />
                                    {{ data.gratitude.statusChange }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                {{ data.interval_years }}-year window:
                                {{ data.interval_start }} → {{ data.interval_end }}
                            </p>
                        </div>
                        <!-- Progress bar + points -->
                        <div class="flex-1 min-w-[120px] max-w-xs hidden sm:block px-4">
                            <div class="flex items-center justify-between text-xs text-muted-foreground mb-1">
                                <span>{{ formatNumber(data.rolling_tier_points) }} pts</span>
                                <span v-if="data.next_level">{{ formatNumber(data.rolling_tier_points + data.points_to_next_level) }} for {{ data.next_level }}</span>
                                <span v-else class="text-green-600 dark:text-green-400 font-semibold">Top tier</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full bg-primary transition-all duration-500" :style="{ width: levelProgressPct + '%' }" />
                            </div>
                            <p v-if="data.next_level" class="text-[10px] text-muted-foreground mt-1">
                                {{ formatNumber(data.points_to_next_level) }} pts to {{ data.next_level }}
                            </p>
                        </div>
                    </div>
                    <ChevronDown class="w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-200 ml-4" :class="{ 'rotate-180': isLevelHistoryOpen }" />
                </div>

                <!-- Level History (collapsible) -->
                <div v-show="isLevelHistoryOpen" class="border-t border-border bg-card">
                    <div class="p-4">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Level History</p>
                        <div v-if="data.gratitude.levelHistory && data.gratitude.levelHistory.length" class="space-y-2">
                            <div
                                v-for="(entry, i) in [...(data.gratitude.levelHistory)].reverse()"
                                :key="i"
                                class="flex items-start gap-3 rounded-md border border-border bg-muted/10 px-4 py-3"
                            >
                                <component
                                    :is="changeTypeIcon(entry.changeType)"
                                    class="w-4 h-4 mt-0.5 shrink-0"
                                    :class="changeTypeClass(entry.changeType)"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-foreground">
                                            {{ entry.fromLevel }}
                                            <span class="text-muted-foreground font-normal mx-1">→</span>
                                            {{ entry.toLevel }}
                                        </span>
                                        <span :class="['inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider',
                                            entry.changeType === 'upgrade'   ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' :
                                            entry.changeType === 'downgrade' ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' :
                                            'bg-muted text-muted-foreground']">
                                            {{ entry.changeType }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ entry.reason }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-medium text-foreground">{{ entry.date }}</p>
                                    <p class="text-[10px] text-muted-foreground">{{ formatNumber(entry.earnedPoints) }} pts</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground text-center py-4 border border-dashed border-border rounded-md">
                            No level changes recorded yet.
                        </p>
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
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">-</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-card hover:bg-muted/30 transition-colors">
                        <span class="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1 flex items-center gap-2"><Clock class="w-3.5 h-3.5 text-orange-500"/> Expired</span>
                        <span class="font-bold text-2xl text-foreground">{{ formatNumber(expiredPointsSum) }}</span>
                    </div>
                    <div class="bg-muted/50 lg:bg-transparent flex items-center justify-center py-2 lg:py-0 w-full lg:w-16 text-muted-foreground font-light text-xl">=</div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-blue-50/50 dark:bg-blue-950/20">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider mb-1">Remaining Points</span>
                        <span class="font-bold text-2xl text-blue-700 dark:text-blue-300">{{ formatNumber(totalPoints) }}</span>
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-center bg-green-50/50 dark:bg-green-950/20 shadow-[inset_4px_0_0_0_rgba(34,197,94,0.2)]">
                        <span class="text-xs text-green-600 dark:text-green-400 font-bold uppercase tracking-wider mb-1">Usable Points</span>
                        <span class="font-bold text-2xl text-green-700 dark:text-green-300">{{ formatNumber(usablePoints) }}</span>
                        <span class="text-xs text-green-600/70 dark:text-green-400/70 font-medium mt-0.5">(${{ (usablePoints / (data.points_per_dollar || 35)).toFixed(2) }})</span>
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

                <!-- Full Points History Card -->
                <Card class="shadow-sm border-border overflow-hidden transition-all duration-300">
                    <div class="bg-card hover:bg-muted/30 transition-colors px-6 py-4 flex items-center justify-between cursor-pointer border-b border-transparent" :class="{'border-border': isPointsHistoryOpen}" @click="isPointsHistoryOpen = !isPointsHistoryOpen">
                        <div class="flex items-center gap-3">
                            <div class="bg-slate-500/10 dark:bg-slate-500/20 p-2 rounded-md">
                                <History class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground tracking-tight">Points History</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">Earned, bonus, redeemed, cancelled, and expired events</p>
                            </div>
                        </div>
                        <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isPointsHistoryOpen}" />
                    </div>
                    <div v-show="isPointsHistoryOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Points</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="entry in data.points_history" :key="`${entry.type}-${entry.source_type}-${entry.source_id}-${entry.date}-${entry.points}`" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ entry.date || 'N/A' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                                :class="entry.points >= 0 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400'">
                                                {{ entry.type }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-muted-foreground">{{ entry.source_type }} #{{ entry.source_id }}</td>
                                        <td class="px-6 py-4 text-sm text-foreground/80">{{ entry.description }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-right" :class="entry.points >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                            {{ entry.points >= 0 ? '+' : '' }}{{ formatNumber(entry.points) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!data.points_history || data.points_history.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted-foreground">No point history recorded.</td>
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
                                <AddEarnedPoints
                                    :gratitudeNumber="gratitudeNumber"
                                    :expireDays="earnedExpireDays"
                                    @saved="fetchDetails"
                                />
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
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Redeemed</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Cancelled</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Remaining</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Expires On</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider w-1/3">Description</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <template v-for="item in combinedTierPoints" :key="item.rowType + '-' + item.id">
                                        <tr class="hover:bg-muted/30 transition-colors"
                                            :class="{
                                                'bg-red-50/40 dark:bg-red-950/20 border-l-2 border-l-destructive': item.hasCancellation,
                                                'opacity-60': item.isFullyCancelled
                                            }">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">
                                                <span :class="{'line-through text-muted-foreground': item.isFullyCancelled}">
                                                    {{ item.displayDate ? new Date(item.displayDate).toISOString().split('T')[0] : '' }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <div class="text-sm text-foreground/90 font-bold" :class="{'line-through': item.isFullyCancelled}">{{ item.displayProject }}</div>
                                                <div v-if="item.displaySubtitle" class="text-xs text-muted-foreground mt-0.5">{{ item.displaySubtitle }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold" :class="item.rowType === 'cancel' || item.isFullyCancelled ? 'text-destructive line-through' : 'text-foreground'">
                                                {{ formatNumber(item.displayPoints) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-amber-600 dark:text-amber-400">
                                                <template v-if="item.rowType !== 'cancel'">{{ formatNumber(item.redeemed_points || 0) }}</template>
                                                <span v-else class="text-muted-foreground">—</span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-destructive">
                                                <template v-if="item.rowType !== 'cancel'">{{ formatNumber(pointCancelled(item)) }}</template>
                                                <span v-else class="text-muted-foreground">—</span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold" :class="item.isFullyCancelled ? 'text-muted-foreground' : 'text-green-600 dark:text-green-400'">
                                                <template v-if="item.rowType !== 'cancel'">{{ formatNumber(pointRemaining(item)) }}</template>
                                                <span v-else class="text-muted-foreground">—</span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/80">
                                                <span v-if="item.displayExpiresOn || item.expires_at" :class="{'line-through text-muted-foreground': item.isFullyCancelled}">
                                                    {{ new Date(item.displayExpiresOn || item.expires_at).toISOString().split('T')[0] }}
                                                    <span v-if="item.expires_at_manual" class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400 border border-violet-300/50">Manual</span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span :class="item.isFullyCancelled ? 'text-muted-foreground line-through text-xs' : 'text-foreground/80'">{{ item.displayDescription }}</span>
                                                <!-- Inline cancellation block for rows with cancel_id -->
                                                <div v-if="item.cancellationData && item.cancellationsList.length === 0" class="mt-1.5 flex items-start gap-1.5 bg-red-50 dark:bg-red-950/30 border border-red-200/60 dark:border-red-800/40 rounded px-2 py-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-destructive text-white shrink-0 mt-0.5">Cancelled</span>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-destructive">{{ item.cancellationData?.description }}</p>
                                                        <p class="text-[10px] text-red-500/70 dark:text-red-400/60 mt-0.5">
                                                            {{ item.cancellationData?.date ? new Date(item.cancellationData.date).toISOString().split('T')[0] : '' }}
                                                            <span v-if="item.cancellationData?.points" class="ml-1 font-bold">· -{{ formatNumber(item.cancellationData.points) }} pts</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div v-for="cancel in item.cancellationsList" :key="cancel.id" class="mt-1.5 flex items-start gap-1.5 bg-red-50 dark:bg-red-950/30 border border-red-200/60 dark:border-red-800/40 rounded px-2 py-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-destructive text-white shrink-0 mt-0.5">Cancelled</span>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-destructive">{{ cancel.description }}</p>
                                                        <p class="text-[10px] text-red-500/70 dark:text-red-400/60 mt-0.5">
                                                            {{ cancel.date ? new Date(cancel.date).toISOString().split('T')[0] : '' }}
                                                            <span class="ml-1 font-bold">· -{{ formatNumber(cancel.points) }} pts</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td class="px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                                <ViewEntryDetails :item="item" />
                                               <!-- If it's pure earned without cancellation, show actions -->
                                                <template v-if="item.rowType === 'earned'">
                                                    <span v-if="item.isExpired" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-300/50">
                                                        <Clock class="w-3 h-3" /> Expired
                                                    </span>
                                                    <UpdateEarnedPoints
                                                        :gratitudeNumber="gratitudeNumber"
                                                        :point="item"
                                                        :expireDays="earnedExpireDays"
                                                        @saved="fetchDetails"
                                                    />
                                                    <template v-if="!item.isExpired && pointRemaining(item) > 0">
                                                        <CancelPointEntry
                                                            :gratitudeNumber="gratitudeNumber"
                                                            :point="item"
                                                            pointType="earned"
                                                            @saved="fetchDetails"
                                                        />
                                                        <DeletePointEntry
                                                            :gratitudeNumber="gratitudeNumber"
                                                            :point="item"
                                                            pointType="earned"
                                                            @saved="fetchDetails"
                                                        />
                                                    </template>
                                                </template>
                                                <!-- If it is purely a cancellation row or completed_cancel, lock actions -->
                                                <template v-else-if="item.rowType === 'cancel'">
                                                    <DeleteCancellation
                                                        :gratitudeNumber="gratitudeNumber"
                                                        :cancellation="item"
                                                        @saved="fetchDetails"
                                                    />
                                                </template>
                                            </td>
                                        </tr>
                                        <!-- Nested Redemption History Row -->
                                        <tr v-if="item.redemptionsList && item.redemptionsList.length > 0" class="bg-muted/10 border-t-0 border-b border-border shadow-inner">
                                            <td colspan="9" class="px-8 py-3">
                                                <div class="flex flex-col gap-1.5 pl-4 border-l-2 border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 px-4 py-2 rounded-r-md">
                                                    <div v-for="red in item.redemptionsList" :key="red._key" class="text-xs text-muted-foreground flex items-center gap-3">
                                                        <History class="w-3.5 h-3.5" />
                                                        <span class="font-bold text-amber-600 dark:text-amber-400">-{{ formatNumber(red.points) }} pts</span>
                                                        <span>Redeemed for: <strong class="text-foreground/90 uppercase tracking-widest text-[10px] bg-card px-2 py-0.5 rounded border border-border shadow-sm mx-1">{{ red.reason }}</strong></span>
                                                        <span v-if="red.date">on {{ red.date }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="combinedTierPoints.length === 0">
                                        <td colspan="9" class="px-6 py-8 text-center text-sm text-muted-foreground">No tier points recorded.</td>
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
                                <AddBonusPoints
                                    :gratitudeNumber="gratitudeNumber"
                                    :expireDays="bonusExpireDays"
                                    @saved="fetchDetails"
                                />
                            </div>
                            <ChevronDown class="w-5 h-5 text-muted-foreground transition-transform duration-200" :class="{'rotate-180': isBonusOpen}" />
                        </div>
                    </div>
                    <div v-show="isBonusOpen" class="p-0 border-t border-border bg-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Effective Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Expires On</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Points</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Redeemed</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Cancelled</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Remaining</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <template v-for="point in combinedBonusPoints" :key="point.id">
                                        <tr class="hover:bg-muted/30 transition-colors"
                                            :class="{
                                                'bg-red-50/40 dark:bg-red-950/20 border-l-2 border-l-destructive': point.hasCancellation,
                                                'opacity-60': point.isFullyCancelled
                                            }">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">
                                                <span :class="{'line-through text-muted-foreground': point.isFullyCancelled}">
                                                    {{ point.date ? new Date(point.date).toISOString().split('T')[0] : 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <template v-if="point.expires_at">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                        :class="[
                                                            new Date(point.expires_at) < new Date() ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
                                                            point.isFullyCancelled ? 'opacity-50 line-through' : ''
                                                        ]">
                                                        {{ new Date(point.expires_at).toISOString().split('T')[0] }}
                                                    </span>
                                                    <span v-if="point.expires_at_manual" class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400 border border-violet-300/50">Manual</span>
                                                </template>
                                                <span v-else class="text-muted-foreground text-xs">No Expiry</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span :class="point.isFullyCancelled ? 'text-muted-foreground line-through text-xs' : 'text-foreground/80'">
                                                    {{ point.description || point.category }}
                                                </span>
                                                <!-- Inline cancellation block -->
                                                <div v-if="point.cancellationData && point.cancellationsList.length === 0" class="mt-1.5 flex items-start gap-1.5 bg-red-50 dark:bg-red-950/30 border border-red-200/60 dark:border-red-800/40 rounded px-2 py-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-destructive text-white shrink-0 mt-0.5">Cancelled</span>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-destructive">{{ point.cancellationData?.description }}</p>
                                                        <p class="text-[10px] text-red-500/70 dark:text-red-400/60 mt-0.5">
                                                            {{ point.cancellationData?.date ? new Date(point.cancellationData.date).toISOString().split('T')[0] : '' }}
                                                            <span v-if="point.cancellationData?.points" class="ml-1 font-bold">· -{{ formatNumber(point.cancellationData.points) }} pts</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div v-for="cancel in point.cancellationsList" :key="cancel.id" class="mt-1.5 flex items-start gap-1.5 bg-red-50 dark:bg-red-950/30 border border-red-200/60 dark:border-red-800/40 rounded px-2 py-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-destructive text-white shrink-0 mt-0.5">Cancelled</span>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold text-destructive">{{ cancel.description }}</p>
                                                        <p class="text-[10px] text-red-500/70 dark:text-red-400/60 mt-0.5">
                                                            {{ cancel.date ? new Date(cancel.date).toISOString().split('T')[0] : '' }}
                                                            <span class="ml-1 font-bold">· -{{ formatNumber(cancel.points) }} pts</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold" :class="point.isFullyCancelled ? 'text-destructive line-through' : 'text-foreground'">{{ formatNumber(point.points) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-amber-600 dark:text-amber-400">{{ formatNumber(point.redeemed_points || 0) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-destructive">{{ formatNumber(pointCancelled(point)) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold" :class="point.isFullyCancelled ? 'text-muted-foreground' : 'text-green-600 dark:text-green-400'">{{ formatNumber(pointRemaining(point)) }}</td>
                                            <td class=" px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                                <ViewEntryDetails :item="point" />
                                                <template v-if="point.rowType === 'bonus'">
                                                    <span v-if="point.isExpired" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-300/50">
                                                        <Clock class="w-3 h-3" /> Expired
                                                    </span>
                                                    <UpdateBonusPoints
                                                        :gratitudeNumber="gratitudeNumber"
                                                        :point="point"
                                                        :expireDays="bonusExpireDays"
                                                        @saved="fetchDetails"
                                                    />
                                                    <template v-if="!point.isExpired && pointRemaining(point) > 0">
                                                        <CancelPointEntry
                                                            :gratitudeNumber="gratitudeNumber"
                                                            :point="point"
                                                            pointType="bonus"
                                                            @saved="fetchDetails"
                                                        />
                                                        <DeletePointEntry
                                                            :gratitudeNumber="gratitudeNumber"
                                                            :point="point"
                                                            pointType="bonus"
                                                            @saved="fetchDetails"
                                                        />
                                                    </template>
                                                </template>
                                            </td>
                                        </tr>
                                        <!-- Nested Redemption History Row -->
                                        <tr v-if="point.redemptionsList && point.redemptionsList.length > 0" class="bg-muted/10 border-t-0 border-b border-border shadow-inner">
                                            <td colspan="8" class="px-8 py-3">
                                                <div class="flex flex-col gap-1.5 pl-4 border-l-2 border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 px-4 py-2 rounded-r-md">
                                                    <div v-for="red in point.redemptionsList" :key="red._key" class="text-xs text-muted-foreground flex items-center gap-3">
                                                        <History class="w-3.5 h-3.5" />
                                                        <span class="font-bold text-amber-600 dark:text-amber-400">-{{ formatNumber(red.points) }} pts</span>
                                                        <span>Redeemed for: <strong class="text-foreground/90 uppercase tracking-widest text-[10px] bg-card px-2 py-0.5 rounded border border-border shadow-sm mx-1">{{ red.reason }}</strong></span>
                                                        <span v-if="red.date">on {{ red.date }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="combinedBonusPoints.length === 0">
                                        <td colspan="8" class="px-6 py-8 text-center text-sm text-muted-foreground">No bonus points recorded.</td>
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
                                <p class="text-xs text-muted-foreground mt-0.5">Journey: <strong>{{ data.redemption_points_per_dollar || data.points_per_dollar }} pts = $1</strong> · Partner: <strong>{{ data.partner_points_per_dollar || data.points_per_dollar }} pts = $1</strong></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div @click.stop>
                                <AddRedemption
                                    :gratitudeNumber="gratitudeNumber"
                                    :usablePoints="usablePoints"
                                    :pointsPerDollar="data.redemption_points_per_dollar || data.points_per_dollar"
                                    :partnerPointsPerDollar="data.partner_points_per_dollar || data.points_per_dollar"
                                    :level="data.gratitude?.level || 'Explorer'"
                                    @saved="fetchDetails"
                                />
                            </div>
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
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Value ($)</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="redemption in data.redemptions" :key="redemption.id" class="hover:bg-muted/30 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-foreground">{{ redemption.created_at ? new Date(redemption.created_at).toISOString().split('T')[0] : 'N/A' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-foreground/80">{{ redemption.reason }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-foreground">{{ formatNumber(redemption.points) }} pts</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-green-600 dark:text-green-400">
                                            ${{ redemption.amount > 0 ? Number(redemption.amount).toFixed(2) : (redemption.points / data.points_per_dollar).toFixed(2) }}
                                        </td>
                                        <td class=" px-6 py-4 text-sm text-right space-x-2 flex items-center justify-end">
                                            <ViewRedemptionDetails :redemption="redemption" :pointsPerDollar="data.points_per_dollar" />
                                            <UpdateRedemption
                                                :gratitudeNumber="gratitudeNumber"
                                                :redemption="redemption"
                                                :pointsPerDollar="data.points_per_dollar"
                                                @saved="fetchDetails"
                                            />
                                            <DeleteRedemption :gratitudeNumber="gratitudeNumber" :redemptionId="redemption.id" @saved="fetchDetails" />
                                        </td>
                                    </tr>
                                    <tr v-if="!data.redemptions || data.redemptions.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted-foreground">No redemptions recorded.</td>
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
