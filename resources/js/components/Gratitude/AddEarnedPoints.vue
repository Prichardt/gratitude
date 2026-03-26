<script setup lang="ts">
import { ref, computed } from 'vue';
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
const isSubmitting = ref(false);

const form = ref({
    journey_id: '',
    category: 'Guest',
    date: new Date().toISOString().split('T')[0],
    amount: '',
    points: 0,
    description: 'Tier Points Earned on Journey'
});

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/earned`, form.value);
        isOpen.value = false;
        // Reset form
        form.value.amount = '';
        form.value.points = 0;
        emit('saved');
    } catch (error) {
        console.error('Error adding points', error);
    } finally {
        isSubmitting.value = false;
    }
};

// If there's an automatic formula for amount -> points, they can hook it here. 
// For now, they are independent fields side-by-side as requested.
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="default" size="sm" class="bg-[#0f2e4a] hover:bg-[#0b2238] text-white">Add Earned Points</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-2xl rounded-lg shadow-xl border border-border overflow-hidden">
                <!-- Header -->
                <div class="bg-[#0f2e4a] text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-semibold tracking-wide">Add Earned Points</h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    
                    <!-- Journey -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Journey</Label>
                        <select v-model="form.journey_id" class="w-full flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 text-foreground">
                            <option value="" disabled>Select Journey</option>
                            <!-- Options populate here -->
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Category</Label>
                        <select v-model="form.category" class="w-full flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 text-foreground" required>
                            <option value="Guest">Guest</option>
                            <option value="Guest of travel Agency">Guest of travel Agency</option>
                            <option value="third party cruise">third party cruise</option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Date</Label>
                        <Input type="date" v-model="form.date" required class="h-10" />
                    </div>

                    <!-- Amount & Total Points -->
                    <div class="space-y-1.5 p-3 bg-muted/30 rounded-md border border-border">
                        <Label class="text-xs font-semibold text-foreground/80">Amount Paid & Equivalent Points</Label>
                        <div class="flex flex-col sm:flex-row gap-4 items-center">
                            <Input type="number" step="0.01" v-model="form.amount" placeholder="Enter Amount Paid" class="flex-1 h-10" required />
                            <div class="flex items-center gap-3 w-full sm:w-auto bg-card border border-border rounded-md px-4 h-10 shadow-sm">
                                <span class="text-sm font-medium text-muted-foreground">Total Points:</span>
                                <input type="number" v-model="form.points" class="w-24 bg-transparent outline-none text-right font-bold text-foreground" required />
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-foreground/80">Description</Label>
                        <Input v-model="form.description" placeholder="Tier Points Earned on Journey" required class="h-10" />
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Earned points on this level expire after {{ props.expireDays }} days from the selected date.
                    </p>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-border mt-6">
                        <Button type="button" variant="outline" class="h-9 px-6 font-semibold shadow-sm" @click="isOpen = false">CANCEL</Button>
                        <Button type="submit" class="h-9 px-6 bg-[#0f2e4a] hover:bg-black text-white font-semibold tracking-wider shadow-sm" :disabled="isSubmitting">
                            {{ isSubmitting ? 'SAVING...' : 'SUBMIT' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
