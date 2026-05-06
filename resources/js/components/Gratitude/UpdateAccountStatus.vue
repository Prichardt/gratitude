<script setup lang="ts">
import { ref, watch } from 'vue';
import type { PropType } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X } from 'lucide-vue-next';

const props = defineProps({
    gratitudeNumber: { type: String, required: true },
    gratitude: { type: Object, required: true },
    levels: { type: Array as PropType<any[]>, default: () => [] },
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const isSubmitting = ref(false);

const form = ref({
    status: 'active',
    change_level: false,
    level: '',
    reason: 'Manual status update',
});

watch(isOpen, (open) => {
    if (!open) return;

    form.value = {
        status: String(props.gratitude?.status || 'active').toLowerCase() === 'inactive' ? 'inactive' : 'active',
        change_level: false,
        level: String(props.gratitude?.level || ''),
        reason: 'Manual status update',
    };
});

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.patch(`/internal-api/gratitude/account/${props.gratitudeNumber}/status`, form.value);
        isOpen.value = false;
        emit('saved');
    } catch (error: any) {
        console.error('Error updating account status', error);
        alert(error.response?.data?.message || 'Failed to update account status.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div>
        <Button
            class="bg-blue-600 hover:bg-blue-700 text-white shadow-md transition-all h-10 px-6 text-xs font-bold tracking-wider uppercase rounded-lg"
            @click="isOpen = true"
        >
            Update Status
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4 text-left">
            <div class="bg-card w-full max-w-3xl rounded-lg shadow-xl border border-border overflow-hidden">
                <div class="bg-[#0f2e4a] text-white px-5 py-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold tracking-wide">Gratitude Status Update</h2>
                    <button type="button" class="text-white/70 hover:text-white transition-colors" @click="isOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-5 space-y-5">
                    <div class="grid gap-5 md:grid-cols-[1fr_220px]">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground/80">Gratitude Level</Label>
                            <select
                                v-model="form.level"
                                :disabled="!form.change_level"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option v-for="level in levels" :key="level.id" :value="level.name">
                                    {{ level.name }}
                                </option>
                            </select>
                        </div>

                        <label class="flex items-center gap-2 pt-7 text-sm text-foreground">
                            <input
                                v-model="form.change_level"
                                type="checkbox"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <span>Change Gratitude Level</span>
                        </label>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground/80">Status</Label>
                            <select
                                v-model="form.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground/80">Reason</Label>
                            <Input v-model="form.reason" class="h-10" placeholder="Reason for this update" />
                        </div>
                    </div>

                    <p v-if="form.change_level" class="text-xs text-muted-foreground">
                        Level changes start a fresh 2-year window from today and switch automatic tier updates off for this account.
                    </p>

                    <div class="flex justify-end gap-3 border-t border-border pt-4">
                        <Button type="button" variant="outline" class="h-9 px-6 font-semibold" @click="isOpen = false">Cancel</Button>
                        <Button type="submit" class="h-9 px-6 bg-[#0f2e4a] hover:bg-black text-white font-semibold tracking-wider" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Updating...' : 'Update Status' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
