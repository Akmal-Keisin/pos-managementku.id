<script setup lang="ts">
import ProductCard from '@/components/pos/ProductCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { SearchIcon, ShoppingCartIcon } from 'lucide-vue-next';
import { ref } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    price?: number;
    current_stock?: number;
    total_sold?: number;
    description?: string;
    image_url?: string | null;
}

const props = defineProps<{
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    auth?: { user?: { id?: number } };
}>();

const search = ref('');
</script>

<template>
    <Head title="POS Terminal" />

    <AppLayout>
        <div class="flex min-h-screen flex-col">
            <!-- Header - Sticky on mobile -->
            <div
                class="sticky top-0 z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
            >
                <div class="border-b">
                    <div class="container mx-auto px-4 py-3 sm:py-4">
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <h1 class="text-xl font-bold sm:text-2xl">
                                POS Terminal
                            </h1>
                            <Button
                                as="a"
                                :href="`/pos-terminal/${props.auth?.user?.id ?? 'me'}/cart`"
                                size="sm"
                                class="w-full sm:w-auto"
                            >
                                <ShoppingCartIcon class="mr-2 h-4 w-4" />
                                Open Cart
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="border-b bg-muted/30">
                    <div class="container mx-auto px-4 py-3">
                        <div class="relative">
                            <SearchIcon
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                v-model="search"
                                placeholder="Search products by name or SKU..."
                                class="input w-full pl-10"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1">
                <div class="container mx-auto px-4 py-4 sm:py-6">
                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    >
                        <ProductCard
                            v-for="product in props.products.data"
                            :key="product.id"
                            :product="product"
                        />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="props.products.data.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <div class="mb-4 text-4xl text-muted-foreground">
                            📦
                        </div>
                        <h3 class="mb-2 text-lg font-semibold">
                            No products found
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Try adjusting your search criteria
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t bg-muted/30">
                <div class="container mx-auto px-4 py-3">
                    <div
                        class="flex flex-col gap-2 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:text-sm"
                    >
                        <div>
                            Showing
                            <strong>{{ props.products.data.length }}</strong> of
                            <strong>{{ props.products.total }}</strong> products
                        </div>
                        <div v-if="props.products.last_page > 1">
                            Page {{ props.products.current_page }} of
                            {{ props.products.last_page }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
