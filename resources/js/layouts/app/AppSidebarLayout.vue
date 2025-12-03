<script setup lang="ts">
import AppContent from '@/components/layout/AppContent.vue';
import AppShell from '@/components/layout/shell/AppShell.vue';
import AppSidebar from '@/components/layout/sidebar/AppSidebar.vue';
import AppSidebarHeader from '@/components/layout/sidebar/AppSidebarHeader.vue';
import ToastContainer from '@/components/ui/toast/ToastContainer.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Alert {
    type?: 'success' | 'error' | 'info' | 'warning';
    message: string;
    description?: string | null;
}

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const toast = useToast();

const alert = computed(() => page.props.alert as Alert | null);
if (alert.value && alert.value.message) {
    const type = alert.value.type ?? 'info';
    const description = alert.value.description ?? undefined;

    switch (type) {
        case 'success':
            toast.success(alert.value.message, description);
            break;
        case 'error':
            toast.error(alert.value.message, description);
            break;
        case 'warning':
            toast.warning(alert.value.message, description);
            break;
        case 'info':
            toast.info(alert.value.message, description);
            break;
    }
}

watch(
    () => page.props.alert as Alert | null,
    (newAlert) => {
        if (newAlert && newAlert.message) {
            const type = newAlert.type ?? 'info';
            const description = newAlert.description ?? undefined;

            switch (type) {
                case 'success':
                    toast.success(newAlert.message, description);
                    break;
                case 'error':
                    toast.error(newAlert.message, description);
                    break;
                case 'warning':
                    toast.warning(newAlert.message, description);
                    break;
                case 'info':
                    toast.info(newAlert.message, description);
                    break;
            }
        }
    },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <ToastContainer />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
