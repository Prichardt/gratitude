<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { X, Info, History } from 'lucide-vue-next';

const props = defineProps({
    item: { type: Object, required: true }
});

const isOpen = ref(false);

const openDialog = () => {
    isOpen.value = true;
};

const closeDialog = () => {
    isOpen.value = false;
};

const cancelledPoints = () => Number(props.item.cancelled_points || 0);
const remainingPoints = () => {
    if (props.item.remaining_points !== undefined && props.item.remaining_points !== null) {
        return Math.max(0, Number(props.item.remaining_points || 0));
    }

    return Math.max(
        0,
        Number(props.item.points || 0) - Number(props.item.redeemed_points || 0) - cancelledPoints(),
    );
};
const effectiveDate = () => props.item.displayDate || props.item.usable_date || props.item.useable_date || props.item.date;
const formatDate = (date: any, fallback = 'N/A') => {
    if (!date) return fallback;

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) {
        return fallback;
    }

    return parsed.toISOString().split('T')[0];
};
</script>

<template>
    <div class="inline-block">
        <Button @click.stop="openDialog" variant="outline" size="sm" class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 border-primary/20 text-primary hover:bg-primary/10">
            View
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-2xl rounded-xl shadow-2xl border border-border/50 overflow-hidden" @click.stop>
                <div class="bg-primary text-primary-foreground px-6 py-4 flex justify-between items-center">
                    <h2 class="text-base font-bold tracking-wide flex items-center gap-2">
                        <Info class="w-5 h-5 opacity-80" />
                        Entry Details
                    </h2>
                    <button @click="closeDialog" class="text-primary-foreground/70 hover:text-primary-foreground transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="p-6 max-h-[80vh] overflow-y-auto space-y-6">
                    <!-- General Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">General Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Type</span>
                                <div class="font-medium text-sm mt-0.5 capitalize">{{ item.rowType || 'Entry' }}</div>
                            </div>
                            <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Total Points</span>
                                <div class="font-bold text-sm mt-0.5" :class="item.points < 0 ? 'text-destructive' : 'text-primary'">{{ item.points }} pts</div>
                            </div>
                            <div class="bg-amber-50/60 dark:bg-amber-950/20 p-3 rounded-lg border border-amber-200/50 dark:border-amber-800/30">
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">Redeemed</span>
                                <div class="font-bold text-sm mt-0.5 text-amber-600 dark:text-amber-400">{{ item.redeemed_points || 0 }} pts</div>
                            </div>
                            <div class="bg-red-50/60 dark:bg-red-950/20 p-3 rounded-lg border border-red-200/50 dark:border-red-800/30">
                                <span class="text-[10px] text-red-600 dark:text-red-400 font-bold uppercase tracking-wider">Cancelled</span>
                                <div class="font-bold text-sm mt-0.5 text-red-600 dark:text-red-400">{{ cancelledPoints() }} pts</div>
                            </div>
                            <div class="bg-green-50/60 dark:bg-green-950/20 p-3 rounded-lg border border-green-200/50 dark:border-green-800/30">
                                <span class="text-[10px] text-green-600 dark:text-green-400 font-bold uppercase tracking-wider">Remaining</span>
                                <div class="font-bold text-sm mt-0.5 text-green-600 dark:text-green-400">{{ remainingPoints() }} pts</div>
                            </div>
                            <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Effective Date</span>
                                <div class="font-medium text-sm mt-0.5">{{ formatDate(effectiveDate()) }}</div>
                            </div>
                            <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Status</span>
                                <div class="font-medium text-sm mt-0.5 capitalize">{{ item.status || 'Active' }}</div>
                            </div>
                            <div v-if="item.displayExpiresOn || item.expires_at" class="bg-orange-50/60 dark:bg-orange-950/20 p-3 rounded-lg border border-orange-200/50 dark:border-orange-800/30 col-span-2">
                                <span class="text-[10px] text-orange-600 dark:text-orange-400 font-bold uppercase tracking-wider">Expires On</span>
                                <div class="font-medium text-sm mt-0.5 text-orange-700 dark:text-orange-300">{{ new Date(item.displayExpiresOn || item.expires_at).toISOString().split('T')[0] }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="item.cancellationsList && item.cancellationsList.length > 0">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                            Cancellation History
                        </h3>
                        <div class="border border-border/50 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-border/50">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Reason</th>
                                        <th class="px-4 py-2 text-right text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Points</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50 bg-card">
                                    <tr v-for="cancel in item.cancellationsList" :key="cancel.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-foreground/70 whitespace-nowrap">{{ cancel.date ? new Date(cancel.date).toISOString().split('T')[0] : '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-foreground/80">{{ cancel.description }}</td>
                                        <td class="px-4 py-2.5 text-xs font-bold text-red-600 dark:text-red-400 text-right whitespace-nowrap">-{{ cancel.points }} pts</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Redemption History -->
                    <div v-if="item.redemptionsList && item.redemptionsList.length > 0">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3 flex items-center gap-2">
                            <History class="w-4 h-4 text-amber-500" /> Redemption History
                        </h3>
                        <div class="border border-border/50 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-border/50">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Reason</th>
                                        <th class="px-4 py-2 text-right text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Points</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50 bg-card">
                                    <tr v-for="red in item.redemptionsList" :key="red._key" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-foreground/70 whitespace-nowrap">{{ red.date || '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-foreground/80">{{ red.reason }}</td>
                                        <td class="px-4 py-2.5 text-xs font-bold text-amber-600 dark:text-amber-400 text-right whitespace-nowrap">-{{ red.points }} pts</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Project Info -->
                    <div v-if="item.project_data">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3 mt-6">Project / Journey</h3>
                        <div class="bg-blue-50/50 dark:bg-blue-950/20 p-4 rounded-xl border border-blue-500/20 space-y-3">
                            <div>
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">Project Number</span>
                                <div class="font-bold text-sm mt-0.5 text-foreground">{{ item.project_data.projectNumber || item.project_data.number || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">Project Name</span>
                                <div class="font-bold text-base mt-0.5 text-foreground">{{ item.project_data.name || item.project_data.title || 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">Subtitle / Type</span>
                                <div class="font-medium text-sm mt-0.5 text-foreground/80">{{ item.project_data.subtitle || item.project_data.type || 'N/A' }}</div>
                            </div>
                            
                            <div class="pt-2 border-t border-blue-500/10 grid grid-cols-2 gap-4" v-if="item.project_data.startDate || item.project_data.endDate">
                                <div v-if="item.project_data.startDate">
                                    <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">Start Date</span>
                                    <div class="text-sm mt-0.5 text-foreground/80">{{ new Date(item.project_data.startDate).toISOString().split('T')[0] }}</div>
                                </div>
                                <div v-if="item.project_data.endDate">
                                    <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">End Date</span>
                                    <div class="text-sm mt-0.5 text-foreground/80">{{ new Date(item.project_data.endDate).toISOString().split('T')[0] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Raw JSON Dump for Debugging / Fallback -->
                    <div v-if="item.project_data" class="mt-4">
                        <details class="group">
                            <summary class="text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground transition-colors outline-none list-none flex items-center gap-2">
                                <span class="inline-block transition-transform group-open:rotate-90">▶</span>
                                Raw Project Payload
                            </summary>
                            <div class="mt-2 text-xs bg-muted/50 p-3 rounded border border-border/50 overflow-x-auto text-left font-mono">
                                <pre>{{ JSON.stringify(item.project_data, null, 2) }}</pre>
                            </div>
                        </details>
                    </div>

                </div>

                <div class="p-4 border-t border-border/50 bg-muted/10 flex justify-end">
                    <Button type="button" variant="outline" class="h-9 px-6 font-semibold shadow-sm" @click="closeDialog">Close</Button>
                </div>
            </div>
        </div>
    </div>
</template>
