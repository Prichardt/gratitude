<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    benefit: { type: Object, required: true },
    levels: { type: Array, required: true }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    level_mappings: {} as Record<number, any>
});

const openModal = () => {
    // Populate form with existing mappings
    props.levels.forEach((l: any) => {
        const pivot = props.benefit.levels[l.id];
        form.value.level_mappings[l.id] = {
            enabled: pivot?.has_benefit || false,
            value: pivot?.value || '',
            description: pivot?.description || '',
            value_type: pivot?.value_type || 'fixed'
        };
    });
    isOpen.value = true;
};

const submit = async () => {
    try {
        await axios.put(`/internal-api/gratitude/program-benefits/${props.benefit.id}`, form.value);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error updating program level benefit', error);
    }
};
</script>

<template>
    <div>
        <Button @click="openModal" variant="outline" size="sm">Edit Levels</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold mb-1">Assign "{{ benefit.name }}" to Levels</h2>
                <p class="text-sm text-muted-foreground mb-4">Select which tier levels receive this benefit and specify their exact values.</p>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="mt-4">
                        <div v-for="level in (levels as any[])" :key="level.id" class="p-3 border border-border rounded-md mb-2 bg-muted/20">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.level_mappings[level.id].enabled" :id="'edit_level_'+level.id" class="rounded border-input text-primary h-4 w-4" />
                                    <Label :for="'edit_level_'+level.id" class="font-bold">{{ level.name }}</Label>
                                </div>
                            </div>
                            <div v-if="form.level_mappings[level.id].enabled" class="grid grid-cols-2 gap-2 mt-2">
                                <div>
                                    <Label class="text-xs text-muted-foreground">Value</Label>
                                    <Input v-model="form.level_mappings[level.id].value" placeholder="e.g. 5,000 pts" size="sm" />
                                </div>
                                <div>
                                    <Label class="text-xs text-muted-foreground">Details/Description</Label>
                                    <Input v-model="form.level_mappings[level.id].description" size="sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Save Assignments</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
