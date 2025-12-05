<script setup lang="ts">
import MarkdownRenderer from '@/components/markdown/MarkdownRenderer.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    MessageSquare,
    Plus,
    RotateCcw,
    SendHorizontal,
    Trash2,
} from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

interface Topic {
    id: number;
    title: string;
    last_message_at?: string | null;
    created_at: string;
}

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
    pending?: boolean;
}

interface Props {
    topics: Topic[];
    selectedTopic: Topic;
    messages: Message[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'AI Chatbot', href: '/chatbot' },
];

const messageInput = ref('');
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const messagesContainerRef = ref<HTMLDivElement | null>(null);
const maxTextareaHeight = 240; // px ~12 lines
const lastCopiedId = ref<number | null>(null);
const sidebarOpen = ref(false);

const autoResize = () => {
    // textareaRef may point to the component instance or the native textarea element.
    const comp = textareaRef.value as any;
    if (!comp) return;
    const el: HTMLTextAreaElement | null = comp.$el ?? comp;
    if (!el || !el.style) return;
    el.style.height = 'auto';
    const next = Math.min(el.scrollHeight, maxTextareaHeight);
    el.style.height = `${next}px`;
    el.style.overflowY = el.scrollHeight > maxTextareaHeight ? 'auto' : 'hidden';
};

const scrollToBottom = () => {
    const el = messagesContainerRef.value;
    if (!el) return;
    el.scrollTop = el.scrollHeight;
};

const formatTime = (iso: string) =>
    new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });

const copyMessage = async (m: Message) => {
    try {
        await navigator.clipboard.writeText(m.content);
        lastCopiedId.value = m.id;
        setTimeout(() => (lastCopiedId.value = null), 1500);
    } catch {}
};

// Local reactive messages so we can append without reloading the page
const messages = ref<Message[]>([...props.messages]);

// Keep local messages in sync when server props change (e.g., switching topics)
watch(
    () => props.messages,
    (next) => {
        messages.value = [...next];
    },
    { deep: true },
);

const sendMessage = async () => {
    // Ensure we read the most up-to-date value: prefer `messageInput`, fallback to DOM textarea value
    let domVal = '';
    const comp = textareaRef.value as any;
    const el: HTMLTextAreaElement | null = comp? (comp.$el ?? comp) : null;
    if (el && typeof el.value === 'string') domVal = el.value;
    const content = (messageInput.value || domVal || '').toString();
    if (!content.trim()) return;

    const url = `/chatbot/topics/${props.selectedTopic.id}/messages`;
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const payload = { content };

    console.debug('sendMessage -> content', content);

    // Optimistic UI: push user message and assistant placeholder immediately
    const tempUserId = Date.now();
    const tempAssistantId = Date.now() + 1;
    const nowIso = new Date().toISOString();

    const tempUser: Message = {
        id: tempUserId,
        role: 'user',
        content: content,
        created_at: nowIso,
    };

    const tempAssistant: Message = {
        id: tempAssistantId,
        role: 'assistant',
        content: '',
        pending: true,
        created_at: nowIso,
    };

    messages.value.push(tempUser);
    messages.value.push(tempAssistant);

    // Clear input locally right away
    messageInput.value = '';
    autoResize();
    await nextTick();
    scrollToBottom();

    try {
        console.debug('POST', url, payload);
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            // Non-OK (status 4xx/5xx). Log response body for diagnosis and show local error message.
            const text = await res.text().catch(() => '[unable to read body]');
            console.error('AJAX non-ok response', res.status, text);
            // remove assistant placeholder
            messages.value = messages.value.filter((m) => m.id !== tempAssistantId);
            // show local assistant error message
            messages.value.push({
                id: Date.now() + 3,
                role: 'assistant',
                content: 'Terjadi kesalahan saat menghubungi server. Coba lagi.',
                created_at: new Date().toISOString(),
            });
            return;
        }

        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await res.text().catch(() => '[unable to read body]');
            console.error('AJAX non-json response', text);
            messages.value = messages.value.filter((m) => m.id !== tempAssistantId);
            messages.value.push({
                id: Date.now() + 4,
                role: 'assistant',
                content: 'Server mengembalikan respons yang tidak terduga. Coba lagi.',
                created_at: new Date().toISOString(),
            });
            return;
        }

        const data = await res.json();

        // Build normalized Message objects from server response to avoid rendering raw JSON
        const serverUser = data.user_message
            ? ({
                  id: data.user_message.id,
                  role: data.user_message.role ?? 'user',
                  content: data.user_message.content ?? String(data.user_message),
                  created_at: data.user_message.created_at ?? new Date().toISOString(),
              } as Message)
            : null;

        const serverAssistant = data.assistant_message
            ? ({
                  id: data.assistant_message.id,
                  role: data.assistant_message.role ?? 'assistant',
                  content: data.assistant_message.content ?? String(data.assistant_message),
                  created_at:
                      data.assistant_message.created_at ?? new Date().toISOString(),
              } as Message)
            : null;

        // Replace temp user message with server user_message (if returned)
        if (serverUser) {
            const idx = messages.value.findIndex((m) => m.id === tempUserId);
            if (idx !== -1) messages.value.splice(idx, 1, serverUser);
        }

        // Replace temp assistant placeholder with actual assistant_message
        const aidx = messages.value.findIndex((m) => m.id === tempAssistantId);
        if (serverAssistant) {
            if (aidx !== -1) messages.value.splice(aidx, 1, serverAssistant);
            else messages.value.push(serverAssistant);
        } else {
            // if assistant missing, remove placeholder
            if (aidx !== -1) messages.value.splice(aidx, 1);
        }

        await nextTick();
        scrollToBottom();
    } catch (e) {
        // On error, remove placeholder and fallback to Inertia submission
        console.error('sendMessage error', e);
        messages.value = messages.value.filter((m) => m.id !== tempAssistantId);
        console.error("AJAX error", e);
        return;
    }
};

const createTopic = () => {
    router.post(
        '/chatbot/topics',
        {},
        {
            preserveState: false,
        },
    );
};

const clearTopic = () => {
    router.delete(`/chatbot/topics/${props.selectedTopic.id}/clear`, {
        preserveState: true,
    });
};

const deleteTopic = () => {
    router.delete(`/chatbot/topics/${props.selectedTopic.id}`, {
        preserveState: false,
    });
};

const isAssistant = (m: Message) => m.role === 'assistant';

const selectTopic = (id: number) => {
    router.get('/chatbot', { topic: id }, { preserveState: true });
};

onMounted(() => {
    autoResize();
    nextTick(scrollToBottom);
});

watch(
    () => messages.value.length,
    async () => {
        await nextTick();
        scrollToBottom();
    },
);
</script>

<template>
    <Head title="AI Chatbot" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="grid h-[calc(100vh-64px)] grid-cols-1 gap-4 p-4 lg:grid-cols-[320px_1fr]"
        >
            <!-- Sidebar -->
            <div
                class="sticky top-4 hidden h-[calc(100vh-64px-2rem)] flex-col overflow-hidden rounded-xl border bg-card shadow-lg lg:flex"
            >
                <div
                    class="flex items-center justify-between border-b bg-muted/30 p-4"
                >
                    <div class="flex items-center gap-2">
                        <div class="rounded-lg bg-primary/10 p-2">
                            <MessageSquare class="h-4 w-4 text-primary" />
                        </div>
                        <h2 class="font-semibold">Chats</h2>
                    </div>
                    <Button size="sm" @click="createTopic">
                        <Plus class="mr-1.5 h-4 w-4" /> New Chat
                    </Button>
                </div>
                <div class="flex-1 overflow-y-auto p-3">
                    <div
                        v-if="props.topics.length === 0"
                        class="flex flex-col items-center justify-center gap-2 py-8 text-center"
                    >
                        <MessageSquare
                            class="h-12 w-12 text-muted-foreground/40"
                        />
                        <p class="text-sm font-medium text-muted-foreground">
                            No chats yet
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Start a new conversation
                        </p>
                    </div>
                    <div class="space-y-1">
                        <button
                            v-for="t in props.topics"
                            :key="t.id"
                            class="group flex w-full items-start gap-3 rounded-lg px-3 py-3 text-left transition-all hover:bg-muted/50"
                            :class="
                                t.id === props.selectedTopic.id
                                    ? 'border-l-2 border-primary bg-primary/5'
                                    : 'border-l-2 border-transparent'
                            "
                            @click="selectTopic(t.id)"
                        >
                            <MessageSquare
                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-medium">
                                    {{ t.title }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            t.created_at,
                                        ).toLocaleDateString('id-ID', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })
                                    }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chat area -->
            <div
                class="relative flex h-[calc(100vh-64px-2rem)] flex-col overflow-hidden rounded-xl border bg-gradient-to-b from-background to-muted/10 shadow-lg"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b bg-card/50 p-4 backdrop-blur-sm"
                >
                    <div class="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="lg:hidden"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <MessageSquare class="h-5 w-5" />
                        </Button>
                        <div>
                            <h1 class="text-lg font-semibold">
                                {{ props.selectedTopic.title }}
                            </h1>
                            <p class="text-xs text-muted-foreground">
                                AI Assistant
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="ghost" size="sm" @click="clearTopic">
                            <RotateCcw class="mr-1.5 h-4 w-4" /> Clear
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive"
                            @click="deleteTopic"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Messages -->
                <div
                    ref="messagesContainerRef"
                    class="flex-1 space-y-6 overflow-y-auto p-6"
                >
                    <div
                                        v-if="messages.length === 0"
                        class="flex h-full flex-col items-center justify-center gap-4 text-center"
                    >
                        <div class="rounded-full bg-primary/10 p-6">
                            <MessageSquare class="h-12 w-12 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">
                                Start a conversation
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Ask me anything or type your message below
                            </p>
                        </div>
                    </div>
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex w-full animate-in fade-in slide-in-from-bottom-2"
                        :class="
                            isAssistant(m) ? 'justify-start' : 'justify-end'
                        "
                    >
                        <div class="group relative max-w-[85%] md:max-w-2xl">
                            <div
                                class="rounded-2xl p-4 shadow-sm transition-all"
                                :class="
                                    isAssistant(m)
                                        ? 'border bg-card'
                                        : 'bg-primary text-primary-foreground'
                                "
                            >
                                <div
                                    class="mb-2 flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                                            :class="
                                                isAssistant(m)
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-primary-foreground text-primary'
                                            "
                                        >
                                            {{ isAssistant(m) ? 'AI' : 'U' }}
                                        </div>
                                        <span
                                            class="text-xs font-medium"
                                            :class="
                                                isAssistant(m)
                                                    ? 'text-muted-foreground'
                                                    : 'text-primary-foreground/80'
                                            "
                                        >
                                            {{ formatTime(m.created_at) }}
                                        </span>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 opacity-0 transition-opacity group-hover:opacity-100"
                                        :class="
                                            isAssistant(m)
                                                ? ''
                                                : 'text-primary-foreground hover:bg-primary-foreground/20'
                                        "
                                        :title="
                                            lastCopiedId === m.id
                                                ? 'Copied!'
                                                : 'Copy message'
                                        "
                                        @click="copyMessage(m)"
                                    >
                                        <Check
                                            v-if="lastCopiedId === m.id"
                                            class="h-4 w-4"
                                        />
                                        <Copy v-else class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div
                                    :class="
                                        isAssistant(m)
                                            ? ''
                                            : '[&_*]:text-primary-foreground'
                                    "
                                >
                                    <template v-if="m.pending">
                                        <div class="flex items-center gap-3">
                                            <svg
                                                class="h-5 w-5 animate-spin text-primary"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                />
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                                />
                                            </svg>
                                            <span class="text-sm text-muted-foreground">
                                                Sedang mengetik...
                                            </span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <MarkdownRenderer :content="m.content" />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Composer -->
                <div class="border-t bg-card/95 p-4 backdrop-blur-sm">
                    <div class="mx-auto max-w-4xl">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <Textarea
                                    v-model="messageInput"
                                    ref="textareaRef"
                                    placeholder="Type your message... (Markdown supported)"
                                    class="max-h-60 min-h-[52px] resize-none rounded-xl border-2 focus-visible:ring-0 focus-visible:ring-offset-0"
                                    @input="autoResize"
                                    @focus="autoResize"
                                    @keyup.enter.exact.prevent="sendMessage"
                                />
                            </div>
                            <Button
                                size="lg"
                                :disabled="!messageInput.trim()"
                                class="h-[52px] rounded-xl px-6"
                                @click="sendMessage"
                            >
                                <SendHorizontal class="mr-2 h-5 w-5" />
                                <span class="hidden sm:inline">Send</span>
                            </Button>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span
                                >Press
                                <kbd
                                    class="rounded bg-muted px-1.5 py-0.5 font-mono"
                                    >Enter</kbd
                                >
                                to send,
                                <kbd
                                    class="rounded bg-muted px-1.5 py-0.5 font-mono"
                                    >Shift+Enter</kbd
                                >
                                for new line</span
                            >
                            <span class="hidden md:inline"
                                >Markdown supported</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
