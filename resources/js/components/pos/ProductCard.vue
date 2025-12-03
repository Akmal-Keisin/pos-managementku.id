<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const addToCart = async () => {
    await router.post('/pos-terminal/add-to-cart', {
        product_id: props.product.id,
        quantity: qty.value,
    });
    open.value = false;
};

const checkout = async () => {
    await router.post('/pos-terminal/checkout', {
        product_id: props.product.id,
        quantity: qty.value,
    });
    open.value = false;
};
</script>

<template>
    <Card class="overflow-hidden">
        <CardContent class="p-4">
            <div class="flex flex-col items-start gap-3">
                <div class="w-full overflow-hidden rounded-md bg-gray-100">
                    <img
                        :src="props.product.image_url || '/assets/no-image.png'"
                        alt="Product image"
                        class="h-36 w-full object-cover"
                    />
                </div>
                <div class="w-full">
                    <h3 class="text-sm font-medium">
                        {{ props.product.name }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        SKU: {{ props.product.sku }}
                    </p>
                </div>
                <div class="flex w-full items-center justify-between">
                    <div class="text-lg font-semibold">
                        {{ props.product.price ?? 0 }}
                    </div>
                    <Button size="sm" @click="open = true">Details</Button>
                </div>
            </div>
        </CardContent>

        <CardFooter>
            <div class="flex w-full items-center justify-between">
                <div class="text-xs text-muted-foreground">
                    Stock: {{ props.product.current_stock ?? 0 }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Sold: {{ props.product.total_sold ?? 0 }}
                </div>
            </div>
        </CardFooter>

        <Sheet v-model:open="open">
            <SheetContent side="right" class="flex flex-col">
                <div class="flex h-full flex-col space-y-4 p-4">
                    <div class="flex-shrink-0">
                        <h2 class="text-lg font-bold">
                            {{ props.product.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            SKU: {{ props.product.sku }}
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        <img
                            :src="
                                props.product.image_url ||
                                '/assets/no-image.png'
                            "
                            class="h-48 w-full rounded-md object-cover"
                        />
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col space-y-2">
                        <div
                            class="flex-1 overflow-y-auto rounded border border-border p-3 text-sm"
                        >
                            {{ props.product.description || '-' }}
                        </div>
                        <div class="flex-shrink-0 space-y-1">
                            <div class="text-sm">
                                Price:
                                <span class="font-semibold">{{
                                    props.product.price ?? 0
                                }}</span>
                            </div>
                            <div class="text-sm">
                                Stock: {{ props.product.current_stock ?? 0 }}
                            </div>
                            <div class="text-sm">
                                Total Sold: {{ props.product.total_sold ?? 0 }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-shrink-0 items-center gap-2">
                        <input
                            type="number"
                            v-model.number="qty"
                            min="1"
                            class="input input-sm w-20"
                        />
                        <Button size="sm" @click.prevent="addToCart"
                            >Add to Cart</Button
                        >
                        <Button
                            size="sm"
                            variant="destructive"
                            @click.prevent="checkout"
                            >Checkout</Button
                        >
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </Card>
</template>
