<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Calendar, User, CreditCard, Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface Product {
    id: number;
    name: string;
    brand: { name: string };
}

interface SaleItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    unit_cost: number;
    subtotal: number;
    product: Product;
}

interface Customer {
    id: number;
    name: string;
}

interface Sale {
    id: number;
    sale_date: string;
    subtotal_amount: number;
    discount_amount: number;
    total_amount: number;
    total_cost: number;
    payment_method: string;
    payment_status: string;
    notes: string | null;
    customer: Customer | null;
    items: SaleItem[];
}

const props = defineProps<{
    sale: Sale;
}>();

const showProfitData = ref(false); // Security ACL toggle

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);

    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const formatPaymentMethod = (method: string) => {
    const labels: Record<string, string> = {
        cash: 'Dinheiro',
        card: 'Cartão',
        pix: 'PIX',
        fiado: 'Caderneta (Fiado)'
    };

    return labels[method] || method;
};

const getMethodBadgeVariant = (method: string) => {
    if (method === 'fiado') {
return 'destructive';
}

    if (method === 'pix') {
return 'secondary';
}

    return 'outline';
};

const totalCost = Number(props.sale.total_cost);
const totalAmount = Number(props.sale.total_amount);
const profit = totalAmount - totalCost;
const profitMargin = totalAmount > 0 ? ((profit / totalAmount) * 100).toFixed(2) : '0';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="`Venda #${sale.id}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
        <!-- Back navigation -->
        <div class="flex items-center gap-2">
            <Button as-child variant="ghost" size="icon" class="rounded-xl">
                <Link href="/sales">
                    <ChevronLeft class="h-5 w-5" />
                </Link>
            </Button>
            <div>
                <h1 class="text-xl font-bold text-foreground">Detalhes da Venda #{{ sale.id }}</h1>
                <p class="text-xs text-muted-foreground mt-0.5">Comprovante de venda e faturamento</p>
            </div>
        </div>

        <!-- Sale Details Grid -->
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Summary Info -->
            <Card class="rounded-xl shadow-sm border border-border/40 md:col-span-2 space-y-4">
                <CardHeader class="p-5 pb-2">
                    <CardTitle class="text-base">Itens Comprados</CardTitle>
                </CardHeader>
                <CardContent class="p-5 pt-0">
                    <div class="divide-y divide-border/30">
                        <div v-for="item in sale.items" :key="item.id" class="flex justify-between items-center py-3 first:pt-0 last:pb-0">
                            <div>
                                <span class="text-[9px] uppercase font-bold text-rose-500 tracking-wider">
                                    {{ item.product.brand.name }}
                                </span>
                                <p class="text-xs font-bold text-foreground">{{ item.product.name }}</p>
                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                    {{ item.quantity }} un x {{ formatCurrency(item.unit_price) }}
                                </p>
                            </div>
                            <span class="text-xs font-extrabold text-foreground">
                                {{ formatCurrency(item.subtotal) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata & Customer Info -->
            <Card class="rounded-xl shadow-sm border border-border/40 h-fit">
                <CardHeader class="p-5 pb-2">
                    <CardTitle class="text-base">Informações Gerais</CardTitle>
                </CardHeader>
                <CardContent class="p-5 pt-0 space-y-4 text-xs">
                    <!-- Date -->
                    <div class="flex items-center gap-2.5">
                        <Calendar class="h-4.5 w-4.5 text-muted-foreground" />
                        <div>
                            <p class="text-[10px] text-muted-foreground font-semibold">Data da Venda</p>
                            <p class="font-bold text-foreground">{{ formatDate(sale.sale_date) }}</p>
                        </div>
                    </div>
                    <!-- Customer -->
                    <div class="flex items-center gap-2.5">
                        <User class="h-4.5 w-4.5 text-muted-foreground" />
                        <div>
                            <p class="text-[10px] text-muted-foreground font-semibold">Cliente</p>
                            <p class="font-bold text-foreground">
                                <Link v-if="sale.customer" :href="`/customers/${sale.customer.id}`" class="text-rose-600 hover:underline">
                                    {{ sale.customer.name }}
                                </Link>
                                <span v-else>Venda Rápida (Avulso)</span>
                            </p>
                        </div>
                    </div>
                    <!-- Payment -->
                    <div class="flex items-center gap-2.5">
                        <CreditCard class="h-4.5 w-4.5 text-muted-foreground" />
                        <div>
                            <p class="text-[10px] text-muted-foreground font-semibold">Pagamento</p>
                            <div class="flex gap-1.5 mt-0.5">
                                <Badge :variant="getMethodBadgeVariant(sale.payment_method)" class="text-[9px] font-bold uppercase py-0 px-1 rounded-md">
                                    {{ formatPaymentMethod(sale.payment_method) }}
                                </Badge>
                                <Badge :variant="sale.payment_status === 'paid' ? 'secondary' : 'destructive'" class="text-[9px] font-bold uppercase py-0 px-1 rounded-md">
                                    {{ sale.payment_status === 'paid' ? 'Pago' : 'Pendente' }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Receipt Total Breakdown -->
        <Card class="rounded-xl shadow-sm border border-border/40">
            <CardContent class="p-5 flex flex-col sm:flex-row justify-between gap-6 text-xs">
                <!-- Notes block -->
                <div class="flex-1">
                    <p class="font-semibold text-muted-foreground mb-1 uppercase tracking-wider text-[10px]">Observações</p>
                    <p class="text-muted-foreground bg-muted/40 border p-3 rounded-lg min-h-[44px]">
                        {{ sale.notes || 'Nenhuma observação cadastrada.' }}
                    </p>
                </div>

                <!-- Financial calculation grid -->
                <div class="w-full sm:w-[250px] space-y-2.5">
                    <div class="flex justify-between font-medium">
                        <span class="text-muted-foreground">Subtotal:</span>
                        <span class="text-foreground">{{ formatCurrency(sale.subtotal_amount) }}</span>
                    </div>
                    <div class="flex justify-between font-medium text-destructive">
                        <span>Desconto:</span>
                        <span>-{{ formatCurrency(sale.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-border/30 pt-2 text-sm font-extrabold text-foreground">
                        <span>Total Líquido:</span>
                        <span>{{ formatCurrency(sale.total_amount) }}</span>
                    </div>

                    <!-- Sensitive profits area (secured with password toggle/state toggle) -->
                    <div class="border-t border-dashed border-border/60 pt-3 mt-1.5">
                        <Button @click="showProfitData = !showProfitData" variant="ghost" size="sm" class="text-[10px] p-0 h-6 text-rose-600 hover:text-rose-500 font-bold uppercase tracking-wider">
                            <component :is="showProfitData ? EyeOff : Eye" class="mr-1 h-3.5 w-3.5" />
                            {{ showProfitData ? 'Ocultar Relatório de Lucro' : 'Exibir Relatório de Lucro' }}
                        </Button>
                        <div v-if="showProfitData" class="mt-3 space-y-2 bg-rose-500/5 dark:bg-rose-500/[0.01] p-3 rounded-xl border border-rose-500/10">
                            <div class="flex justify-between">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground">Preço de Custo total:</span>
                                <span class="font-semibold">{{ formatCurrency(totalCost) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[10px] uppercase font-bold text-rose-500">Lucro Líquido:</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ formatCurrency(profit) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[10px] uppercase font-bold text-rose-500">Margem de Lucro:</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ profitMargin }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
