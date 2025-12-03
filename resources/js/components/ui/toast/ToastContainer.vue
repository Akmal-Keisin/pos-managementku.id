<script setup lang="ts">
import Toast from './Toast.vue';
import { useToast } from '@/composables/useToast';

const { toasts, remove } = useToast();
</script>

<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 z-[9999] flex items-start px-4 py-6 sm:items-start sm:p-6"
    >
        <div class="flex w-full flex-col items-end space-y-4">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <Toast
                    v-for="toast in toasts"
                    :key="toast.id"
                    :toast="toast"
                    @remove="remove"
                />
            </TransitionGroup>
        </div>
    </div>
</template>
