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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { router } from '@inertiajs/vue3';
import {
    MinusIcon,
    PackageIcon,
    PlusIcon,
    ShoppingCartIcon,
    TagIcon,
    TrendingUpIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    product: {
        id: number;
        name: string;
        sku: string;
        price?: number;
        current_stock?: number;
        total_sold?: number;
        description?: string;
        image_url?: string | null;
    };
}>();

const open = ref(false);
const qty = ref(1);
const showAddToCartDialog = ref(false);
const showCheckoutDialog = ref(false);

const isLowStock = computed(() => (props.product.current_stock ?? 0) < 10);
const isOutOfStock = computed(() => (props.product.current_stock ?? 0) === 0);

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(props.product.price ?? 0);
});

const totalPrice = computed(() => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format((props.product.price ?? 0) * qty.value);
});

const incrementQty = () => {
    if (qty.value < (props.product.current_stock ?? 0)) {
        qty.value++;
    }
};

const decrementQty = () => {
    if (qty.value > 1) {
        qty.value--;
    }
};

const confirmAddToCart = () => {
    if (isOutOfStock.value) return;
    showAddToCartDialog.value = true;
};

const confirmCheckout = () => {
    if (isOutOfStock.value) return;
    showCheckoutDialog.value = true;
};

const addToCart = async () => {
    await router.post('/pos-terminal/add-to-cart', {
        product_id: props.product.id,
        quantity: qty.value,
    });
    showAddToCartDialog.value = false;
    open.value = false;
    qty.value = 1;
};

const checkout = async () => {
    await router.post('/pos-terminal/checkout', {
        product_id: props.product.id,
        quantity: qty.value,
    });
    showCheckoutDialog.value = false;
    open.value = false;
    qty.value = 1;
};
</script>

<template>
    <Card
        class="group cursor-pointer overflow-hidden transition-all hover:shadow-lg"
        :class="{ 'opacity-60': isOutOfStock }"
        @click="open = true"
    >
        <CardContent class="p-0">
            <!-- Product Image -->
            <div class="relative aspect-square w-full overflow-hidden bg-muted">
                <img
                    :src="props.product.image_url || '/assets/no-image.png'"
                    :alt="props.product.name"
                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                />

                <!-- Stock Badge -->
                <div class="absolute top-2 right-2 flex flex-col gap-1">
                    <Badge
                        v-if="isOutOfStock"
                        variant="destructive"
                        class="shadow-sm"
                    >
                        Out of Stock
                    </Badge>
                    <Badge
                        v-else-if="isLowStock"
                        variant="secondary"
                        class="bg-yellow-500/90 text-white shadow-sm"
                    >
                        Low Stock
                    </Badge>
                </div>
            </div>

            <!-- Product Info -->
            <div class="space-y-2 p-3">
                <!-- Product Name -->
                <h3
                    class="line-clamp-2 min-h-[2.5rem] text-sm leading-tight font-semibold"
                >
                    {{ props.product.name }}
                </h3>

                <!-- SKU -->
                <div
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <TagIcon class="h-3 w-3" />
                    <span class="truncate">{{ props.product.sku }}</span>
                </div>

                <!-- Price -->
                <div class="text-base font-bold text-primary">
                    {{ formattedPrice }}
                </div>

                <!-- Stats -->
                <div
                    class="flex items-center justify-between gap-2 pt-1 text-xs"
                >
                    <div
                        class="flex items-center gap-1"
                        :class="{ 'text-destructive': isLowStock }"
                    >
                        <PackageIcon class="h-3 w-3" />
                        <span class="font-medium">{{
                            props.product.current_stock ?? 0
                        }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-muted-foreground">
                        <TrendingUpIcon class="h-3 w-3" />
                        <span>{{ props.product.total_sold ?? 0 }} sold</span>
                    </div>
                </div>
            </div>
        </CardContent>

        <Sheet v-model:open="open">
            <SheetContent side="right" class="flex w-full flex-col sm:max-w-md">
                <SheetHeader class="space-y-2 pb-4">
                    <SheetTitle class="text-left text-xl">{{
                        props.product.name
                    }}</SheetTitle>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs">
                            <TagIcon class="mr-1 h-3 w-3" />
                            {{ props.product.sku }}
                        </Badge>
                        <Badge v-if="isOutOfStock" variant="destructive">
                            Out of Stock
                        </Badge>
                        <Badge
                            v-else-if="isLowStock"
                            variant="secondary"
                            class="bg-yellow-500 text-white"
                        >
                            Low Stock
                        </Badge>
                    </div>
                </SheetHeader>

                <div class="flex flex-1 flex-col gap-4 overflow-y-auto pb-4">
                    <!-- Product Image -->
                    <div
                        class="aspect-video w-full overflow-hidden rounded-lg border"
                    >
                        <img
                            :src="
                                props.product.image_url ||
                                '/assets/no-image.png'
                            "
                            :alt="props.product.name"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <!-- Price -->
                    <div class="rounded-lg border bg-muted/50 p-4">
                        <div class="text-sm text-muted-foreground">Price</div>
                        <div class="text-2xl font-bold">
                            {{ formattedPrice }}
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border bg-background p-3">
                            <div
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <PackageIcon class="h-4 w-4" />
                                Stock
                            </div>
                            <div
                                class="mt-1 text-xl font-semibold"
                                :class="{ 'text-destructive': isLowStock }"
                            >
                                {{ props.product.current_stock ?? 0 }}
                            </div>
                        </div>
                        <div class="rounded-lg border bg-background p-3">
                            <div
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <TrendingUpIcon class="h-4 w-4" />
                                Sold
                            </div>
                            <div class="mt-1 text-xl font-semibold">
                                {{ props.product.total_sold ?? 0 }}
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <h4 class="text-sm font-semibold">Description</h4>
                        <div
                            class="rounded-lg border bg-muted/30 p-3 text-sm leading-relaxed"
                        >
                            {{
                                props.product.description ||
                                'No description available.'
                            }}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="space-y-3 border-t pt-4">
                    <!-- Quantity Selector -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Quantity</label>
                        <div class="flex items-center gap-2">
                            <Button
                                size="icon"
                                variant="outline"
                                @click="decrementQty"
                                :disabled="qty <= 1"
                            >
                                <MinusIcon class="h-4 w-4" />
                            </Button>
                            <input
                                type="number"
                                v-model.number="qty"
                                min="1"
                                :max="props.product.current_stock ?? 0"
                                class="input flex-1 text-center text-lg font-semibold"
                                :disabled="isOutOfStock"
                            />
                            <Button
                                size="icon"
                                variant="outline"
                                @click="incrementQty"
                                :disabled="
                                    qty >= (props.product.current_stock ?? 0)
                                "
                            >
                                <PlusIcon class="h-4 w-4" />
                            </Button>
                        </div>
                        <div class="text-center text-sm text-muted-foreground">
                            Total:
                            <span class="font-semibold text-foreground">{{
                                totalPrice
                            }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Button
                            class="flex-1"
                            @click.prevent="confirmAddToCart"
                            :disabled="isOutOfStock"
                        >
                            <ShoppingCartIcon class="mr-2 h-4 w-4" />
                            Add to Cart
                        </Button>
                        <Button
                            class="flex-1"
                            variant="destructive"
                            @click.prevent="confirmCheckout"
                            :disabled="isOutOfStock"
                        >
                            Checkout Now
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Add to Cart Confirmation Dialog -->
        <AlertDialog v-model:open="showAddToCartDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Add to Cart</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to add
                        <strong>{{ qty }}x {{ props.product.name }}</strong> to
                        your cart?
                        <div class="mt-2 text-sm">
                            Total: <strong>{{ totalPrice }}</strong>
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="addToCart"
                        >Confirm</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Checkout Confirmation Dialog -->
        <AlertDialog v-model:open="showCheckoutDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Checkout Now</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to checkout
                        <strong>{{ qty }}x {{ props.product.name }}</strong>
                        immediately?
                        <div class="mt-2 text-sm">
                            Total: <strong>{{ totalPrice }}</strong>
                        </div>
                        <div class="mt-2 text-sm text-yellow-600">
                            This will process the payment immediately.
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="checkout"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >Checkout</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </Card>
</template>
