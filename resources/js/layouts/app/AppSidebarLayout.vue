<script setup lang="ts">
import AppContent from '@/components/layout/AppContent.vue';
import AppShell from '@/components/layout/shell/AppShell.vue';
import AppSidebar from '@/components/layout/sidebar/AppSidebar.vue';
import AppSidebarHeader from '@/components/layout/sidebar/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const alert = page.props.alert as {
    type?: 'success' | 'error' | 'info' | 'warning';
    message: string;
    description?: string | null;
} | null;

if (alert && alert.message) {
    const type = alert.type ?? 'info';
    const description = alert.description ?? undefined;
    const opts = description ? { description } : undefined;
    switch (type) {
        case 'success':
            toast.success(alert.message, opts);
            break;
        case 'error':
            toast.error(alert.message, opts);
            break;
        case 'warning':
            toast.warning(alert.message, opts);
            break;
        default:
            toast.info(alert.message, opts);
            break;
    }
}
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <Toaster rich-colors position="top-right" />
            <slot />
        </AppContent>
    </AppShell>
</template>
