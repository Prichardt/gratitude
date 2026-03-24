<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-vue-next';

const props = defineProps({
    benefit: { type: Object, required: true },
    levels: { type: Array, required: true }
});

const isOpen = ref(false);

const openModal = () => {
    isOpen.value = true;
};
</script>

<template>
    <div>
        <Button @click="openModal" variant="ghost" size="icon" class="h-8 w-8 hover:bg-muted" title="View Benefit">
            <Eye class="w-4 h-4" />
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 text-left">
            <div class="bg-card w-full max-w-2xl p-6 rounded-lg shadow-lg border border-border max-h-[90vh] overflow-y-auto">
                <div class="mb-4">
                    <h2 class="text-xl font-bold mb-1">View Benefit: {{ benefit.name }}</h2>
                    <p class="text-sm text-foreground mb-4">{{ benefit.description || 'No description provided.' }}</p>
                    <span v-if="benefit.status || benefit.is_active" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                    <span v-else class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Inactive</span>
                </div>

                <div class="mt-4 border-t pt-4">
                    <h3 class="font-semibold text-lg mb-2">Level Assignments</h3>
                    <div v-for="level in (levels as any[])" :key="level.id" class="p-4 border border-border rounded-md mb-2 bg-muted/10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold uppercase text-xs tracking-wider text-muted-foreground">{{ level.name }}</span>
                            <span v-if="benefit.levels[level.id]?.has_benefit" class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">Enabled</span>
                            <span v-else class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Disabled</span>
                        </div>
                        <div v-if="benefit.levels[level.id]?.has_benefit" class="grid grid-cols-12 gap-2 mt-2">
                            <div class="col-span-4">
                                <span class="text-[10px] uppercase font-semibold text-muted-foreground block">Value</span>
                                <span class="text-sm font-medium">{{ benefit.levels[level.id]?.value || '-' }}</span>
                            </div>
                            <div class="col-span-5">
                                <span class="text-[10px] uppercase font-semibold text-muted-foreground block">Details</span>
                                <span class="text-sm">{{ benefit.levels[level.id]?.description || '-' }}</span>
                            </div>
                            <div class="col-span-3">
                                <span class="text-[10px] uppercase font-semibold text-muted-foreground block">Web Status</span>
                                <span class="text-sm">{{ benefit.levels[level.id]?.web_status ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6 pt-4 border-t">
                    <Button type="button" variant="outline" @click="isOpen = false">Close</Button>
                </div>
            </div>
        </div>
    </div>
</template>
