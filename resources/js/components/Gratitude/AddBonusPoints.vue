<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    expireDays: { type: Number, default: 730 },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    date: '',
    description: '',
    points: 0,
});

const submit = async () => {
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/bonus`, form.value);
        isOpen.value = false;
        form.value = { date: '', description: '', points: 0 };
        emit('saved');
    } catch (error) {
        console.error('Error adding bonus points', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="secondary" size="sm">Add Bonus Points</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-sm p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Add Bonus Points</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Date</Label>
                        <Input type="date" v-model="form.date" required />
                    </div>
                    <div>
                        <Label>Description/Reason</Label>
                        <Input v-model="form.description" required />
                    </div>
                    <div>
                        <Label>Points</Label>
                        <Input type="number" v-model="form.points" required />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Bonus points on this level expire after {{ props.expireDays }} days from the selected date.
                    </p>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
