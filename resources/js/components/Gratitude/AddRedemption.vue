<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { History, DollarSign } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    usablePoints: { type: Number, default: 0 },
    pointsPerDollar: { type: Number, default: 35 },
    partnerPointsPerDollar: { type: Number, default: 35 },
    level: { type: String, default: 'Explorer' },
    journeys: { type: Array, default: () => [] },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);

const form = ref({
    redemption_type: 'partner',
    journey_id: '' as string | number,
    points: 0,
    amount: '' as string | number,
    reason: 'Partner Redemption'
});

const selectedRate = computed(() => {
    return form.value.redemption_type === 'partner'
        ? props.partnerPointsPerDollar
        : props.pointsPerDollar;
});
const journeyOptions = computed(() => props.journeys as any[]);
const selectedJourney = computed(() =>
    journeyOptions.value.find((journey: any) => String(journey.journey_id || journey.id) === String(form.value.journey_id)),
);

// Computed estimated value based on level rate
const estimatedValue = computed(() => {
    const pts = Number(form.value.points) || 0;
    if (pts <= 0 || !selectedRate.value) return '0.00';
    return (pts / selectedRate.value).toFixed(2);
});

const maxRedeemable = computed(() => props.usablePoints);
const isInsufficient = computed(() => Number(form.value.points) > maxRedeemable.value);
const needsJourney = computed(() => form.value.redemption_type === 'journey');
const canSubmit = computed(() =>
    Number(form.value.points) > 0
    && !isInsufficient.value
    && (!needsJourney.value || !!form.value.journey_id),
);

const setMaxPoints = () => {
    form.value.points = maxRedeemable.value;
};

const submit = async () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/redeem`, {
            points: form.value.points,
            amount: form.value.amount || estimatedValue.value,
            reason: form.value.reason,
            redemption_type: form.value.redemption_type,
            journey_id: form.value.redemption_type === 'journey' ? form.value.journey_id : null,
            journey_data: form.value.redemption_type === 'journey' ? (selectedJourney.value?.raw || selectedJourney.value || null) : null,
        });
        isOpen.value = false;
        form.value.redemption_type = 'partner';
        form.value.journey_id = '';
        form.value.points = 0;
        form.value.amount = '';
        form.value.reason = 'Partner Redemption';
        emit('saved');
    } catch (error: any) {
        console.error('Error redeeming points', error);
        alert(error.response?.data?.message || 'Failed to redeem points. Make sure you have enough useable points.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="default" class="bg-amber-600 hover:bg-amber-700 text-white shadow-md transition-all h-10 px-6 text-xs font-bold tracking-wider uppercase rounded-lg flex items-center gap-2">
            <History class="w-4 h-4" /> Redeem Points
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-2xl border border-border overflow-hidden">
                <div class="bg-amber-600 text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <History class="w-4 h-4 opacity-80" /> Redeem Points
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <!-- Info Banner -->
                <div class="bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-800 px-6 py-3 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 font-bold uppercase tracking-wider">Available Points</p>
                        <p class="text-lg font-bold text-amber-800 dark:text-amber-200">{{ usablePoints.toLocaleString() }} pts</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 font-bold uppercase tracking-wider">Rate ({{ level }})</p>
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ selectedRate }} pts = $1</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Redemption Type</Label>
                        <select v-model="form.redemption_type" class="w-full flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="partner">Partner Purchase</option>
                            <option value="journey">Journey</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div v-if="form.redemption_type === 'journey'" class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Journey</Label>
                        <select
                            v-model="form.journey_id"
                            class="w-full flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            required
                        >
                            <option value="" disabled>Select a journey</option>
                            <option v-for="journey in journeyOptions" :key="`${journey.guest_id || 'account'}-${journey.journey_id || journey.id}`" :value="journey.journey_id || journey.id">
                                {{ journey.guest_name ? `${journey.guest_name} - ` : '' }}{{ journey.label || `Journey #${journey.journey_id || journey.id}` }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-semibold text-foreground/80">Points to Redeem</Label>
                            <button type="button" @click="setMaxPoints" class="text-[10px] text-amber-600 font-bold uppercase tracking-wider hover:underline">
                                Use Max ({{ usablePoints.toLocaleString() }})
                            </button>
                        </div>
                        <Input type="number" v-model="form.points" required class="h-10 text-lg font-bold" min="1" :max="maxRedeemable" />
                        <p v-if="isInsufficient" class="text-xs font-bold text-destructive">Exceeds available usable points.</p>
                        <p v-else class="text-xs text-muted-foreground">Points are consumed FIFO — soonest expiring first.</p>
                    </div>

                    <!-- Computed Monetary Value Display -->
                    <div class="bg-green-50/70 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
                            <DollarSign class="w-4 h-4" />
                            <span class="text-xs font-bold uppercase tracking-wider">Estimated Value</span>
                        </div>
                        <span class="text-xl font-bold text-green-700 dark:text-green-300">${{ estimatedValue }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Override Amount (Optional)</Label>
                        <Input type="number" step="0.01" v-model="form.amount" class="h-10" placeholder="Leave blank to use calculated value" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Reason</Label>
                        <Input v-model="form.reason" required class="h-10" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-border mt-6">
                        <Button type="button" variant="outline" class="h-9 px-6 font-semibold shadow-sm" @click="isOpen = false">CANCEL</Button>
                        <Button type="submit" class="h-9 px-6 bg-amber-600 hover:bg-amber-700 text-white font-semibold tracking-wider shadow-sm" :disabled="isSubmitting || !canSubmit">
                            {{ isSubmitting ? 'PROCESSING...' : 'CONFIRM REDEMPTION' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
