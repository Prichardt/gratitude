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
    description: '',
    status: true,
});

const submit = async () => {
    try {
        await axios.post('/internal-api/gratitude/benefits', form.value);
        isOpen.value = false;
        form.value = { name: '', description: '', status: true };
        emit('saved');
    } catch (error) {
        console.error('Error creating base benefit', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" size="sm">Add Benefit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-lg p-6 rounded-lg shadow-lg border border-border m-4">
                <h2 class="text-xl font-bold mb-4">Add Base Benefit</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Name</Label>
                        <Input v-model="form.name" required placeholder="e.g. Priority Support" />
                    </div>
                    <div>
                        <Label>Description</Label>
                        <Input v-model="form.description" placeholder="Optional details about this benefit" />
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" v-model="form.status" id="base_status" class="rounded border-input text-primary h-4 w-4" />
                        <Label for="base_status">Active</Label>
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
