<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarIcon,
    EyeIcon,
    FileText,
    FilterIcon,
    ReceiptIcon,
    SearchIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
}

interface User {
    id: number;
    name: string;
    username: string;
}

interface TransactionDetail {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: Product;
}

interface Transaction {
    id: number;
    user_id: number;
    total: number;
    created_at: string;
    user: User;
    details: TransactionDetail[];
}

interface Props {
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    users: User[];
    products: Product[];
    filters: {
        search?: string;
        start_date?: string;
        end_date?: string;
        user_id?: number | string;
        product_id?: number | string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reporting', href: '/reporting' },
];

const searchQuery = ref(props.filters.search || '');
const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const userFilter = ref(props.filters.user_id?.toString() || '');
const productFilter = ref(props.filters.product_id?.toString() || '');

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        startDateFilter.value ||
        endDateFilter.value ||
        userFilter.value ||
        productFilter.value
    );
});

const handleFilter = () => {
    router.get(
        '/reporting',
        {
            search: searchQuery.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
            user_id: userFilter.value || undefined,
            product_id: productFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    startDateFilter.value = '';
    endDateFilter.value = '';
    userFilter.value = '';
    productFilter.value = '';
    router.get('/reporting', {}, { preserveState: false });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(amount);
};

const getItemsCount = (transaction: Transaction) => {
    return transaction.details.reduce(
        (sum, detail) => sum + detail.quantity,
        0,
    );
};

const handlePageChange = (page: number) => {
    router.get(
        '/reporting',
        {
            page,
            search: searchQuery.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
            user_id: userFilter.value || undefined,
            product_id: productFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const paginationPages = computed(() => {
    const pages: (number | 'ellipsis')[] = [];
    const currentPage = props.transactions.current_page;
    const lastPage = props.transactions.last_page;
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
    <Head title="Transaction Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <FileText class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Transaction Reports</h1>
                        <p class="text-sm text-muted-foreground">
                            View and filter transaction history
                        </p>
                    </div>
                </div>
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

                    <!-- User Filter -->
                    <div class="space-y-2">
                        <Label for="user">User</Label>
                        <Select v-model="userFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="All User"
                                    >All users</SelectItem
                                >
                                <template
                                    v-for="user in props.users"
                                    :key="user.id"
                                >
                                    <SelectItem :value="user.id">
                                        {{ user.name }} (@{{ user.username }})
                                    </SelectItem>
                                </template>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Product Filter -->
                    <div class="space-y-2">
                        <Label for="product">Product</Label>
                        <Select v-model="productFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="All products" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="All Product"
                                    >All products</SelectItem
                                >
                                <template
                                    v-for="product in props.products"
                                    :key="product.id"
                                >
                                    <SelectItem :value="product.id">
                                        {{ product.name }} ({{ product.sku }})
                                    </SelectItem>
                                </template>
                            </SelectContent>
                        </Select>
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
                                placeholder="Search..."
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

            <!-- Transactions Table -->
            <div class="flex-1 rounded-lg border bg-card shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Transaction ID</TableHead>
                            <TableHead>Date & Time</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Items</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.transactions.data.length === 0">
                            <TableCell colspan="6" class="h-32 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-muted-foreground"
                                >
                                    <ReceiptIcon class="mb-2 h-8 w-8" />
                                    <p class="font-medium">
                                        No transactions found
                                    </p>
                                    <p class="text-sm">
                                        Try adjusting your filters
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="transaction in props.transactions.data"
                            :key="transaction.id"
                            class="cursor-pointer hover:bg-muted/50"
                        >
                            <TableCell class="font-medium">
                                <Badge variant="outline">
                                    #{{ transaction.id }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {{ formatDate(transaction.created_at) }}
                            </TableCell>
                            <TableCell>
                                <div>
                                    <div class="font-medium">
                                        {{ transaction.user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        @{{ transaction.user.username }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary">
                                    {{ getItemsCount(transaction) }} items
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right font-semibold">
                                {{ formatCurrency(transaction.total) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="`/reporting/${transaction.id}`">
                                    <Button variant="ghost" size="sm">
                                        <EyeIcon class="mr-1 h-4 w-4" />
                                        View Details
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.transactions.last_page > 1"
                class="flex items-center justify-between px-2"
            >
                <div class="text-sm text-muted-foreground">
                    Showing
                    {{
                        (props.transactions.current_page - 1) *
                            props.transactions.per_page +
                        1
                    }}
                    to
                    {{
                        Math.min(
                            props.transactions.current_page *
                                props.transactions.per_page,
                            props.transactions.total,
                        )
                    }}
                    of {{ props.transactions.total }} transactions
                </div>

                <Pagination
                    :total="props.transactions.total"
                    :items-per-page="props.transactions.per_page"
                    :default-page="props.transactions.current_page"
                    :sibling-count="1"
                    show-edges
                >
                    <PaginationContent>
                        <PaginationFirst
                            :disabled="props.transactions.current_page === 1"
                            @click="handlePageChange(1)"
                        />
                        <PaginationPrevious
                            :disabled="props.transactions.current_page === 1"
                            @click="
                                handlePageChange(
                                    props.transactions.current_page - 1,
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
                                    page === props.transactions.current_page
                                "
                                @click="handlePageChange(page)"
                            >
                                {{ page }}
                            </PaginationItem>
                        </template>

                        <PaginationNext
                            :disabled="
                                props.transactions.current_page ===
                                props.transactions.last_page
                            "
                            @click="
                                handlePageChange(
                                    props.transactions.current_page + 1,
                                )
                            "
                        />
                        <PaginationLast
                            :disabled="
                                props.transactions.current_page ===
                                props.transactions.last_page
                            "
                            @click="
                                handlePageChange(props.transactions.last_page)
                            "
                        />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
