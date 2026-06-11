<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, ChevronRight, Plus, Calendar } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Customer {
    name: string;
}

interface Sale {
    id: number;
    sale_date: string;
    subtotal_amount: number;
    discount_amount: number;
    total_amount: number;
    payment_method: string;
    payment_status: string;
    customer: Customer | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedSales {
    data: Sale[];
    links: PaginationLink[];
}

const props = defineProps<{
    sales: PaginatedSales;
}>();

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
    if (method === 'fiado') return 'destructive';
    if (method === 'pix') return 'secondary';
    return 'outline';
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Histórico de Vendas" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    <ShoppingBag class="h-6 w-6 text-rose-500" /> Histórico de Vendas
                </h1>
                <p class="text-sm text-muted-foreground mt-1">Veja todas as vendas efetuadas e formas de pagamento.</p>
            </div>
            <Button as-child class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">
                <Link href="/sales/create">
                    <Plus class="mr-2 h-4 w-4" /> Nova Venda
                </Link>
            </Button>
        </div>

        <!-- Sales List -->
        <div v-if="sales.data.length === 0" class="flex flex-col items-center justify-center p-12 border border-dashed rounded-2xl bg-muted/20">
            <ShoppingBag class="h-12 w-12 text-muted-foreground stroke-1 mb-4" />
            <h3 class="text-base font-semibold">Nenhuma venda realizada</h3>
            <p class="text-sm text-muted-foreground text-center max-w-xs mt-1">Comece registrando uma nova venda rápida.</p>
            <Button as-child class="mt-4 bg-rose-600 hover:bg-rose-500 text-white rounded-xl">
                <Link href="/sales/create">Registrar Primeira Venda</Link>
            </Button>
        </div>

        <div v-else class="space-y-3">
            <Card v-for="sale in sales.data" :key="sale.id" class="rounded-xl shadow-sm border border-border/40 hover:border-rose-500/10 transition-all">
                <CardContent class="p-4 flex justify-between items-center text-xs">
                    <div class="flex flex-col gap-1 max-w-[65%]">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-foreground">Venda #{{ sale.id }}</span>
                            <span class="text-muted-foreground font-medium flex items-center gap-1"><Calendar class="h-3 w-3" /> {{ formatDate(sale.sale_date) }}</span>
                        </div>
                        <p class="text-muted-foreground font-medium truncate">
                            Cliente: <span class="text-foreground font-semibold">{{ sale.customer ? sale.customer.name : 'Venda Rápida (Avulso)' }}</span>
                        </p>
                        <div class="flex gap-1.5 mt-1">
                            <Badge :variant="getMethodBadgeVariant(sale.payment_method)" class="text-[9px] font-bold uppercase py-0 px-1.5 rounded-md">
                                {{ formatPaymentMethod(sale.payment_method) }}
                            </Badge>
                            <Badge :variant="sale.payment_status === 'paid' ? 'secondary' : 'destructive'" class="text-[9px] font-bold uppercase py-0 px-1.5 rounded-md">
                                {{ sale.payment_status === 'paid' ? 'Pago' : 'Pendente' }}
                            </Badge>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-semibold">Total</p>
                            <p class="text-base font-extrabold text-foreground">{{ formatCurrency(sale.total_amount) }}</p>
                        </div>
                        <Button as-child variant="ghost" size="icon" class="rounded-xl h-8 w-8 shrink-0 hover:bg-muted/80">
                            <Link :href="`/sales/${sale.id}`">
                                <ChevronRight class="h-4.5 w-4.5 text-muted-foreground" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Pagination -->
        <div v-if="sales.links && sales.links.length > 3" class="flex justify-center items-center gap-2 mt-4">
            <template v-for="link in sales.links" :key="link.label">
                <Button v-if="link.url" as-child :variant="link.active ? 'default' : 'outline'" size="sm" class="rounded-xl min-w-[36px]" :class="{'bg-rose-600 hover:bg-rose-500 text-white': link.active}">
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-muted-foreground" />
            </template>
        </div>
    </div>
</template>
