<script setup lang="ts">
import ProductCard from '@/components/pos/ProductCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { SearchIcon, ShoppingCartIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
        links: {
            first: string | null;
            last: string | null;
            prev: string | null;
            next: string | null;
        };
    };
    auth?: { user?: { id?: number } };
}>();

const search = ref('');
const allProducts = ref<Product[]>([...props.products.data]);
const currentPage = ref(props.products.current_page);
const isLoading = ref(false);
const hasMorePages = computed(
    () => currentPage.value < props.products.last_page,
);
const scrollContainer = ref<HTMLElement | null>(null);
const searchTimeout = ref<number | null>(null);

// Update products when props change (e.g., after search)
watch(
    () => props.products.data,
    (newData) => {
        // If we're on page 1, replace all products (new search)
        if (props.products.current_page === 1) {
            allProducts.value = [...newData];
            currentPage.value = 1;
        }
    },
);

// Search handler with debounce
watch(search, (newValue) => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }

    searchTimeout.value = window.setTimeout(() => {
        currentPage.value = 1;
        allProducts.value = [];
        router.get(
            '/pos-terminal',
            { search: newValue || undefined },
            {
                preserveState: true,
                preserveScroll: false,
                only: ['products'],
            },
        );
    }, 300);
});

// Load more products
const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) {
        return;
    }

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        '/pos-terminal',
        {
            page: nextPage,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['products'],
            onSuccess: (page) => {
                const newProducts = (
                    page.props.products as typeof props.products
                ).data;
                allProducts.value = [...allProducts.value, ...newProducts];
                currentPage.value = nextPage;
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
            },
        },
    );
};

// Infinite scroll handler
const handleScroll = () => {
    if (!scrollContainer.value || isLoading.value || !hasMorePages.value) {
        return;
    }

    const container = scrollContainer.value;
    const scrollTop = container.scrollTop;
    const scrollHeight = container.scrollHeight;
    const clientHeight = container.clientHeight;

    // Load more when user scrolls to within 200px of the bottom
    if (scrollTop + clientHeight >= scrollHeight - 200) {
        loadMore();
    }
};

onMounted(() => {
    scrollContainer.value = document.querySelector('.scroll-container');
    if (scrollContainer.value) {
        scrollContainer.value.addEventListener('scroll', handleScroll);
    }
});

onUnmounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.removeEventListener('scroll', handleScroll);
    }
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
});
</script>

<template>
    <Head title="POS Terminal" />

    <AppLayout>
        <div class="flex h-screen flex-col overflow-hidden">
            <!-- Header - Sticky on mobile -->
            <div
                class="z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
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

            <!-- Products Grid with Infinite Scroll -->
            <div
                class="scroll-container custom-scrollbar flex-1 overflow-y-auto"
            >
                <div class="container mx-auto px-4 py-4 sm:py-6">
                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    >
                        <ProductCard
                            v-for="product in allProducts"
                            :key="product.id"
                            :product="product"
                        />
                    </div>

                    <!-- Loading Indicator -->
                    <div
                        v-if="isLoading"
                        class="flex items-center justify-center py-8"
                    >
                        <div
                            class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"
                        ></div>
                        <span class="ml-3 text-sm text-muted-foreground"
                            >Loading more products...</span
                        >
                    </div>

                    <!-- End of Results -->
                    <div
                        v-if="!hasMorePages && allProducts.length > 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        You've reached the end of the product list
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="allProducts.length === 0 && !isLoading"
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
                            <strong>{{ allProducts.length }}</strong> of
                            <strong>{{ props.products.total }}</strong> products
                        </div>
                        <div v-if="props.products.last_page > 1">
                            Page {{ currentPage }} of
                            {{ props.products.last_page }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
