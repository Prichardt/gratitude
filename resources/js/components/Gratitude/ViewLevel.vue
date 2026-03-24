<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    level: {
        type: Object,
        required: true,
    },
});

const isOpen = ref(false);
const rules = ref<
    { name: string; value: string; status: boolean; value_type: string }[]
>([]);

watch(isOpen, (newVal) => {
    if (newVal) {
        if (props.level.level_rules) {
            rules.value =
                typeof props.level.level_rules === 'string'
                    ? JSON.parse(props.level.level_rules)
                    : props.level.level_rules;
        } else {
            rules.value = [];
        }
    }
});
</script>

<template>
    <div>
        <Button @click="isOpen = true" variant="outline" size="sm">View</Button>

        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50"
        >
            <div
                class="m-4 flex max-h-[90vh] w-full max-w-2xl flex-col gap-6 overflow-y-auto rounded-lg border border-border bg-card p-6 text-left shadow-lg"
            >
                <div>
                    <h2 class="mb-1 text-xl font-bold">
                        Gratitude Level: {{ level.name }}
                    </h2>
                    <!-- <p class="text-sm text-muted-foreground">
                        Details for this specific level.
                    </p> -->
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <span
                            class="text-xs font-semibold text-muted-foreground uppercase"
                            >Name</span
                        >
                        <div class="mt-1 text-base font-medium">
                            {{ level.name }}
                        </div>
                    </div>
                    <div>
                        <span
                            class="text-xs font-semibold text-muted-foreground uppercase"
                            >Status</span
                        >
                        <div class="mt-1">
                            <span
                                v-if="level.status"
                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                >Active</span
                            >
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                                >Inactive</span
                            >
                        </div>
                    </div>
                    <div>
                        <span
                            class="text-xs font-semibold text-muted-foreground uppercase"
                            >Min Points</span
                        >
                        <div class="mt-1 text-base font-medium">
                            {{ level.min_points }}
                        </div>
                    </div>
                    <div>
                        <span
                            class="text-xs font-semibold text-muted-foreground uppercase"
                            >Max Points</span
                        >
                        <div class="mt-1 text-base font-medium">
                            {{
                                level.max_points !== null &&
                                level.max_points !== ''
                                    ? level.max_points
                                    : '∞'
                            }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 border-t border-border pt-4">
                    <div>
                        <span
                            class="mb-2 block text-xs font-semibold text-muted-foreground uppercase"
                            >Level Image</span
                        >
                        <div v-if="level.level_image" class="mt-1">
                            <img
                                :src="
                                    level.level_image.startsWith('http')
                                        ? level.level_image
                                        : `/storage/${level.level_image}`
                                "
                                alt="Level Image"
                                class="h-20 w-auto rounded border border-border"
                            />
                        </div>
                        <div v-else class="mt-1 text-sm text-muted-foreground">
                            No image uploaded
                        </div>
                    </div>
                    <div>
                        <span
                            class="mb-2 block text-xs font-semibold text-muted-foreground uppercase"
                            >Level Icon</span
                        >
                        <div v-if="level.level_icon" class="mt-1">
                            <img
                                :src="
                                    level.level_icon.startsWith('http')
                                        ? level.level_icon
                                        : `/storage/${level.level_icon}`
                                "
                                alt="Level Icon"
                                class="h-10 w-10 rounded border border-border object-contain"
                            />
                        </div>
                        <div v-else class="mt-1 text-sm text-muted-foreground">
                            No icon uploaded
                        </div>
                    </div>
                </div>

                <div class="border-t border-border pt-4">
                    <div class="mb-3 block">
                        <span class="text-lg font-semibold"
                            >Rules Repeater</span
                        >
                    </div>
                    <div
                        v-for="(rule, index) in rules"
                        :key="index"
                        class="mb-3 grid grid-cols-12 items-center gap-4 rounded-md border border-border bg-muted/10 p-4"
                    >
                        <div class="col-span-4">
                            <span
                                class="text-xs font-semibold text-muted-foreground uppercase"
                                >Rule Name</span
                            >
                            <div class="mt-1 text-sm font-medium break-words">
                                {{ rule.name }}
                            </div>
                        </div>
                        <div class="col-span-4">
                            <span
                                class="text-xs font-semibold text-muted-foreground uppercase"
                                >Value</span
                            >
                            <div class="mt-1 text-sm font-medium break-words">
                                {{ rule.value }}
                            </div>
                        </div>
                        <div class="col-span-3">
                            <span
                                class="text-xs font-semibold text-muted-foreground uppercase"
                                >Type</span
                            >
                            <div class="mt-1 text-sm font-medium capitalize">
                                {{ rule.value_type }}
                            </div>
                        </div>
                        <div class="col-span-1 text-right">
                            <span
                                v-if="rule.status"
                                class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[0.6rem] font-medium tracking-wide text-green-800 uppercase"
                                >Active</span
                            >
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[0.6rem] font-medium tracking-wide text-red-800 uppercase"
                                >Inactive</span
                            >
                        </div>
                    </div>
                    <p
                        v-if="rules.length === 0"
                        class="rounded border border-dashed border-border bg-muted/10 py-4 text-center text-sm text-muted-foreground"
                    >
                        No rules added to this level.
                    </p>
                </div>

                <div class="mt-2 flex justify-end border-t border-border pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                        >Close</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
