<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    levels: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    name: '',
    description: '',
    type: 'fixed',
    is_active: true,
    level_mappings: {} // Store { levelId: { enabled: true, value: '' } }
});

const initMappings = () => {
    props.levels.forEach(l => {
        form.value.level_mappings[l.id] = { enabled: false, value: '', description: '', value_type: 'fixed' };
    });
};

const submit = async () => {
    try {
        await axios.post('/internal-api/gratitude/benefits', form.value);
        isOpen.value = false;
        form.value = { name: '', description: '', type: 'fixed', is_active: true, level_mappings: {} };
        emit('saved');
    } catch (error) {
        console.error('Error saving benefit', error);
    }
};

const openModal = () => {
    initMappings();
    isOpen.value = true;
};
</script>

<template>
    <div>
        <Button @click="openModal" variant="default">Add Benefit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border max-h-[90vh] overflow-y-auto w-full">
                <h2 class="text-xl font-bold mb-4">Add Gratitude Benefit</h2>
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
                                    <input type="checkbox" v-model="form.level_mappings[level.id].enabled" :id="'level_'+level.id" class="rounded border-input text-primary" />
                                    <Label :for="'level_'+level.id" class="font-bold">{{ level.name }}</Label>
                                </div>
                            </div>
                            <div v-if="form.level_mappings[level.id].enabled" class="grid grid-cols-2 gap-2 mt-2">
                                <div>
                                    <Label class="text-xs text-muted-foreground">Value</Label>
                                    <Input v-model="form.level_mappings[level.id].value" placeholder="e.g. 5,000 pts" size="sm" />
                                </div>
                                <div>
                                    <Label class="text-xs text-muted-foreground">Details/Description</Label>
                                    <Input v-model="form.level_mappings[level.id].description" placeholder="Optional breakdown" size="sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" v-model="form.is_active" id="benefit_active" class="rounded border-input text-primary" />
                        <Label for="benefit_active">Active</Label>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Save Benefit</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
