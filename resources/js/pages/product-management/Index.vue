<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarIcon,
    FilterIcon,
    MoreVertical,
    PackageIcon,
    Plus,
    SearchIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    current_stock: number;
    total_sold: number;
    price?: number;
}

interface Props {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        start_date?: string;
        end_date?: string;
        price_min?: number | string;
        price_max?: number | string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Product Management', href: '/product-management' },
];

const searchQuery = ref(props.filters.search || '');
const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const priceMinFilter = ref(props.filters.price_min?.toString() || '');
const priceMaxFilter = ref(props.filters.price_max?.toString() || '');
const deleteDialog = ref(false);
const productToDelete = ref<Product | null>(null);

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        startDateFilter.value ||
        endDateFilter.value ||
        priceMinFilter.value ||
        priceMaxFilter.value
    );
});

const handleFilter = () => {
    router.get(
        '/product-management',
        {
            search: searchQuery.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
            price_min: priceMinFilter.value || undefined,
            price_max: priceMaxFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    startDateFilter.value = '';
    endDateFilter.value = '';
    priceMinFilter.value = '';
    priceMaxFilter.value = '';
    router.get('/product-management', {}, { preserveState: false });
};

const openDeleteDialog = (product: Product) => {
    productToDelete.value = product;
    deleteDialog.value = true;
};

const deleteProduct = () => {
    if (productToDelete.value) {
        router.delete(`/product-management/${productToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialog.value = false;
                productToDelete.value = null;
            },
        });
    }
};

const handlePageChange = (page: number) => {
    router.get(
        '/product-management',
        {
            page,
            search: searchQuery.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
            price_min: priceMinFilter.value || undefined,
            price_max: priceMaxFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const paginationPages = computed(() => {
    const pages: (number | 'ellipsis')[] = [];
    const currentPage = props.products.current_page;
    const lastPage = props.products.last_page;
    const delta = 2;

    pages.push(1);

    const rangeStart = Math.max(2, currentPage - delta);
    const rangeEnd = Math.min(lastPage - 1, currentPage + delta);

    if (rangeStart > 2) {
        pages.push('ellipsis');
    }

    for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
    }

    if (rangeEnd < lastPage - 1) {
        pages.push('ellipsis');
    }

    if (lastPage > 1) {
        pages.push(lastPage);
    }

    return pages;
});
</script>

<template>
    <Head title="Product Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <PackageIcon class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Product Management</h1>
                        <p class="text-sm text-muted-foreground">
                            View product details and manage your inventory.
                        </p>
                    </div>
                </div>
                <Link href="/product-management/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Create Product
                    </Button>
                </Link>
            </div>

            <!-- Filters Section -->
            <div class="rounded-lg border bg-card p-4 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <FilterIcon class="h-4 w-4 text-muted-foreground" />
                    <h3 class="font-semibold">Filters</h3>
                    <Button
                        v-if="hasActiveFilters"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                        class="ml-auto"
                    >
                        <XIcon class="mr-1 h-3 w-3" />
                        Clear Filters
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Start Date Filter -->
                    <div class="space-y-2">
                        <Label for="start_date">Start Date</Label>
                        <div class="relative">
                            <CalendarIcon
                                class="absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="start_date"
                                type="date"
                                v-model="startDateFilter"
                                class="pl-8"
                            />
                        </div>
                    </div>

                    <!-- End Date Filter -->
                    <div class="space-y-2">
                        <Label for="end_date">End Date</Label>
                        <div class="relative">
                            <CalendarIcon
                                class="absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="end_date"
                                type="date"
                                v-model="endDateFilter"
                                class="pl-8"
                            />
                        </div>
                    </div>

                    <!-- Min Price Filter -->
                    <div class="space-y-2">
                        <Label for="price_min">Min Price</Label>
                        <Input
                            id="price_min"
                            type="number"
                            min="0"
                            step="0.01"
                            v-model="priceMinFilter"
                            placeholder="0"
                        />
                    </div>

                    <!-- Max Price Filter -->
                    <div class="space-y-2">
                        <Label for="price_max">Max Price</Label>
                        <Input
                            id="price_max"
                            type="number"
                            min="0"
                            step="0.01"
                            v-model="priceMaxFilter"
                            placeholder="0"
                        />
                    </div>

                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <SearchIcon
                                class="absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="search"
                                v-model="searchQuery"
                                placeholder="Search products..."
                                class="pl-8"
                                @keydown.enter="handleFilter"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <Button @click="handleFilter">
                        <FilterIcon class="mr-2 h-4 w-4" />
                        Apply Filters
                    </Button>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Current Stock</TableHead>
                            <TableHead>Total Sold</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="product in products.data"
                            :key="product.id"
                        >
                            <TableCell>{{ product.name }}</TableCell>
                            <TableCell>{{ product.sku }}</TableCell>
                            <TableCell>{{ product.price ?? 0 }}</TableCell>
                            <TableCell>{{ product.current_stock }}</TableCell>
                            <TableCell>{{ product.total_sold }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link
                                                :href="`/product-management/${product.id}/edit`"
                                            >
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openDeleteDialog(product)"
                                        >
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.products.last_page > 1"
                class="flex items-center justify-between px-2"
            >
                <div class="text-sm text-muted-foreground">
                    Showing
                    {{
                        (props.products.current_page - 1) *
                            props.products.per_page +
                        1
                    }}
                    to
                    {{
                        Math.min(
                            props.products.current_page *
                                props.products.per_page,
                            props.products.total,
                        )
                    }}
                    of {{ props.products.total }} products
                </div>

                <Pagination
                    :total="props.products.total"
                    :items-per-page="props.products.per_page"
                    :default-page="props.products.current_page"
                    :sibling-count="1"
                    show-edges
                >
                    <PaginationContent>
                        <PaginationFirst
                            :disabled="props.products.current_page === 1"
                            @click="handlePageChange(1)"
                        />
                        <PaginationPrevious
                            :disabled="props.products.current_page === 1"
                            @click="
                                handlePageChange(
                                    props.products.current_page - 1,
                                )
                            "
                        />

                        <template
                            v-for="(page, index) in paginationPages"
                            :key="index"
                        >
                            <PaginationEllipsis
                                v-if="page === 'ellipsis'"
                                :index="index"
                            />
                            <PaginationItem
                                v-else
                                :value="page"
                                :is-active="
                                    page === props.products.current_page
                                "
                                @click="handlePageChange(page)"
                            >
                                {{ page }}
                            </PaginationItem>
                        </template>

                        <PaginationNext
                            :disabled="
                                props.products.current_page ===
                                props.products.last_page
                            "
                            @click="
                                handlePageChange(
                                    props.products.current_page + 1,
                                )
                            "
                        />
                        <PaginationLast
                            :disabled="
                                props.products.current_page ===
                                props.products.last_page
                            "
                            @click="handlePageChange(props.products.last_page)"
                        />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>

        <AlertDialog :open="deleteDialog" @update:open="deleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will delete the product "{{
                            productToDelete?.name
                        }}". This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="deleteProduct"
                        >Delete</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
