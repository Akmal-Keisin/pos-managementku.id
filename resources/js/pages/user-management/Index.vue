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
    CalendarIcon,
    FilterIcon,
    MoreVertical,
    Plus,
    SearchIcon,
    Users,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface User {
    id: number;
    name: string;
    username: string;
    role: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
        start_date?: string;
        end_date?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'User Management', href: '/user-management' },
];

const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const startDateFilter = ref(props.filters.start_date || '');
const endDateFilter = ref(props.filters.end_date || '');
const deleteDialog = ref(false);
const userToDelete = ref<User | null>(null);

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        roleFilter.value ||
        startDateFilter.value ||
        endDateFilter.value
    );
});

const handleFilter = () => {
    router.get(
        '/user-management',
        {
            search: searchQuery.value || undefined,
            role: roleFilter.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
    startDateFilter.value = '';
    endDateFilter.value = '';
    router.get('/user-management', {}, { preserveState: false });
};

const openDeleteDialog = (user: User) => {
    userToDelete.value = user;
    deleteDialog.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(`/user-management/${userToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialog.value = false;
                userToDelete.value = null;
            },
        });
    }
};

const handlePageChange = (page: number) => {
    router.get(
        '/user-management',
        {
            page,
            search: searchQuery.value || undefined,
            role: roleFilter.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const paginationPages = computed(() => {
    const pages: (number | 'ellipsis')[] = [];
    const currentPage = props.users.current_page;
    const lastPage = props.users.last_page;
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
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <Users class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">User Management</h1>
                        <p class="text-sm text-muted-foreground">
                            View user details and manage your users.
                        </p>
                    </div>
                </div>
                <Link href="/user-management/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Create User
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

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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

                    <!-- Role Filter -->
                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <Select v-model="roleFilter">
                            <SelectTrigger id="role">
                                <SelectValue placeholder="All roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All roles</SelectItem>
                                <SelectItem value="super-admin"
                                    >Super Admin</SelectItem
                                >
                                <SelectItem value="admin">Admin</SelectItem>
                                <SelectItem value="cashier">Cashier</SelectItem>
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
                                placeholder="Search users..."
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
                            <TableHead>Username</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell>{{ user.name }}</TableCell>
                            <TableCell>{{ user.username }}</TableCell>
                            <TableCell>{{ user.role }}</TableCell>
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
                                                :href="`/user-management/${user.id}/edit`"
                                            >
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openDeleteDialog(user)"
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
                v-if="props.users.last_page > 1"
                class="flex items-center justify-between px-2"
            >
                <div class="text-sm text-muted-foreground">
                    Showing
                    {{
                        (props.users.current_page - 1) * props.users.per_page +
                        1
                    }}
                    to
                    {{
                        Math.min(
                            props.users.current_page * props.users.per_page,
                            props.users.total,
                        )
                    }}
                    of {{ props.users.total }} users
                </div>

                <Pagination
                    :total="props.users.total"
                    :items-per-page="props.users.per_page"
                    :default-page="props.users.current_page"
                    :sibling-count="1"
                    show-edges
                >
                    <PaginationContent>
                        <PaginationFirst
                            :disabled="props.users.current_page === 1"
                            @click="handlePageChange(1)"
                        />
                        <PaginationPrevious
                            :disabled="props.users.current_page === 1"
                            @click="
                                handlePageChange(props.users.current_page - 1)
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
                                :is-active="page === props.users.current_page"
                                @click="handlePageChange(page)"
                            >
                                {{ page }}
                            </PaginationItem>
                        </template>

                        <PaginationNext
                            :disabled="
                                props.users.current_page ===
                                props.users.last_page
                            "
                            @click="
                                handlePageChange(props.users.current_page + 1)
                            "
                        />
                        <PaginationLast
                            :disabled="
                                props.users.current_page ===
                                props.users.last_page
                            "
                            @click="handlePageChange(props.users.last_page)"
                        />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="deleteDialog" @update:open="deleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will delete the user "{{ userToDelete?.name }}".
                        This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="deleteUser"
                        >Delete</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
