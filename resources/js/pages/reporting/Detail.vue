<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    PackageIcon,
    ReceiptIcon,
    UserIcon,
} from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    sku: string;
}

interface User {
    id: number;
    name: string;
    username: string;
    role: string;
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
    transaction: Transaction;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reporting', href: '/reporting' },
    { title: `Transaction #${props.transaction.id}`, href: '' },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(amount);
};

const getTotalItems = () => {
    return props.transaction.details.reduce(
        (sum, detail) => sum + detail.quantity,
        0,
    );
};

// const handlePrint = () => {
//     window.print();
// };
</script>

<template>
    <Head :title="`Transaction #${transaction.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/reporting">
                        <Button variant="ghost" size="icon">
                            <ArrowLeftIcon class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div class="rounded-lg bg-primary/10 p-2">
                        <ReceiptIcon class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">
                            Transaction #{{ transaction.id }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            View transaction details and items
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <!-- Transaction Info Cards -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-medium"
                        >
                            <CalendarIcon
                                class="h-4 w-4 text-muted-foreground"
                            />
                            Date & Time
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-semibold">
                            {{ formatDate(transaction.created_at) }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-medium"
                        >
                            <UserIcon class="h-4 w-4 text-muted-foreground" />
                            Processed By
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-semibold">
                            {{ transaction.user.name }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            @{{ transaction.user.username }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-medium"
                        >
                            <PackageIcon
                                class="h-4 w-4 text-muted-foreground"
                            />
                            Total Items
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-semibold">
                            {{ getTotalItems() }} items
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Transaction Details -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle>Transaction Items</CardTitle>
                    <CardDescription>
                        List of products in this transaction
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead class="text-center"
                                    >Quantity</TableHead
                                >
                                <TableHead class="text-right"
                                    >Unit Price</TableHead
                                >
                                <TableHead class="text-right"
                                    >Subtotal</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="detail in transaction.details"
                                :key="detail.id"
                            >
                                <TableCell>
                                    <div>
                                        <div class="font-medium">
                                            {{ detail.product.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            SKU: {{ detail.product.sku }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="secondary">
                                        {{ detail.quantity }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    {{ formatCurrency(detail.price) }}
                                </TableCell>
                                <TableCell class="text-right font-semibold">
                                    {{ formatCurrency(detail.total) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <Separator class="my-4" />

                    <!-- Summary -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-medium">
                                {{ formatCurrency(transaction.total) }}
                            </span>
                        </div>
                        <Separator />
                        <div
                            class="flex items-center justify-between text-lg font-bold"
                        >
                            <span>Total</span>
                            <span class="text-primary">
                                {{ formatCurrency(transaction.total) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons -->
            <div class="flex justify-between">
                <Link href="/reporting">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4" />
                        Back to Reports
                    </Button>
                </Link>
                <!-- TODO: Update to generate  pdf -->
                <!-- Temporary Disabled -->
                <!-- <Button @click="handlePrint"> Print Receipt </Button> -->
            </div>
        </div>
    </AppLayout>
</template>
