<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, ShoppingCart, Users, Package, Tag, History } from '@lucide/vue';
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Painel Principal',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Venda Rápida',
        href: '/sales/create',
        icon: ShoppingCart,
    },
    {
        title: 'Clientes (Caderneta)',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Produtos (Estoque)',
        href: '/products',
        icon: Package,
    },
    {
        title: 'Marcas',
        href: '/brands',
        icon: Tag,
    },
    {
        title: 'Histórico de Vendas',
        href: '/sales',
        icon: History,
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
