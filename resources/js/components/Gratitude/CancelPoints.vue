<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    gratitudeNumber: { type: String, required: true }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    date: '',
    cancellation_reason: '',
    cancellation_points: 0,
});

const submit = async () => {
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/cancel`, form.value);
        isOpen.value = false;
        form.value = { date: '', cancellation_reason: '', cancellation_points: 0 };
        emit('saved');
    } catch (error) {
        console.error('Error processing cancellation', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="destructive" size="sm">Cancel Points</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-sm p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Process Cancellation</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Date</Label>
                        <Input type="date" v-model="form.date" required />
                    </div>
                    <div>
                        <Label>Reason</Label>
                        <Input v-model="form.cancellation_reason" required />
                    </div>
                    <div>
                        <Label>Points to Deduct</Label>
                        <Input type="number" v-model="form.cancellation_points" required />
                    </div>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" variant="destructive">Process</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
