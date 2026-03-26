<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X } from 'lucide-vue-next';

const props = defineProps({
    level: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({ ...props.level });

const levelImage = ref<File | null>(null);
const levelIcon = ref<File | null>(null);

const rules = ref<{name: string, value: string, status: boolean, value_type: string}[]>([]);

// Pre-fill rules when opening
watch(isOpen, (newVal) => {
    if (newVal) {
        form.value = {
            ...props.level,
            earned_expire_days: props.level.earned_expire_days ?? 730,
            bonus_expire_days: props.level.bonus_expire_days ?? 730,
        };
        levelImage.value = null;
        levelIcon.value = null;
        if (props.level.level_rules) {
            rules.value = typeof props.level.level_rules === 'string' 
                ? JSON.parse(props.level.level_rules) 
                : props.level.level_rules;
        } else {
            rules.value = [];
        }
    }
});

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files?.length) levelImage.value = target.files[0];
};

const handleIconUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files?.length) levelIcon.value = target.files[0];
};

const addRule = () => {
    rules.value.push({ name: '', value: '', status: true, value_type: 'string' });
};
const removeRule = (index: number) => {
    rules.value.splice(index, 1);
};

const submit = async () => {
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT'); // Laravel spoofing since HTML forms don't support PUT with FormData natively in some contexts, but Axios POST over multipart is better tested this way.
        formData.append('name', form.value.name);
        formData.append('min_points', String(form.value.min_points));
        if (form.value.max_points !== '' && form.value.max_points !== null) {
            formData.append('max_points', String(form.value.max_points));
        }
        formData.append('status', String(form.value.status));
        if (form.value.redeemation_points_per_dollar) {
            formData.append('redeemation_points_per_dollar', String(form.value.redeemation_points_per_dollar));
        }
        formData.append('earned_expire_days', String(form.value.earned_expire_days || 730));
        formData.append('bonus_expire_days', String(form.value.bonus_expire_days || 730));
        
        if (levelImage.value) formData.append('level_image', levelImage.value);
        if (levelIcon.value) formData.append('level_icon', levelIcon.value);
        if (rules.value.length > 0) formData.append('level_rules', JSON.stringify(rules.value));
        else formData.append('level_rules', ''); // clear rules

        await axios.post(`/internal-api/gratitude/levels/${props.level.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        isOpen.value = false;
        emit('saved');
    } catch (error) {
        console.error('Error updating level', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="outline" size="sm">Edit</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border m-4 max-h-[90vh] overflow-y-auto text-left">
                <h2 class="text-xl font-bold mb-4">Update Gratitude Level</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Name</Label>
                            <Input v-model="form.name" required />
                        </div>
                        <div class="flex items-end space-x-2 pb-2">
                            <input type="checkbox" v-model="form.status" id="status_edit" class="rounded border-input text-primary focus:ring-primary h-4 w-4" />
                            <Label for="status_edit">Active</Label>
                        </div>
                        <div>
                            <Label>Min Points</Label>
                            <Input type="number" v-model="form.min_points" required />
                        </div>
                        <div>
                            <Label>Max Points (Leave blank for ∞)</Label>
                            <Input type="number" v-model="form.max_points" />
                        </div>
                        <div class="col-span-2">
                            <Label>Points Per Dollar (Redemption Rate)</Label>
                            <Input type="number" step="0.01" min="1" v-model="form.redeemation_points_per_dollar" required />
                            <p class="text-xs text-muted-foreground mt-1">How many points equal $1 in value. Explorer=35, Globetrotter=30, Jetsetter=25</p>
                        </div>
                        <div>
                            <Label>Earned Points Expire After (Days)</Label>
                            <Input type="number" min="1" v-model="form.earned_expire_days" required />
                        </div>
                        <div>
                            <Label>Bonus Points Expire After (Days)</Label>
                            <Input type="number" min="1" v-model="form.bonus_expire_days" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Level Image (Leave blank to keep current)</Label>
                            <Input type="file" accept="image/*" @change="handleImageUpload" />
                        </div>
                        <div>
                            <Label>Level Icon (Leave blank to keep current)</Label>
                            <Input type="file" accept="image/*" @change="handleIconUpload" />
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <Label class="text-lg font-semibold">Rules Repeater</Label>
                            <Button type="button" variant="outline" size="sm" @click="addRule">Add Rule</Button>
                        </div>
                        <div v-for="(rule, index) in rules" :key="index" class="p-4 border rounded-md mb-2 bg-muted/10">
                            <div class="grid grid-cols-12 gap-2 items-end">
                                <div class="col-span-4">
                                    <Label>Rule Name</Label>
                                    <Input v-model="rule.name" placeholder="e.g., discount_rate" required />
                                </div>
                                <div class="col-span-3">
                                    <Label>Value</Label>
                                    <Input v-model="rule.value" required />
                                </div>
                                <div class="col-span-3">
                                    <Label>Value Type</Label>
                                    <select v-model="rule.value_type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                                        <option value="string">String</option>
                                        <option value="number">Number</option>
                                        <option value="boolean">Boolean</option>
                                    </select>
                                </div>
                                <div class="col-span-1 flex justify-center pb-2">
                                    <input type="checkbox" v-model="rule.status" title="Active" class="rounded border-input text-primary focus:ring-primary h-4 w-4" />
                                </div>
                                <div class="col-span-1 flex justify-end pb-1">
                                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="removeRule(index)">
                                        <X class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <p v-if="rules.length === 0" class="text-sm text-muted-foreground text-center py-2">No rules added.</p>
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
