<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    expireDays: { type: Number, default: 730 },
    guests: { type: Array, default: () => [] },
    journeys: { type: Array, default: () => [] },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    type: 'other',
    category: 'other',
    date: new Date().toISOString().split('T')[0],
    description: '',
    points: 0,
    guest_id: '',
    guest_name: '',
    journey_id: '',
});

const guestOptions = computed(() => props.guests as any[]);
const journeyOptions = computed(() => props.journeys as any[]);
const guestName = (guest: any) =>
    [guest.preferred_name, guest.first_name, guest.last_name].filter(Boolean).join(' ')
    || guest.name
    || guest.full_name
    || `Guest #${guest.guest_id || guest.id}`;
const selectedGuest = computed(() =>
    guestOptions.value.find((guest: any) => String(guest.guest_id || guest.id) === String(form.value.guest_id)),
);
const journeysForGuest = computed(() => {
    if (!form.value.guest_id) return journeyOptions.value;

    const attached = journeyOptions.value.filter((journey: any) => String(journey.guest_id || '') === String(form.value.guest_id));

    return attached.length ? attached : journeyOptions.value;
});
const selectedJourney = computed(() =>
    journeysForGuest.value.find((journey: any) => String(journey.journey_id || journey.id) === String(form.value.journey_id)),
);
const selectedJourneyEndDate = computed(() =>
    selectedJourney.value?.endDate
    || selectedJourney.value?.raw?.endDate
    || selectedJourney.value?.raw?.end_date
    || selectedJourney.value?.raw?.returnDate
    || selectedJourney.value?.raw?.return_date
    || '',
);
const isReferralBonus = computed(() => form.value.type === 'referring_guest');
const canSubmit = computed(() =>
    !isReferralBonus.value || (!!form.value.guest_id && !!form.value.journey_id && !!selectedJourneyEndDate.value),
);

const selectGuest = () => {
    const guest = selectedGuest.value;
    form.value.guest_name = guest ? guestName(guest) : '';
    form.value.journey_id = '';
};

const submit = async () => {
    if (!canSubmit.value) return;

    try {
        const payload: any = { ...form.value };

        if (isReferralBonus.value) {
            payload.category = 'referring_guest';
            payload.date = selectedJourneyEndDate.value;
            payload.journey_end_date = selectedJourneyEndDate.value;
            payload.journey_data = selectedJourney.value?.raw || selectedJourney.value || null;
            payload.guest_name = form.value.guest_name || (selectedGuest.value ? guestName(selectedGuest.value) : '');
        } else {
            payload.guest_id = null;
            payload.guest_name = null;
            payload.journey_id = null;
            payload.journey_end_date = null;
            payload.journey_data = null;
        }

        await axios.post(`/internal-api/gratitude/${props.gratitudeNumber}/bonus`, payload);
        isOpen.value = false;
        form.value = {
            type: 'other',
            category: 'other',
            date: new Date().toISOString().split('T')[0],
            description: '',
            points: 0,
            guest_id: '',
            guest_name: '',
            journey_id: '',
        };
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
            <div class="bg-card w-full max-w-lg p-6 rounded-lg shadow-lg border border-border">
                <h2 class="text-xl font-bold mb-4">Add Bonus Points</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label>Bonus Type</Label>
                        <select v-model="form.type" class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" required>
                            <option value="other">Other</option>
                            <option value="referring_guest">Referring a Guest</option>
                        </select>
                    </div>
                    <div v-if="isReferralBonus">
                        <Label>Guest</Label>
                        <select v-model="form.guest_id" class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" required @change="selectGuest">
                            <option value="" disabled>Select a guest</option>
                            <option v-for="guest in guestOptions" :key="guest.guest_id || guest.id" :value="guest.guest_id || guest.id">
                                {{ guestName(guest) }}
                            </option>
                        </select>
                    </div>
                    <div v-if="isReferralBonus">
                        <Label>Guest Journey</Label>
                        <select v-model="form.journey_id" class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" required>
                            <option value="" disabled>Select a journey</option>
                            <option v-for="journey in journeysForGuest" :key="`${journey.guest_id || 'account'}-${journey.journey_id || journey.id}`" :value="journey.journey_id || journey.id">
                                {{ journey.label || `Journey #${journey.journey_id || journey.id}` }}
                            </option>
                        </select>
                    </div>
                    <div v-if="!isReferralBonus">
                        <Label>Effective Date</Label>
                        <Input type="date" v-model="form.date" required />
                    </div>
                    <div v-else>
                        <Label>Effective Date</Label>
                        <div class="mt-1 flex h-10 items-center rounded-md border border-input bg-muted/30 px-3 text-sm text-muted-foreground">
                            {{ selectedJourneyEndDate || 'Select a journey with an end date' }}
                        </div>
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
                        Bonus points on this level expire after {{ props.expireDays }} days from the effective date.
                    </p>
                    <div class="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="!canSubmit">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
