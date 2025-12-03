<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

interface Props {
    availableRoles: string[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'User Management', href: '/user-management' },
    { title: 'Create User', href: '/user-management/create' },
];

const form = useForm({
    name: '',
    username: '',
    role: '',
    password: '',
    confirm_password: '',
});

const submit = () => {
    form.post('/user-management');
};

const goBack = () => {
    router.visit('/user-management');
};
</script>

<template>
    <Head title="Create User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Create User</h1>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>User Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="username">Username</Label>
                            <Input
                                id="username"
                                v-model="form.username"
                                type="text"
                                required
                            />
                            <p
                                v-if="form.errors.username"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.username }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="role">Role</Label>
                            <Select v-model="form.role" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in availableRoles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ role }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.role"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.role }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="confirm_password"
                                >Confirm Password</Label
                            >
                            <Input
                                id="confirm_password"
                                v-model="form.confirm_password"
                                type="password"
                                required
                            />
                            <p
                                v-if="form.errors.confirm_password"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.confirm_password }}
                            </p>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <Button type="submit" :disabled="form.processing">
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="goBack"
                            >
                                Back
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
