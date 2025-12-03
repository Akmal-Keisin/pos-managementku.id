<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    CalendarIcon,
    FilterIcon,
    PackageIcon,
    Plus,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface StockHistory {
    id: number;
    product: {
        name: string;
        sku: string;
    };
    user: {
        name: string;
    };
    type: string;
    quantity: number;
    notes: string | null;
    created_at: string;
}

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

interface Props {
    stockHistories: {
        data: StockHistory[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    products: Product[];
    users: User[];
    filters: {
        start_date?: string;
        end_date?: string;
        product_id?: number | string;
        user_id?: number | string;
        type?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Management', href: '/stock-management' },
];

const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const productFilter = ref(props.filters.product_id?.toString() || '');
const userFilter = ref(props.filters.user_id?.toString() || '');
const typeFilter = ref(props.filters.type || '');

const hasActiveFilters = computed(() => {
    return (
        startDateFilter.value ||
        endDateFilter.value ||
        productFilter.value ||
        userFilter.value ||
        typeFilter.value
    );
});

const handleFilter = () => {
    router.get(
        '/stock-management',
        {
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
            product_id: productFilter.value || undefined,
            user_id: userFilter.value || undefined,
            type: typeFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    startDateFilter.value = '';
    endDateFilter.value = '';
    productFilter.value = '';
    userFilter.value = '';
    typeFilter.value = '';
    router.get('/stock-management', {}, { preserveState: false });
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

const truncateText = (text: string | null, length: number = 50) => {
    if (!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <Head title="Stock Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <PackageIcon class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Stock Management</h1>
                        <p class="text-sm text-muted-foreground">
                            View stock movement history and manage inventory
                        </p>
                    </div>
                </div>
                <Link href="/stock-management/update-stock">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Update Stock
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

                    <!-- Product Filter -->
                    <div class="space-y-2">
                        <Label for="product">Product</Label>
                        <Select v-model="productFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="All products" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="All Products"
                                    >All products</SelectItem
                                >
                                <template
                                    v-for="product in props.products"
                                    :key="product.id"
                                >
                                    <SelectItem :value="product.id.toString()">
                                        {{ product.name }} ({{ product.sku }})
                                    </SelectItem>
                                </template>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- User Filter -->
                    <div class="space-y-2">
                        <Label for="user">User</Label>
                        <Select v-model="userFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="All Users"
                                    >All users</SelectItem
                                >
                                <template
                                    v-for="user in props.users"
                                    :key="user.id"
                                >
                                    <SelectItem :value="user.id.toString()">
                                        {{ user.name }}
                                    </SelectItem>
                                </template>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Type Filter -->
                    <div class="space-y-2">
                        <Label for="type">Type</Label>
                        <Select v-model="typeFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="All Types"
                                    >All types</SelectItem
                                >
                                <SelectItem value="increase"
                                    >Increase</SelectItem
                                >
                                <SelectItem value="decrease"
                                    >Decrease</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <Button @click="handleFilter">
                        <FilterIcon class="mr-2 h-4 w-4" />
                        Apply Filters
                    </Button>
                </div>
            </div>

            <!-- Stock History Table -->
            <div class="flex-1 rounded-lg border bg-card shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Updated By</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Quantity</TableHead>
                            <TableHead>Notes</TableHead>
                            <TableHead>Date</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.stockHistories.data.length === 0">
                            <TableCell colspan="7" class="h-32 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-muted-foreground"
                                >
                                    <PackageIcon class="mb-2 h-8 w-8" />
                                    <p class="font-medium">
                                        No stock history found
                                    </p>
                                    <p class="text-sm">
                                        Try adjusting your filters
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="history in props.stockHistories.data"
                            :key="history.id"
                        >
                            <TableCell class="font-medium">{{
                                history.product.name
                            }}</TableCell>
                            <TableCell>
                                <Badge variant="outline">{{
                                    history.product.sku
                                }}</Badge>
                            </TableCell>
                            <TableCell>{{ history.user.name }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        history.type === 'increase'
                                            ? 'default'
                                            : 'destructive'
                                    "
                                    class="flex w-fit items-center gap-1"
                                >
                                    <ArrowUpIcon
                                        v-if="history.type === 'increase'"
                                        class="h-3 w-3"
                                    />
                                    <ArrowDownIcon v-else class="h-3 w-3" />
                                    {{
                                        history.type.charAt(0).toUpperCase() +
                                        history.type.slice(1)
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell class="font-semibold">
                                {{ history.quantity }}
                            </TableCell>
                            <TableCell class="max-w-xs">
                                <span
                                    :title="history.notes || '-'"
                                    class="text-muted-foreground"
                                >
                                    {{ truncateText(history.notes) }}
                                </span>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(history.created_at) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Info -->
            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <div>
                    Showing {{ props.stockHistories.data.length }} of
                    {{ props.stockHistories.total }} records
                </div>
                <div v-if="props.stockHistories.last_page > 1">
                    Page {{ props.stockHistories.current_page }} of
                    {{ props.stockHistories.last_page }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
