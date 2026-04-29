<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Ban, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    point: { type: Object, required: true },
    pointType: { type: String as () => 'earned' | 'bonus', required: true },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);
const form = ref({
    date: new Date().toISOString().split('T')[0],
    cancellation_reason: '',
    cancellation_points: 0,
});

const remainingPoints = computed(() => {
    if (props.point.remaining_points !== undefined && props.point.remaining_points !== null) {
        return Math.max(0, Number(props.point.remaining_points || 0));
    }

    return Math.max(
        0,
        Number(props.point.points || 0) - Number(props.point.redeemed_points || 0) - Number(props.point.cancelled_points || 0),
    );
});

const open = () => {
    form.value = {
        date: new Date().toISOString().split('T')[0],
        cancellation_reason: '',
        cancellation_points: remainingPoints.value,
    };
    isOpen.value = true;
};

const pointLabel = computed(() => props.pointType === 'earned' ? 'Tier Point' : 'Bonus Point');

const submit = async () => {
    isSubmitting.value = true;
    try {
        const payload: Record<string, any> = {
            date: form.value.date,
            cancellation_reason: form.value.cancellation_reason,
            cancellation_points: form.value.cancellation_points,
        };
        if (props.pointType === 'earned') {
            payload.earned_point_id = props.point.id;
        } else {
            payload.bonus_point_id = props.point.id;
        }
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/cancel`, payload);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error processing cancellation', error);
        alert('Failed to process cancellation. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const formatNum = (n: any) => new Intl.NumberFormat('en-US').format(Number(n || 0));
</script>

<template>
    <div>
        <Button @click="open" variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950">
            <Ban class="w-3 h-3 mr-1" />Cancel
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-2xl border border-amber-500/20 overflow-hidden">
                <!-- Header -->
                <div class="bg-amber-500 text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Ban class="w-4 h-4 opacity-80" /> Cancel {{ pointLabel }}
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Warning -->
                    <div class="flex items-start gap-3 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <AlertTriangle class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-800 dark:text-amber-300 font-medium">
                            This will deduct up to <strong>{{ formatNum(remainingPoints) }} pts</strong> from the remaining balance on this entry.
                        </p>
                    </div>

                    <!-- Point Summary -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Date</p>
                            <p class="text-sm font-semibold mt-0.5">{{ point.date ? new Date(point.date).toISOString().split('T')[0] : 'N/A' }}</p>
                        </div>
                        <div class="bg-amber-50/70 dark:bg-amber-950/20 rounded-lg p-3 border border-amber-200/50 dark:border-amber-800/50">
                            <p class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">Points</p>
                            <p class="text-lg font-bold text-amber-700 dark:text-amber-300 mt-0.5">{{ formatNum(remainingPoints) }} pts</p>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Cancellation Date</Label>
                            <Input type="date" v-model="form.date" required class="mt-1.5" />
                        </div>
                        <div>
                            <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Reason</Label>
                            <Input v-model="form.cancellation_reason" placeholder="Enter cancellation reason..." required class="mt-1.5" />
                        </div>
                        <div>
                            <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Points to Deduct</Label>
                            <Input type="number" v-model.number="form.cancellation_points" required min="1" :max="remainingPoints" class="mt-1.5" />
                        </div>
                        <div class="flex justify-end space-x-3 pt-2 border-t border-border/50">
                            <Button type="button" variant="outline" class="h-9 px-5 font-semibold shadow-sm" @click="isOpen = false">Close</Button>
                            <Button type="submit" class="h-9 px-5 bg-amber-500 hover:bg-amber-600 text-white font-bold tracking-wider shadow-sm" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Processing...' : 'Confirm Cancel' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
