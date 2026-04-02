<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Trash2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    cancellation: { type: Object, required: true },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.delete(`/internal-api/gratitude/${props.gratitudeNumber}/cancel/${props.cancellation.id}`);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error deleting cancellation', error);
        alert('Failed to delete cancellation. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const formatNum = (n: any) => new Intl.NumberFormat('en-US').format(Number(n || 0));
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2">
            <Trash2 class="w-3 h-3 mr-1" />Delete
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-2xl border border-destructive/20 overflow-hidden">
                <!-- Header -->
                <div class="bg-destructive text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Trash2 class="w-4 h-4 opacity-80" /> Delete Cancellation
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Warning -->
                    <div class="flex items-start gap-3 bg-red-50/80 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                        <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                            Deleting this cancellation will <strong>restore {{ formatNum(cancellation.points) }} pts</strong> to the account balance.
                        </p>
                    </div>

                    <!-- Cancellation Summary -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Date</p>
                            <p class="text-sm font-semibold mt-0.5">{{ cancellation.date ? new Date(cancellation.date).toISOString().split('T')[0] : 'N/A' }}</p>
                        </div>
                        <div class="bg-red-50/70 dark:bg-red-950/20 rounded-lg p-3 border border-red-200/50 dark:border-red-800/50">
                            <p class="text-[10px] text-red-600 dark:text-red-400 font-bold uppercase tracking-wider">Points</p>
                            <p class="text-lg font-bold text-red-700 dark:text-red-300 mt-0.5">{{ formatNum(cancellation.points) }} pts</p>
                        </div>
                    </div>
                    <div v-if="cancellation.description" class="bg-muted/30 rounded-lg p-3 border border-border/40">
                        <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Reason</p>
                        <p class="text-sm mt-0.5">{{ cancellation.description }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-2 border-t border-border/50">
                        <Button type="button" variant="outline" class="h-9 px-5 font-semibold shadow-sm" @click="isOpen = false">Cancel</Button>
                        <Button @click="submit" class="h-9 px-5 bg-destructive hover:bg-destructive/90 text-white font-bold tracking-wider shadow-sm" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Deleting...' : 'Confirm Delete' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
