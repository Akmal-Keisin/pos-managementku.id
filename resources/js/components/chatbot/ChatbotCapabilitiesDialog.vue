<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertCircle,
    BarChart3,
    HelpCircle,
    Lightbulb,
    Package,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = ref(props.open);

const capabilities = [
    {
        icon: Package,
        category: 'Inventory Management',
        color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        commands: [
            {
                trigger: 'Stok rendah? / Produk apa yang stoknya rendah?',
                description: 'Shows list of products with low inventory levels',
            },
            {
                trigger: 'Analisis stok / Status stok',
                description:
                    'Provides detailed stock analysis including days of stock remaining',
            },
        ],
    },
    {
        icon: TrendingUp,
        category: 'Sales Analytics',
        color: 'bg-green-500/10 text-green-600 dark:text-green-400',
        commands: [
            {
                trigger: 'Penjualan hari ini? / Berapa omset hari ini?',
                description: 'Shows today\'s total sales and transaction count',
            },
            {
                trigger: 'Penjualan minggu ini?',
                description: 'Displays daily breakdown for the past 7 days',
            },
            {
                trigger: 'Penjualan bulan ini?',
                description:
                    'Shows monthly revenue, transaction count, and average',
            },
        ],
    },
    {
        icon: ShoppingCart,
        category: 'Best Sellers',
        color: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
        commands: [
            {
                trigger: 'Produk terlaris? / Best seller apa?',
                description:
                    'Lists top 10 selling products with quantities and revenue',
            },
        ],
    },
    {
        icon: Users,
        category: 'Performance Metrics',
        color: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
        commands: [
            {
                trigger: 'Performa kasir? / Kasir terbaik?',
                description:
                    'Shows top 10 cashiers with their sales metrics and performance',
            },
        ],
    },
    {
        icon: BarChart3,
        category: 'Product Operations',
        color: 'bg-red-500/10 text-red-600 dark:text-red-400',
        commands: [
            {
                trigger:
                    'Tambah produk nama [PRODUCT] harga [PRICE] stok [STOCK]',
                description:
                    'Add new product with confirmation step (admin only)',
            },
            {
                trigger: 'Restok [PRODUCT] stok [QUANTITY]',
                description:
                    'Update product stock with verification and confirmation',
            },
        ],
    },
    {
        icon: Lightbulb,
        category: 'General Q&A',
        color: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        commands: [
            {
                trigger: 'Any other questions...',
                description:
                    'For any other inquiries, the AI will provide intelligent responses',
            },
        ],
    },
];

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
    emit('update:open', isOpen.value);
};

const closeDialog = () => {
    isOpen.value = false;
    emit('update:open', false);
};
</script>

<template>
    <div>
        <!-- Trigger Button (Info Icon) -->
        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8"
            title="View chatbot capabilities"
            @click="toggleOpen"
        >
            <HelpCircle class="h-5 w-5" />
        </Button>

        <!-- Dialog -->
        <Dialog :open="isOpen" @update:open="closeDialog">
            <DialogContent
                class="max-w-3xl max-h-[80vh] overflow-y-auto"
                @click.stop
            >
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Lightbulb class="h-5 w-5 text-primary" />
                        Chatbot Capabilities & Commands
                    </DialogTitle>
                    <DialogDescription>
                        Discover what the AI assistant can do. Try these
                        commands to get instant insights and manage your
                        inventory.
                    </DialogDescription>
                </DialogHeader>

                <!-- Capabilities List -->
                <div class="space-y-6 py-4">
                    <div
                        v-for="capability in capabilities"
                        :key="capability.category"
                        class="space-y-3"
                    >
                        <!-- Category Header -->
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg"
                                :class="capability.color"
                            >
                                <component
                                    :is="capability.icon"
                                    class="h-5 w-5"
                                />
                            </div>
                            <h3 class="font-semibold">{{ capability.category }}</h3>
                        </div>

                        <!-- Commands -->
                        <div class="ml-13 space-y-2">
                            <div
                                v-for="(cmd, idx) in capability.commands"
                                :key="idx"
                                class="rounded-lg border border-muted bg-muted/30 p-3 transition-all hover:bg-muted/50"
                            >
                                <div class="space-y-1">
                                    <code
                                        class="block text-xs font-semibold text-primary"
                                    >
                                        {{ cmd.trigger }}
                                    </code>
                                    <p class="text-xs text-muted-foreground">
                                        {{ cmd.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Tips -->
                <div class="space-y-3 rounded-lg border border-blue-200/50 bg-blue-50/50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                    <div class="flex gap-2">
                        <AlertCircle class="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" />
                        <div class="space-y-2 text-sm">
                            <p class="font-semibold text-blue-900 dark:text-blue-200">
                                💡 Pro Tips
                            </p>
                            <ul class="space-y-1 text-blue-800/80 dark:text-blue-300/80">
                                <li>
                                    • Try combining keywords in different ways
                                    for variations
                                </li>
                                <li>
                                    • Commands are case-insensitive and support
                                    Indonesian language
                                </li>
                                <li>
                                    • Operation commands require confirmation
                                    before execution
                                </li>
                                <li>
                                    • Query commands (analytics) return instant
                                    results
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" @click="closeDialog">
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
