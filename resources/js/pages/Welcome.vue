<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Package,
    Shield,
    ShoppingCart,
    Users,
    Zap,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        icon: ShoppingCart,
        title: 'POS Terminal',
        description:
            'Modern point-of-sale interface with cart management and quick checkout',
    },
    {
        icon: Package,
        title: 'Product Management',
        description:
            'Complete product catalog with SKU, pricing, stock tracking, and images',
    },
    {
        icon: Users,
        title: 'User Management',
        description:
            'Role-based access control for Super Admin, Admin, and Cashier roles',
    },
    {
        icon: BarChart3,
        title: 'Stock Management',
        description:
            'Real-time inventory tracking with stock history and automated updates',
    },
    {
        icon: Shield,
        title: 'Secure Authentication',
        description:
            'Username-based authentication with optional two-factor authentication',
    },
    {
        icon: Zap,
        title: 'Fast & Modern',
        description:
            'Built with Laravel 12, Vue 3, and Inertia.js for blazing fast performance',
    },
];
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <div class="flex items-center gap-2">
                    <ShoppingCart
                        class="h-6 w-6 text-[var(--foreground)] dark:text-white"
                    />
                    <span class="text-xl font-bold">POS Management</span>
                </div>
                <nav class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button>Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()">
                            <Button>Log in</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 text-center">
            <div class="mx-auto max-w-3xl space-y-6">
                <h1
                    class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl"
                >
                    Modern Point of Sales
                    <span class="text-primary">Management System</span>
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    A comprehensive POS solution designed for retail businesses.
                    Manage products, track inventory, process transactions, and
                    handle multiple user roles with ease.
                </p>
                <div
                    class="flex flex-wrap items-center justify-center gap-4 pt-4"
                >
                    <Link v-if="!$page.props.auth.user" :href="login()">
                        <Button size="lg" class="text-base">
                            Get Started
                        </Button>
                    </Link>
                    <Link v-else :href="dashboard()">
                        <Button size="lg" class="text-base">
                            Go to Dashboard
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="mx-auto max-w-5xl space-y-12">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Everything You Need
                    </h2>
                    <p class="mt-2 text-muted-foreground">
                        Powerful features to streamline your business operations
                    </p>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="feature in features" :key="feature.title">
                        <CardHeader>
                            <component
                                :is="feature.icon"
                                class="mb-2 h-10 w-10 text-primary"
                            />
                            <CardTitle class="text-xl">{{
                                feature.title
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ feature.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="bg-muted/50 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl space-y-6 text-center">
                    <h2 class="text-3xl font-bold tracking-tight">
                        About This Project
                    </h2>
                    <div class="space-y-4 text-left text-muted-foreground">
                        <p>
                            This Point of Sales Management System is built to
                            demonstrate modern web application development using
                            cutting-edge technologies. It showcases a complete
                            solution for retail businesses with role-based
                            access control and comprehensive inventory
                            management.
                        </p>
                        <p>The system supports three distinct user roles:</p>
                        <ul class="list-inside list-disc space-y-2 pl-4">
                            <li>
                                <strong>Super Admin</strong> - Full system
                                access including admin user management
                            </li>
                            <li>
                                <strong>Admin</strong> - Access to all business
                                features except admin management
                            </li>
                            <li>
                                <strong>Cashier</strong> - Focused access to POS
                                terminal for daily transactions
                            </li>
                        </ul>
                        <p>
                            Built with Laravel 12 backend, Vue 3 with TypeScript
                            frontend, Inertia.js for seamless SPA experience,
                            and styled with Tailwind CSS and shadcn-vue
                            components for a modern, accessible interface.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border py-8">
            <div
                class="container mx-auto px-4 text-center text-sm text-muted-foreground"
            >
                <p>
                    © {{ new Date().getFullYear() }} POS Management System.
                    Built with Laravel & Vue.js.
                </p>
            </div>
        </footer>
    </div>
</template>
