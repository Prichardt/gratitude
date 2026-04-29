<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { X, Eye, Package, Hash } from 'lucide-vue-next';

const props = defineProps({
    redemption: { type: Object, required: true },
    pointsPerDollar: { type: Number, default: 35 },
});

const isOpen = ref(false);

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num || 0);
};

const formatDate = (val: string | null) => {
    if (!val) return 'N/A';
    return new Date(val).toISOString().split('T')[0];
};

const computedValue = () => {
    const amount = Number(props.redemption.amount);
    const points = Number(props.redemption.points);
    const rate = Number(props.redemption.points_breakdown?.points_per_dollar || props.pointsPerDollar || 35);
    return amount > 0 ? amount.toFixed(2) : (points / rate).toFixed(2);
};

const sourceLabel = (detail: any) => {
    if (!detail.source_type) return `ID #${detail.source_id}`;
    const parts = detail.source_type.split('\\');
    return parts[parts.length - 1] || detail.source_type;
};
</script>

<template>
    <div class="inline-block">
        <Button @click.stop="isOpen = true" variant="outline" size="sm"
            class="h-7 text-[10px] uppercase font-bold tracking-wider px-2 border-primary/20 text-primary hover:bg-primary/10">
            <Eye class="w-3 h-3 mr-1" /> View
        </Button>

        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm text-left px-4">
            <div class="bg-card w-full max-w-2xl rounded-xl shadow-2xl border border-border/50 overflow-hidden" @click.stop>

                <!-- Header -->
                <div class="bg-amber-600 text-white px-6 py-4 flex justify-between items-center">
                    <h2 class="text-base font-bold tracking-wide flex items-center gap-2">
                        <Eye class="w-5 h-5 opacity-80" />
                        Redemption Details
                        <span class="text-white/60 text-xs font-normal ml-1">#{{ redemption.id }}</span>
                    </h2>
                    <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 max-h-[80vh] overflow-y-auto space-y-6">

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                            <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Date</span>
                            <div class="font-medium text-sm mt-0.5">{{ formatDate(redemption.created_at) }}</div>
                        </div>
                        <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                            <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Status</span>
                            <div class="font-medium text-sm mt-0.5 capitalize">{{ redemption.status || 'Completed' }}</div>
                        </div>
                        <div class="bg-amber-50/60 dark:bg-amber-950/20 p-3 rounded-lg border border-amber-200/50 dark:border-amber-800/30">
                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">Points Used</span>
                            <div class="font-bold text-base mt-0.5 text-amber-600 dark:text-amber-400">{{ formatNumber(redemption.points) }} pts</div>
                        </div>
                        <div class="bg-green-50/60 dark:bg-green-950/20 p-3 rounded-lg border border-green-200/50 dark:border-green-800/30">
                            <span class="text-[10px] text-green-600 dark:text-green-400 font-bold uppercase tracking-wider">Value</span>
                            <div class="font-bold text-base mt-0.5 text-green-600 dark:text-green-400">${{ computedValue() }}</div>
                        </div>
                    </div>

                    <!-- Reason & Details -->
                    <div class="space-y-3">
                        <div class="bg-muted/30 p-3 rounded-lg border border-border/50">
                            <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Reason</span>
                            <div class="font-medium text-sm mt-0.5">{{ redemption.reason || '—' }}</div>
                        </div>
                        <div v-if="redemption.category" class="bg-muted/30 p-3 rounded-lg border border-border/50">
                            <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Category</span>
                            <div class="font-medium text-sm mt-0.5 capitalize">{{ redemption.category }}</div>
                        </div>
                        <div v-if="redemption.roomStatus" class="bg-muted/30 p-3 rounded-lg border border-border/50">
                            <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Room Status</span>
                            <div class="font-medium text-sm mt-0.5 capitalize">{{ redemption.roomStatus }}</div>
                        </div>
                    </div>

                    <!-- Reference IDs -->
                    <div v-if="redemption.journey_id || redemption.cancel_id || redemption.old_id" class="space-y-1">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-2 flex items-center gap-2">
                            <Hash class="w-4 h-4" /> References
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-if="redemption.journey_id" class="bg-blue-50/50 dark:bg-blue-950/20 p-3 rounded-lg border border-blue-200/50 dark:border-blue-800/30">
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider">Journey ID</span>
                                <div class="font-medium text-sm mt-0.5">{{ redemption.journey_id }}</div>
                            </div>
                            <div v-if="redemption.cancel_id" class="bg-red-50/50 dark:bg-red-950/20 p-3 rounded-lg border border-red-200/50 dark:border-red-800/30">
                                <span class="text-[10px] text-red-600 dark:text-red-400 font-bold uppercase tracking-wider">Cancel ID</span>
                                <div class="font-medium text-sm mt-0.5">{{ redemption.cancel_id }}</div>
                            </div>
                            <div v-if="redemption.old_id" class="bg-muted/30 p-3 rounded-lg border border-border/50">
                                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">Legacy ID</span>
                                <div class="font-medium text-sm mt-0.5">{{ redemption.old_id }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Point Allocation (details) -->
                    <div v-if="redemption.details && redemption.details.length > 0">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3 flex items-center gap-2">
                            <Package class="w-4 h-4 text-amber-500" /> Point Allocation
                        </h3>
                        <div class="border border-border/50 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-border/50">
                                <thead class="bg-muted/30">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Source</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Source ID</th>
                                        <th class="px-4 py-2 text-right text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Points</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50 bg-card">
                                    <tr v-for="detail in redemption.details" :key="detail.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-foreground/70 whitespace-nowrap">{{ sourceLabel(detail) }}</td>
                                        <td class="px-4 py-2.5 text-xs text-foreground/70 whitespace-nowrap">#{{ detail.source_id }}</td>
                                        <td class="px-4 py-2.5 text-xs font-bold text-amber-600 dark:text-amber-400 text-right whitespace-nowrap">-{{ formatNumber(detail.points) }} pts</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Points Breakdown JSON -->
                    <div v-if="redemption.points_breakdown && Object.keys(redemption.points_breakdown).length > 0">
                        <details class="group">
                            <summary class="text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground transition-colors outline-none list-none flex items-center gap-2">
                                <span class="inline-block transition-transform group-open:rotate-90">▶</span>
                                Points Breakdown
                            </summary>
                            <div class="mt-2 text-xs bg-muted/50 p-3 rounded border border-border/50 overflow-x-auto font-mono">
                                <pre>{{ JSON.stringify(redemption.points_breakdown, null, 2) }}</pre>
                            </div>
                        </details>
                    </div>

                </div>

                <div class="p-4 border-t border-border/50 bg-muted/10 flex justify-end">
                    <Button type="button" variant="outline" class="h-9 px-6 font-semibold shadow-sm" @click="isOpen = false">Close</Button>
                </div>
            </div>
        </div>
    </div>
</template>
