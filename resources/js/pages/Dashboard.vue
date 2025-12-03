<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ActivityIcon,
    AlertTriangleIcon,
    ArrowDownIcon,
    ArrowUpIcon,
    DollarSignIcon,
    PackageIcon,
    ReceiptIcon,
    TrendingUpIcon,
    UsersIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    total_sold?: number;
    current_stock: number;
    price: number;
}

interface Transaction {
    id: number;
    user: {
        name: string;
        username: string;
    };
    total: number;
    items_count: number;
    created_at: string;
}

interface StockMovement {
    id: number;
    product: {
        name: string;
        sku: string;
    };
    user: {
        name: string;
    };
    type: 'increase' | 'decrease';
    quantity: number;
    created_at: string;
}

interface UserActivity {
    id: number;
    name: string;
    username: string;
    role: string;
    stock_histories_count: number;
}

interface SalesByUser {
    user: {
        name: string;
        username: string;
    };
    transaction_count: number;
    total_sales: number;
}

interface RevenueTrend {
    date: string;
    revenue: number;
    transactions: number;
}

interface Props {
    stats: {
        total_revenue: number;
        today_revenue: number;
        total_transactions: number;
        today_transactions: number;
        total_products: number;
        low_stock_products: number;
    };
    revenue_trends: RevenueTrend[];
    top_products: Product[];
    low_stock_items: Product[];
    recent_transactions: Transaction[];
    recent_stock_movements: StockMovement[];
    sales_by_user: SalesByUser[];
    user_activity: UserActivity[] | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatShortDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
};

const todayRevenuePercentage = computed(() => {
    if (props.stats.total_revenue === 0) return 0;
    return (
        (props.stats.today_revenue / props.stats.total_revenue) *
        100
    ).toFixed(1);
});

const todayTransactionsPercentage = computed(() => {
    if (props.stats.total_transactions === 0) return 0;
    return (
        (props.stats.today_transactions / props.stats.total_transactions) *
        100
    ).toFixed(1);
});

const maxRevenue = computed(() => {
    return Math.max(...props.revenue_trends.map((t) => t.revenue), 1);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Welcome Section -->
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    Welcome back! Here's an overview of your POS system.
                </p>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Total Revenue -->
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Total Revenue
                        </CardTitle>
                        <DollarSignIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(stats.total_revenue) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Today: {{ formatCurrency(stats.today_revenue) }}
                            <span class="text-primary"
                                >({{ todayRevenuePercentage }}%)</span
                            >
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Transactions -->
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Transactions
                        </CardTitle>
                        <ReceiptIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_transactions }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Today: {{ stats.today_transactions }}
                            <span class="text-primary"
                                >({{ todayTransactionsPercentage }}%)</span
                            >
                        </p>
                    </CardContent>
                </Card>

                <!-- Products & Low Stock -->
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Products
                        </CardTitle>
                        <PackageIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_products }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            <span
                                v-if="stats.low_stock_products > 0"
                                class="text-destructive"
                            >
                                {{ stats.low_stock_products }} low stock items
                            </span>
                            <span v-else class="text-green-600">
                                All items have sufficient stock
                            </span>
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Revenue Trend Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Revenue Trend (Last 7 Days)</CardTitle>
                    <CardDescription>
                        Daily revenue and transaction count
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="trend in revenue_trends"
                            :key="trend.date"
                            class="flex items-center gap-4"
                        >
                            <div class="w-16 text-sm text-muted-foreground">
                                {{ formatShortDate(trend.date) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-8 rounded bg-primary transition-all"
                                        :style="{
                                            width: `${(trend.revenue / maxRevenue) * 100}%`,
                                        }"
                                    ></div>
                                    <span class="text-sm font-medium">
                                        {{ formatCurrency(trend.revenue) }}
                                    </span>
                                    <Badge variant="secondary" class="ml-auto">
                                        {{ trend.transactions }} txn
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="revenue_trends.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            No revenue data available
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Top Selling Products -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUpIcon class="h-5 w-5 text-primary" />
                            Top Selling Products
                        </CardTitle>
                        <CardDescription>
                            Best performing products by sales volume
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(product, index) in top_products"
                                :key="product.id"
                                class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary"
                                >
                                    {{ index + 1 }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium">
                                        {{ product.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        SKU: {{ product.sku }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold">
                                        {{ product.total_sold }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        sold
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="top_products.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No sales data yet
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Low Stock Alert -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertTriangleIcon
                                class="h-5 w-5 text-destructive"
                            />
                            Low Stock Alert
                        </CardTitle>
                        <CardDescription>
                            Products that need restocking
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="product in low_stock_items"
                                :key="product.id"
                                class="flex items-center gap-3 rounded-lg border border-destructive/20 bg-destructive/5 p-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium">
                                        {{ product.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        SKU: {{ product.sku }}
                                    </div>
                                </div>
                                <Badge
                                    variant="destructive"
                                    class="font-semibold"
                                >
                                    {{ product.current_stock }} left
                                </Badge>
                            </div>
                            <div
                                v-if="low_stock_items.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                All products have sufficient stock
                            </div>
                            <Link
                                v-if="low_stock_items.length > 0"
                                href="/stock-management"
                                class="block text-center text-sm text-primary hover:underline"
                            >
                                Manage Stock →
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Recent Transactions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Transactions</CardTitle>
                        <CardDescription>
                            Latest sales activities
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead class="text-right"
                                        >Total</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="transaction in recent_transactions"
                                    :key="transaction.id"
                                >
                                    <TableCell>
                                        <Badge variant="outline">
                                            #{{ transaction.id }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ transaction.user.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    transaction.created_at,
                                                )
                                            }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="font-semibold">
                                            {{
                                                formatCurrency(
                                                    transaction.total,
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ transaction.items_count }} items
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="recent_transactions.length === 0"
                                >
                                    <TableCell
                                        colspan="3"
                                        class="h-24 text-center text-sm text-muted-foreground"
                                    >
                                        No transactions yet
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <Link
                            v-if="recent_transactions.length > 0"
                            href="/reporting"
                            class="mt-4 block text-center text-sm text-primary hover:underline"
                        >
                            View All Transactions →
                        </Link>
                    </CardContent>
                </Card>

                <!-- Recent Stock Movements -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Stock Movements</CardTitle>
                        <CardDescription>
                            Latest inventory updates
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="movement in recent_stock_movements"
                                :key="movement.id"
                                class="flex items-center gap-3 rounded-lg border p-3"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full"
                                    :class="
                                        movement.type === 'increase'
                                            ? 'bg-green-100 text-green-600'
                                            : 'bg-red-100 text-red-600'
                                    "
                                >
                                    <ArrowUpIcon
                                        v-if="movement.type === 'increase'"
                                        class="h-4 w-4"
                                    />
                                    <ArrowDownIcon v-else class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium">
                                        {{ movement.product.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        by {{ movement.user.name }} •
                                        {{ formatDate(movement.created_at) }}
                                    </div>
                                </div>
                                <Badge
                                    :variant="
                                        movement.type === 'increase'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        movement.type === 'increase' ? '+' : '-'
                                    }}
                                    {{ movement.quantity }}
                                </Badge>
                            </div>
                            <div
                                v-if="recent_stock_movements.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No stock movements yet
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sales by User (Today) & User Activity -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Today's Sales by User -->
                <Card v-if="sales_by_user.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UsersIcon class="h-5 w-5 text-primary" />
                            Today's Sales by User
                        </CardTitle>
                        <CardDescription>
                            Sales performance for today
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(sale, index) in sales_by_user"
                                :key="index"
                                class="flex items-center gap-3 rounded-lg border p-3"
                            >
                                <div class="flex-1">
                                    <div class="font-medium">
                                        {{ sale.user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        @{{ sale.user.username }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold">
                                        {{ formatCurrency(sale.total_sales) }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            sale.transaction_count
                                        }}
                                        transactions
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- User Activity -->
                <Card v-if="user_activity && user_activity.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ActivityIcon class="h-5 w-5 text-primary" />
                            User Activity
                        </CardTitle>
                        <CardDescription>
                            Most active staff members
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="user in user_activity"
                                :key="user.id"
                                class="flex items-center gap-3 rounded-lg border p-3"
                            >
                                <div class="flex-1">
                                    <div class="font-medium">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        @{{ user.username }} • {{ user.role }}
                                    </div>
                                </div>
                                <Badge variant="secondary">
                                    {{ user.stock_histories_count }} actions
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
