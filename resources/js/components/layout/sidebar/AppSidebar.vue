<script setup lang="ts">
import AppLogo from '@/components/layout/AppLogo.vue';
import NavUser from '@/components/layout/sidebar/NavUser.vue';
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
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    FileText,
    History,
    LayoutGrid,
    MessageSquare,
    Package,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from './NavFooter.vue';
import NavMain from './NavMain.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

/**
 * Navigation configuration
 * Define all available menu items with their permissions
 */
const navigationConfig = {
    dashboard: {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        roles: ['super-admin', 'admin'],
    },
    userManagement: {
        title: 'User Management',
        href: '/user-management',
        icon: Users,
        roles: ['super-admin', 'admin'],
    },
    productManagement: {
        title: 'Product Management',
        href: '/product-management',
        icon: Package,
        roles: ['super-admin', 'admin'],
    },
    stockManagement: {
        title: 'Stock Management',
        href: '/stock-management',
        icon: History,
        roles: ['super-admin', 'admin'],
    },
    reporting: {
        title: 'Transaction Reports',
        href: '/reporting',
        icon: FileText,
        roles: ['super-admin', 'admin'],
    },
    chatbot: {
        title: 'Chatbot',
        href: '/chatbot',
        icon: MessageSquare,
        roles: ['super-admin', 'admin'],
    },
    posTerminal: {
        title: 'POS Terminal',
        href: '/pos-terminal',
        icon: LayoutGrid,
        roles: ['super-admin', 'admin', 'cashier'],
    },
} as const;

/**
 * Check if user has permission to access a menu item
 */
const hasPermission = (allowedRoles: readonly string[]): boolean => {
    if (!user.value?.role) return false;
    return allowedRoles.includes(user.value.role);
};

/**
 * Filter and build navigation items based on user role
 */
const mainNavItems = computed<NavItem[]>(() => {
    return Object.values(navigationConfig)
        .filter((item) => hasPermission(item.roles))
        .map(({ title, href, icon }) => ({
            title,
            href,
            icon,
        }));
});

/**
 * Footer navigation items (documentation, external links, etc.)
 */
const footerNavItems: NavItem[] = [];
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
