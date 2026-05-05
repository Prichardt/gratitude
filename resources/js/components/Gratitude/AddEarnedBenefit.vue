<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    availableBenefits: { type: Array as () => any[], default: () => [] },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);
const error = ref('');

const blank = () => ({
    benefit_id: '',
    benefit_name: '',
    benefit_key: '',
    description: '',
    journey_id: '',
    benefit_value: '',
    value_type: '',
    project_data_raw: '',
    date: new Date().toISOString().split('T')[0],
    status: 'active',
    notes: '',
});

const form = ref(blank());

const onBenefitSelect = () => {
    const selected = props.availableBenefits.find((b: any) => String(b.id) === String(form.value.benefit_id));
    if (selected) {
        if (!form.value.benefit_name) form.value.benefit_name = selected.name;
        if (!form.value.benefit_key)  form.value.benefit_key  = selected.benefit_key ?? '';
    }
};

const submit = async () => {
    error.value = '';
    isSubmitting.value = true;
    try {
        let project_data: any = null;
        if (form.value.project_data_raw.trim()) {
            try { project_data = JSON.parse(form.value.project_data_raw); } catch { /* leave null */ }
        }
        const payload: any = { ...form.value, project_data };
        delete payload.project_data_raw;
        if (!payload.benefit_id)  delete payload.benefit_id;
        if (!payload.journey_id)  delete payload.journey_id;
        if (!payload.benefit_key) delete payload.benefit_key;

        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/earned-benefits`, payload);
        isOpen.value = false;
        form.value = blank();
        emit('saved');
    } catch (err: any) {
        error.value = err?.response?.data?.message ?? 'Failed to save. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="secondary" size="sm" class="h-8 text-xs font-bold tracking-wider uppercase flex items-center gap-1.5">
            <Plus class="w-3.5 h-3.5" /> Add Benefit
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-lg rounded-xl shadow-2xl border border-border overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-primary text-primary-foreground px-6 py-4 flex justify-between items-center shrink-0">
                    <h2 class="text-sm font-semibold tracking-wide flex items-center gap-2">
                        <Plus class="w-4 h-4 opacity-80" /> Record Earned Benefit
                    </h2>
                    <button @click="isOpen = false" class="opacity-70 hover:opacity-100 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4 overflow-y-auto">
                    <!-- Benefit picker -->
                    <div v-if="availableBenefits.length">
                        <Label>Benefit Template <span class="text-muted-foreground font-normal">(optional)</span></Label>
                        <select v-model="form.benefit_id" @change="onBenefitSelect"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring">
                            <option value="">— Select a benefit —</option>
                            <option v-for="b in availableBenefits" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <Label>Benefit Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.benefit_name" placeholder="e.g. Room Upgrade" required class="mt-1" />
                        </div>
                        <div>
                            <Label>Benefit Value</Label>
                            <Input v-model="form.benefit_value" placeholder="e.g. 1 Night Free" class="mt-1" />
                        </div>
                        <div>
                            <Label>Value Type</Label>
                            <select v-model="form.value_type"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="">— None —</option>
                                <option value="amount">Amount</option>
                                <option value="percentage">Percentage</option>
                                <option value="points">Points</option>
                                <option value="item">Item / Perk</option>
                                <option value="nights">Nights</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <Label>Description <span class="text-destructive">*</span></Label>
                        <textarea v-model="form.description" rows="2" required
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                            placeholder="Details about this benefit usage…" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Date <span class="text-destructive">*</span></Label>
                            <Input type="date" v-model="form.date" required class="mt-1" />
                        </div>
                        <div>
                            <Label>Journey ID <span class="text-muted-foreground font-normal">(optional)</span></Label>
                            <Input type="number" v-model="form.journey_id" placeholder="Journey ID" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <Label>Project Data <span class="text-muted-foreground font-normal">(JSON, optional)</span></Label>
                        <textarea v-model="form.project_data_raw" rows="2"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono shadow-sm focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                            placeholder='{"projectNumber": "P-001", "name": "The Lodge"}' />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Status</Label>
                            <select v-model="form.status"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring">
                                <option value="active">Active</option>
                                <option value="used">Used</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <Label>Benefit Key <span class="text-muted-foreground font-normal">(optional)</span></Label>
                            <Input v-model="form.benefit_key" placeholder="e.g. room_upgrade" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <Label>Notes <span class="text-muted-foreground font-normal">(optional)</span></Label>
                        <textarea v-model="form.notes" rows="2"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring resize-none" />
                    </div>

                    <p v-if="error" class="text-sm text-destructive bg-destructive/10 px-3 py-2 rounded-md">{{ error }}</p>

                    <div class="flex justify-end space-x-3 pt-2 border-t border-border/50">
                        <Button type="button" variant="outline" class="h-9 px-5 font-semibold" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" class="h-9 px-5 font-bold tracking-wider" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving…' : 'Save Benefit' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
