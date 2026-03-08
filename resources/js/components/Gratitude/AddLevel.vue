<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    name: '',
    min_points: 0,
    max_points: null,
    status: true,
});

const submit = async () => {
    try {
        await axios.post('/internal-api/gratitude/levels', form.value);
        isOpen.value = false;
        form.value = { name: '', min_points: 0, max_points: null, status: true };
        emit('saved');
    } catch (error) {
        console.error('Error saving level', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="default">Add Level</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-card w-full max-w-md p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Add Gratitude Level</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Name</Label>
                        <Input v-model="form.name" required />
                    </div>
                    <div>
                        <Label>Min Points</Label>
                        <Input type="number" v-model="form.min_points" required />
                    </div>
                    <div>
                        <Label>Max Points (Leave blank for ∞)</Label>
                        <Input type="number" v-model="form.max_points" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" v-model="form.status" id="status" class="rounded border-input text-primary focus:ring-primary" />
                        <Label for="status">Active</Label>
                    </div>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
