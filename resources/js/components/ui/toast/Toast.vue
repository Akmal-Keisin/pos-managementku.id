<script setup lang="ts">
import { CircleCheckIcon, InfoIcon, OctagonXIcon, TriangleAlertIcon, XIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import type { Toast } from '@/composables/useToast';

interface Props {
    toast: Toast;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    remove: [id: string];
}>();

const icon = computed(() => {
    switch (props.toast.type) {
        case 'success':
            return CircleCheckIcon;
        case 'error':
            return OctagonXIcon;
        case 'warning':
            return TriangleAlertIcon;
        case 'info':
            return InfoIcon;
        default:
            return InfoIcon;
    }
});

const iconColor = computed(() => {
    switch (props.toast.type) {
        case 'success':
            return 'text-green-500';
        case 'error':
            return 'text-red-500';
        case 'warning':
            return 'text-yellow-500';
        case 'info':
            return 'text-blue-500';
        default:
            return 'text-blue-500';
    }
});
</script>

<template>
    <div
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-background shadow-lg ring-1 ring-border animate-in slide-in-from-top-5"
    >
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <component :is="icon" :class="['h-5 w-5', iconColor]" />
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-foreground">
                        {{ toast.message }}
                    </p>
                    <p v-if="toast.description" class="mt-1 text-sm text-muted-foreground">
                        {{ toast.description }}
                    </p>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button
                        type="button"
                        class="inline-flex rounded-md text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        @click="emit('remove', toast.id)"
                    >
                        <span class="sr-only">Close</span>
                        <XIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
