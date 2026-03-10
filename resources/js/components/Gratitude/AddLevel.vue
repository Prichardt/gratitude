<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X } from 'lucide-vue-next';

const emit = defineEmits(['saved']);
const isOpen = ref(false);
const form = ref({
    name: '',
    min_points: 0,
    max_points: '' as string | number,
    status: true,
});
const levelImage = ref<File | null>(null);
const levelIcon = ref<File | null>(null);

const rules = ref<{name: string, value: string, status: boolean, value_type: string}[]>([]);

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
        formData.append('name', form.value.name);
        formData.append('min_points', String(form.value.min_points));
        if (form.value.max_points !== '' && form.value.max_points !== null) {
            formData.append('max_points', String(form.value.max_points));
        }
        formData.append('status', String(form.value.status));
        
        if (levelImage.value) formData.append('level_image', levelImage.value);
        if (levelIcon.value) formData.append('level_icon', levelIcon.value);
        if (rules.value.length > 0) formData.append('level_rules', JSON.stringify(rules.value));

        await axios.post('/internal-api/gratitude/levels', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        isOpen.value = false;
        form.value = { name: '', min_points: 0, max_points: '', status: true };
        levelImage.value = null;
        levelIcon.value = null;
        rules.value = [];
        emit('saved');
    } catch (error) {
        console.error('Error saving level', error);
    }
};
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="default">Add Level</Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border m-4 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold mb-4">Add Gratitude Level</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Name</Label>
                            <Input v-model="form.name" required />
                        </div>
                        <div class="flex items-end space-x-2 pb-2">
                            <input type="checkbox" v-model="form.status" id="status" class="rounded border-input text-primary focus:ring-primary h-4 w-4" />
                            <Label for="status">Active</Label>
                        </div>
                        <div>
                            <Label>Min Points</Label>
                            <Input type="number" v-model="form.min_points" required />
                        </div>
                        <div>
                            <Label>Max Points (Leave blank for ∞)</Label>
                            <Input type="number" v-model="form.max_points" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Level Image</Label>
                            <Input type="file" accept="image/*" @change="handleImageUpload" />
                        </div>
                        <div>
                            <Label>Level Icon</Label>
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
