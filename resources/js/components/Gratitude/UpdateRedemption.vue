<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pencil, DollarSign } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    redemption: { type: Object, required: true },
    pointsPerDollar: { type: Number, default: 35 }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);

const form = ref({
    reason: '',
    amount: '' as string | number,
});

watch(isOpen, (val) => {
    if (val) {
        form.value.reason = props.redemption.reason ?? '';
        form.value.amount = props.redemption.amount ?? '';
    }
});

const calculatedValue = () => {
    const pts = Number(props.redemption.points || 0);
    const rate = Number(props.redemption.points_breakdown?.points_per_dollar || props.pointsPerDollar || 35);
    return (pts / rate).toFixed(2);
};

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.put(
            `/internal-api/gratitude/${props.gratitudeNumber}/redeem/${props.redemption.id}`,
            { reason: form.value.reason, amount: form.value.amount || null }
        );
        isOpen.value = false;
        emit('saved');
    } catch (error: any) {
        console.error('Error updating redemption', error);
        alert(error.response?.data?.message || 'Failed to update redemption.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">
            <Pencil class="w-3 h-3 mr-1" />Update
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-2xl border border-border/50 overflow-hidden">
                <!-- Header -->
                <div class="bg-primary text-primary-foreground px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Pencil class="w-4 h-4 opacity-80" /> Update Redemption
                    </h2>
                    <button @click="isOpen = false" class="text-primary-foreground/70 hover:text-primary-foreground transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <!-- Info Banner -->
                <div class="bg-muted/40 border-b border-border/50 px-6 py-3 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Points</p>
                        <p class="text-base font-bold text-foreground">{{ new Intl.NumberFormat().format(redemption.points) }} pts</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Calculated Value</p>
                        <p class="text-base font-bold text-green-600 dark:text-green-400 flex items-center gap-1">
                            <DollarSign class="w-3.5 h-3.5" />{{ calculatedValue() }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Reason</Label>
                        <Input v-model="form.reason" required class="h-10" placeholder="Reason for redemption" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Override Amount ($)</Label>
                        <Input type="number" step="0.01" v-model="form.amount" class="h-10" :placeholder="`Calculated: $${calculatedValue()}`" />
                        <p class="text-xs text-muted-foreground">Leave blank to keep the current stored value (${{ Number(redemption.amount || 0).toFixed(2) }}).</p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-border">
                        <Button type="button" variant="outline" class="h-9 px-5 font-semibold shadow-sm" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" class="h-9 px-5 font-bold tracking-wider shadow-sm" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
