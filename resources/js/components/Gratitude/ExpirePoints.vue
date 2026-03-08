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
    points: 0,
});

const submit = async () => {
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/expire`, form.value);
        isOpen.value = false;
        form.value = { date: '', points: 0 };
        emit('saved');
    } catch (error) {
        console.error('Error expiring points', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="ghost" size="sm" class="text-orange-600 hover:text-orange-700">Expire Points</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-sm p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Expire Points</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Date of Expiration</Label>
                        <Input type="date" v-model="form.date" required />
                    </div>
                    <div>
                        <Label>Points to Expire</Label>
                        <Input type="number" v-model="form.points" required />
                    </div>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" variant="default" class="bg-orange-600 hover:bg-orange-700">Expire</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
