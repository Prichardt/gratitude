<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Trash2, AlertTriangle, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    redemptionId: { type: [String, Number], required: true }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);
const isLoading = ref(false);
const redemption = ref<any>(null);

const openDialog = async () => {
    isOpen.value = true;
    isLoading.value = true;
    try {
        const res = await axios.get(`/internal-api/gratitude/${props.gratitudeNumber}/redeem/${props.redemptionId}`);
        redemption.value = res.data;
    } catch (e) {
        console.error('Failed to load redemption details', e);
    } finally {
        isLoading.value = false;
    }
};

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.delete(`/internal-api/gratitude/${props.gratitudeNumber}/redeem/${props.redemptionId}`);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error deleting redemption', error);
        alert('Failed to delete redemption. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const formatDate = (d: string) => d ? new Date(d).toISOString().split('T')[0] : 'N/A';
const formatNum = (n: any) => new Intl.NumberFormat('en-US').format(Number(n || 0));
</script>

<template>
    <div>
        <Button @click="openDialog" variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">
            <Trash2 class="w-3 h-3 mr-1" />Delete
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-lg rounded-xl shadow-2xl border border-destructive/20 overflow-hidden">
                <!-- Header -->
                <div class="bg-destructive text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Trash2 class="w-4 h-4 opacity-80" /> Delete Redemption
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="p-8 text-center text-muted-foreground text-sm">
                    Loading redemption details...
                </div>

                <!-- Details -->
                <div v-else-if="redemption" class="p-6 space-y-5">
                    <!-- Warning -->
                    <div class="flex items-start gap-3 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <AlertTriangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">
                            Deleting this redemption will <strong>restore all consumed points</strong> back to their original entry segments and remove the history entry.
                        </p>
                    </div>

                    <!-- Redemption Summary -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Date</p>
                            <p class="text-sm font-semibold mt-0.5">{{ formatDate(redemption.created_at) }}</p>
                        </div>
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Reason</p>
                            <p class="text-sm font-semibold mt-0.5">{{ redemption.reason || 'N/A' }}</p>
                        </div>
                        <div class="bg-red-50/70 dark:bg-red-950/20 rounded-lg p-3 border border-red-200/50 dark:border-red-800/50">
                            <p class="text-[10px] text-red-600 dark:text-red-400 font-bold uppercase tracking-wider">Points Redeemed</p>
                            <p class="text-lg font-bold text-red-700 dark:text-red-300 mt-0.5">{{ formatNum(redemption.points) }} pts</p>
                        </div>
                        <div class="bg-green-50/70 dark:bg-green-950/20 rounded-lg p-3 border border-green-200/50 dark:border-green-800/50">
                            <p class="text-[10px] text-green-600 dark:text-green-400 font-bold uppercase tracking-wider">Monetary Value</p>
                            <p class="text-lg font-bold text-green-700 dark:text-green-300 mt-0.5">${{ Number(redemption.amount || 0).toFixed(2) }}</p>
                        </div>
                    </div>

                    <!-- Segment Breakdown -->
                    <div v-if="redemption.details && redemption.details.length > 0">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <RotateCcw class="w-3.5 h-3.5" /> Points to be Restored ({{ redemption.details.length }} segment{{ redemption.details.length > 1 ? 's' : '' }})
                        </p>
                        <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                            <div v-for="detail in redemption.details" :key="detail.id"
                                 class="flex items-center justify-between bg-muted/30 rounded-md px-3 py-2 border border-border/40 text-xs">
                                <span class="text-muted-foreground font-medium">
                                    {{ detail.source_type?.split('\\').pop() || 'Segment' }}
                                    <span class="text-foreground/50 ml-1">#{{ detail.source_id }}</span>
                                </span>
                                <span class="font-bold text-primary">+{{ formatNum(detail.points) }} pts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-2 border-t border-border/50">
                        <Button type="button" variant="outline" class="h-9 px-5 font-semibold shadow-sm" @click="isOpen = false">Cancel</Button>
                        <Button @click="submit" class="h-9 px-5 bg-destructive hover:bg-destructive/90 text-white font-bold tracking-wider shadow-sm" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Deleting...' : 'Confirm Delete & Restore' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
