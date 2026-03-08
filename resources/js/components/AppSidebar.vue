<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, Shield, Heart } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Gratitude Program',
        href: '#',
        icon: Heart,
        children: [
            { title: 'Overview', href: '/gratitude' },
            { title: 'Gratitude Accounts', href: '/gratitude/accounts' },
            { title: 'Gratitude Levels', href: '/gratitude/levels' },
            { title: 'Gratitude Benefits', href: '/gratitude/benefits' },
            { title: 'Gratitude Reserve', href: '/gratitude/reserve' },
            { title: 'Program Level Benefits', href: '/gratitude/program-level-benefits' },
        ],
    },
    {
        title: 'Auth & Security',
        href: '#',
        icon: Shield,
        children: [
            { title: 'Users', href: '/users' },
            { title: 'Roles', href: '/roles' },
            { title: 'Permissions', href: '/permissions' },
            { title: 'Application Keys', href: '/application-keys' },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
