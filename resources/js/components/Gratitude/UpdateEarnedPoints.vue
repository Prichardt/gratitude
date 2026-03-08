<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    point: { type: Object, required: true },
    gratitudeNumber: { type: String, required: true }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    date: props.point.date,
    category: props.point.category,
    points: props.point.points,
});

const submit = async () => {
    try {
        await axios.put(`/internal-api/gratitude/${props.gratitudeNumber}/earned/${props.point.id}`, form.value);
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error updating earned points', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="outline" size="sm">Edit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-sm p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Update Earned Points</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Date</Label>
                        <Input type="date" v-model="form.date" required />
                    </div>
                    <div>
                        <Label>Category</Label>
                        <Input v-model="form.category" required />
                    </div>
                    <div>
                        <Label>Points</Label>
                        <Input type="number" v-model="form.points" required />
                    </div>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Update</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
