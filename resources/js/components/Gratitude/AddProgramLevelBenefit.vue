<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    levels: { type: Array, required: true },
    benefits: { type: Array, required: false, default: () => [] }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const mode = ref('new'); // 'new' or 'existing'

const form = ref({
    existing_id: '',
    name: '',
    benefit_key: '',
    description: '',
    is_active: true,
    type: 'base',
    level_mappings: {} as Record<number, any>
});

const unassignedBenefits = computed(() => {
    return props.benefits.filter((b: any) => {
        return !Object.values(b.levels || {}).some((l: any) => l.has_benefit);
    });
});

const resetForm = () => {
    form.value.existing_id = '';
    form.value.name = '';
    form.value.benefit_key = '';
    form.value.description = '';
    form.value.is_active = true;
    form.value.type = 'base';
    form.value.level_mappings = {};
    
    props.levels.forEach((l: any) => {
        form.value.level_mappings[l.id] = {
            enabled: false,
            value: '',
            description: '',
            value_type: 'fixed',
            is_active: true,
            web_status: true
        };
    });
};

const openModal = () => {
    resetForm();
    mode.value = 'new';
    isOpen.value = true;
};

watch(() => form.value.level_mappings, (newVal) => {
    if (!newVal) return;
    Object.keys(newVal).forEach(key => {
        if (newVal[key as any].is_active === false) {
            newVal[key as any].web_status = false;
        }
    });
}, { deep: true });

const submit = async () => {
    try {
        if (mode.value === 'existing') {
            if (!form.value.existing_id) {
                alert('Please select an existing benefit');
                return;
            }
            await axios.put(`/internal-api/gratitude/program-benefits/${form.value.existing_id}`, {
                level_mappings: form.value.level_mappings
            });
        } else {
            if (!form.value.name.trim()) {
                alert('Please enter a name for the new base benefit');
                return;
            }
            await axios.post('/internal-api/gratitude/benefits', form.value);
        }
        
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error saving benefit', error);
    }
};
</script>

<template>
    <div>
        <Button @click="openModal" size="sm">Add Benefit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-3xl p-6 rounded-lg shadow-lg border border-border max-h-[90vh] overflow-y-auto m-4">
                <h2 class="text-xl font-bold mb-4">Add Benefit Assignments</h2>
                
                <div class="mb-6 flex space-x-6 border-b border-border pb-3">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" v-model="mode" value="new" class="text-primary focus:ring-primary h-4 w-4" />
                        <span class="text-sm font-medium">Create New Base Benefit</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" v-model="mode" value="existing" class="text-primary focus:ring-primary h-4 w-4" />
                        <span class="text-sm font-medium text-foreground">Select Existing Benefit</span>
                    </label>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <template v-if="mode === 'new'">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Name</Label>
                                <Input v-model="form.name" required placeholder="e.g. Priority Support" />
                            </div>
                            <div>
                                <Label>Benefit Key</Label>
                                <Input v-model="form.benefit_key" placeholder="e.g. priority_support" class="font-mono" />
                                <p class="text-xs text-muted-foreground mt-1">Unique snake_case key for feature gating.</p>
                            </div>
                        </div>
                        <div>
                            <Label>Description</Label>
                            <Input v-model="form.description" placeholder="Optional details about this benefit" />
                        </div>
                        <div class="flex items-center space-x-2 pt-2">
                            <input type="checkbox" v-model="form.is_active" id="base_status" class="rounded border-input text-primary h-4 w-4" />
                            <Label for="base_status">Base Active Status</Label>
                        </div>
                    </template>
                    
                    <template v-else>
                        <div class="bg-muted/30 p-4 border border-border rounded-lg">
                            <Label>Select Unassigned Base Benefit</Label>
                            <select v-model="form.existing_id" required class="mt-1 flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                <option value="" disabled>Select an unassigned benefit...</option>
                                <option v-for="b in (unassignedBenefits as any[])" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="unassignedBenefits.length === 0" class="text-xs text-destructive mt-2 font-medium">There are no completely unassigned benefits available. All imported benefits already have at least one level assigned.</p>
                            <p v-else class="text-xs text-muted-foreground mt-2">Select a benefit that was created or imported but not yet mapped to any tier.</p>
                        </div>
                    </template>

                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold mb-2">Level Assignments (Optional)</h3>
                        <p class="text-sm text-muted-foreground mb-4">You can assign this benefit to levels right now, or do it later.</p>
                        <div v-for="level in (levels as any[])" :key="level.id" class="p-3 border border-border rounded-md mb-2 bg-muted/20">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.level_mappings[level.id].enabled" :id="'add_level_'+level.id" class="rounded border-input text-primary h-4 w-4" />
                                    <Label :for="'add_level_'+level.id" class="font-bold">{{ level.name }}</Label>
                                </div>
                            </div>
                            <div v-if="form.level_mappings[level.id].enabled" class="mt-2 grid grid-cols-12 gap-3">
                                <div class="col-span-4">
                                    <Label class="text-xs text-muted-foreground">Value</Label>
                                    <Input v-model="form.level_mappings[level.id].value" placeholder="e.g. 5,000 pts" size="sm" />
                                </div>
                                <div class="col-span-4">
                                    <Label class="text-xs text-muted-foreground">Details/Description</Label>
                                    <Input v-model="form.level_mappings[level.id].description" size="sm" />
                                </div>
                                <div class="col-span-2 flex items-center pt-5 pl-2 space-x-2">
                                    <input type="checkbox" v-model="form.level_mappings[level.id].is_active" :id="'new_is_active_'+level.id" class="rounded border-input text-primary h-4 w-4" />
                                    <Label :for="'new_is_active_'+level.id" class="text-xs">System Status</Label>
                                </div>
                                <div class="col-span-2 flex items-center pt-5 space-x-2">
                                    <input type="checkbox" v-model="form.level_mappings[level.id].web_status" :disabled="!form.level_mappings[level.id].is_active" :id="'new_web_status_'+level.id" class="rounded border-input text-primary h-4 w-4 disabled:opacity-50" />
                                    <Label :for="'new_web_status_'+level.id" class="text-xs" :class="{'text-muted-foreground': !form.level_mappings[level.id].is_active}">Web Status</Label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6 border-t pt-4">
                        <Button type="button" variant="outline" @click="isOpen = false">Cancel</Button>
                        <Button type="submit">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
