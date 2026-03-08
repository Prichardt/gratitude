<script setup lang="ts">
import { ref } from 'vue';
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
    name: props.benefit.name,
    description: props.benefit.description,
    is_active: props.benefit.is_active !== undefined ? props.benefit.is_active : true,
    level_mappings: {}
});

const openModal = () => {
    // Populate form with existing mappings
    props.levels.forEach(l => {
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
        await axios.put(`/internal-api/gratitude/benefits/${props.benefit.id}`, form.value);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error updating benefit', error);
    }
};
</script>

<template>
    <div>
        <Button @click="openModal" variant="outline" size="sm">Edit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold mb-4">Edit Gratitude Benefit</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Name</Label>
                        <Input v-model="form.name" required />
                    </div>
                    <div>
                        <Label>Description</Label>
                        <Input v-model="form.description" />
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-md font-semibold mb-2">Assign to Levels</h3>
                        <div v-for="level in levels" :key="level.id" class="p-3 border border-border rounded-md mb-2 bg-muted/20">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.level_mappings[level.id].enabled" :id="'edit_level_'+level.id" class="rounded border-input text-primary" />
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
                        <Button type="submit">Update Benefit</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
