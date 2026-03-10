<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <header
        class="bg-primary text-primary-foreground flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 [&_ol]:text-primary-foreground/70 [&_span[data-slot=breadcrumb-page]]:text-primary-foreground [&_span[data-slot=breadcrumb-page]]:font-semibold [&_a]:text-primary-foreground/70 hover:[&_a]:text-primary-foreground [&_li[data-slot=breadcrumb-separator]]:text-primary-foreground/50">
            <SidebarTrigger class="-ml-1 text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground [&>svg]:text-primary-foreground" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2">
            <Button variant="ghost" size="icon" @click="toggleTheme" class="h-8 w-8 rounded-full hover:bg-primary-foreground/10 text-primary-foreground">
                <Sun v-if="resolvedAppearance === 'dark'" class="h-4 w-4" />
                <Moon v-else class="h-4 w-4" />
                <span class="sr-only">Toggle theme</span>
            </Button>
        </div>
    </header>
</template>
