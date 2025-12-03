import { reactive } from 'vue';

export interface Toast {
    id: string;
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
    description?: string;
}

interface ToastState {
    toasts: Toast[];
}

const state = reactive<ToastState>({
    toasts: [],
});

let toastIdCounter = 0;

function generateId(): string {
    return `toast-${Date.now()}-${toastIdCounter++}`;
}

function addToast(toast: Omit<Toast, 'id'>) {
    const id = generateId();
    const newToast: Toast = { id, ...toast };
    state.toasts.push(newToast);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        removeToast(id);
    }, 5000);

    return id;
}

function removeToast(id: string) {
    const index = state.toasts.findIndex((t) => t.id === id);
    if (index > -1) {
        state.toasts.splice(index, 1);
    }
}

export function useToast() {
    return {
        toasts: state.toasts,
        success: (message: string, description?: string) =>
            addToast({ type: 'success', message, description }),
        error: (message: string, description?: string) =>
            addToast({ type: 'error', message, description }),
        info: (message: string, description?: string) =>
            addToast({ type: 'info', message, description }),
        warning: (message: string, description?: string) =>
            addToast({ type: 'warning', message, description }),
        remove: removeToast,
    };
}
