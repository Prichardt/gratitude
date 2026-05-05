<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Trash2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    benefit: { type: Object, required: true },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.delete(`/internal-api/gratitude/${props.gratitudeNumber}/earned-benefits/${props.benefit.id}`);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error deleting earned benefit', error);
        alert('Failed to delete. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="destructive" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 flex items-center gap-1">
            <Trash2 class="w-3 h-3" /> Delete
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-2xl border border-destructive/20 overflow-hidden">
                <div class="bg-destructive text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Trash2 class="w-4 h-4 opacity-80" /> Delete Earned Benefit
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3 bg-red-50/80 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                        <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                            This will permanently remove this benefit record. The activity log will still retain the history.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Benefit</p>
                            <p class="text-sm font-semibold mt-0.5 truncate">{{ benefit.benefit_name }}</p>
                        </div>
                        <div class="bg-muted/40 rounded-lg p-3 border border-border/50">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Date</p>
                            <p class="text-sm font-semibold mt-0.5">{{ benefit.date ? String(benefit.date).split('T')[0] : '—' }}</p>
                        </div>
                    </div>
                    <div v-if="benefit.description" class="bg-muted/30 rounded-lg p-3 border border-border/40">
                        <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Description</p>
                        <p class="text-sm mt-0.5">{{ benefit.description }}</p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-2 border-t border-border/50">
                        <Button type="button" variant="outline" class="h-9 px-5 font-semibold shadow-sm" @click="isOpen = false">Cancel</Button>
                        <Button @click="submit" class="h-9 px-5 bg-destructive hover:bg-destructive/90 text-white font-bold tracking-wider shadow-sm" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Deleting…' : 'Confirm Delete' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
