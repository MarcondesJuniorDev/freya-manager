<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Sparkles, 
    TrendingUp, 
    Package, 
    AlertTriangle, 
    Users, 
    ShoppingBag,
    DollarSign,
    Plus
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface Product {
    id: number;
    name: string;
    stock_quantity: number;
    min_stock_quantity: number;
    brand: { name: string };
}

interface Sale {
    id: number;
    total_amount: number;
    payment_method: string;
    sale_date: string;
    customer: { name: string } | null;
}

interface ChartItem {
    date: string;
    total: number;
}

const props = defineProps<{
    stats: {
        stockCost: number;
        stockValue: number;
        potentialProfit: number;
        fiadoReceivables: number;
        salesToday: number;
        salesThisMonth: number;
    };
    lowStockProducts: Product[];
    recentSales: Sale[];
    chartData: ChartItem[];
}>();

// Helper to format currency
const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

// Helper for dates
const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);

    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

// Helper to format payment method
const formatPaymentMethod = (method: string) => {
    const labels: Record<string, string> = {
        cash: 'Dinheiro',
        card: 'Cartão',
        pix: 'PIX',
        fiado: 'Caderneta (Fiado)'
    };

    return labels[method] || method;
};

const getPaymentMethodBadgeVariant = (method: string) => {
    if (method === 'fiado') {
return 'destructive';
}

    if (method === 'pix') {
return 'secondary';
}

    return 'outline';
};

// SVG Chart Helpers
const maxChartValue = Math.max(...props.chartData.map(d => d.total), 100);
const chartWidth = 500;
const chartHeight = 150;
const chartPoints = props.chartData.map((d, index) => {
    const x = props.chartData.length > 1 
        ? (index / (props.chartData.length - 1)) * (chartWidth - 40) + 20 
        : chartWidth / 2;
    const y = chartHeight - ((d.total / maxChartValue) * (chartHeight - 40) + 20);

    return { x, y, label: d.date, value: d.total };
});

const linePath = chartPoints.length > 0 
    ? `M ${chartPoints[0].x} ${chartPoints[0].y} ` + chartPoints.slice(1).map(p => `L ${p.x} ${p.y}`).join(' ') 
    : '';

const areaPath = chartPoints.length > 0 
    ? `${linePath} L ${chartPoints[chartPoints.length - 1].x} ${chartHeight - 10} L ${chartPoints[0].x} ${chartHeight - 10} Z` 
    : '';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Painel Principal" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Welcome Banner Mobile-First -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gradient-to-r from-rose-500/10 via-purple-500/10 to-indigo-500/10 p-6 rounded-2xl border border-rose-500/20 dark:border-rose-500/10">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    Olá, Marcondes! <Sparkles class="h-5 w-5 text-rose-500 animate-pulse" />
                </h1>
                <p class="text-sm text-muted-foreground mt-1">Aqui está o resumo das suas vendas e controle de estoque de hoje.</p>
            </div>
            <Button as-child class="w-full sm:w-auto bg-rose-600 hover:bg-rose-500 text-white font-medium shadow-lg shadow-rose-600/20 rounded-xl">
                <Link href="/sales/create">
                    <Plus class="mr-2 h-4 w-4" /> Nova Venda Rápida
                </Link>
            </Button>
        </div>

        <!-- Metric Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
            <!-- Caderneta -->
            <Card class="relative overflow-hidden border-amber-500/20 dark:border-amber-500/10 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        A Receber
                        <Users class="h-3.5 w-3.5 text-amber-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">
                        {{ formatCurrency(stats.fiadoReceivables) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Saldo na Caderneta</p>
                </CardContent>
            </Card>

            <!-- Sales Today -->
            <Card class="relative overflow-hidden border-rose-500/20 dark:border-rose-500/10 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        Vendas (Hoje)
                        <ShoppingBag class="h-3.5 w-3.5 text-rose-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-rose-600 dark:text-rose-400">
                        {{ formatCurrency(stats.salesToday) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Hoje até agora</p>
                </CardContent>
            </Card>

            <!-- Sales This Month -->
            <Card class="relative overflow-hidden border-emerald-500/20 dark:border-emerald-500/10 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        Vendas (Mês)
                        <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                        {{ formatCurrency(stats.salesThisMonth) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Mês atual</p>
                </CardContent>
            </Card>

            <!-- Stock Cost -->
            <Card class="relative overflow-hidden border-border/40 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        Estoque (Custo)
                        <DollarSign class="h-3.5 w-3.5 text-muted-foreground" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-foreground">
                        {{ formatCurrency(stats.stockCost) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Preço pago em compras</p>
                </CardContent>
            </Card>

            <!-- Stock Value -->
            <Card class="relative overflow-hidden border-border/40 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        Estoque (Revista)
                        <Package class="h-3.5 w-3.5 text-muted-foreground" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-foreground">
                        {{ formatCurrency(stats.stockValue) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Preço sugerido catálogo</p>
                </CardContent>
            </Card>

            <!-- Potential Profit -->
            <Card class="relative overflow-hidden border-indigo-500/20 dark:border-indigo-500/10 shadow-sm rounded-xl">
                <CardHeader class="p-4 pb-2">
                    <CardTitle class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        Lucro Potencial
                        <Sparkles class="h-3.5 w-3.5 text-indigo-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                        {{ formatCurrency(stats.potentialProfit) }}
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">Diferença Custo/Venda</p>
                </CardContent>
            </Card>
        </div>

        <!-- Graph Section -->
        <Card class="rounded-xl shadow-sm border border-border/40 overflow-hidden">
            <CardHeader class="p-5 pb-0">
                <CardTitle class="text-base font-semibold">Evolução de Vendas</CardTitle>
                <CardDescription class="text-xs">Últimos 7 dias de faturamento</CardDescription>
            </CardHeader>
            <CardContent class="p-4 pt-4">
                <div class="w-full h-[160px] relative">
                    <!-- Placeholder graphic if no data -->
                    <div v-if="chartData.length === 0" class="absolute inset-0 flex items-center justify-center text-xs text-muted-foreground">
                        Sem vendas registradas nos últimos dias.
                    </div>
                    <!-- SVG Chart -->
                    <svg v-else :viewBox="`0 0 ${chartWidth} ${chartHeight}`" width="100%" height="100%" class="overflow-visible">
                        <!-- Gradients -->
                        <defs>
                            <linearGradient id="chart-area-grad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgb(244, 63, 94)" stop-opacity="0.2" />
                                <stop offset="100%" stop-color="rgb(244, 63, 94)" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>
                        <!-- Area -->
                        <path :d="areaPath" fill="url(#chart-area-grad)" />
                        <!-- Line -->
                        <path :d="linePath" fill="none" stroke="rgb(244, 63, 94)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Grid Lines -->
                        <line x1="20" :y1="chartHeight - 10" :x2="chartWidth - 20" :y2="chartHeight - 10" stroke="currentColor" class="text-border" stroke-width="1" />
                        <!-- Circles & Tooltips -->
                        <g v-for="(p, idx) in chartPoints" :key="idx">
                            <circle :cx="p.x" :cy="p.y" r="4.5" fill="rgb(244, 63, 94)" stroke="white" stroke-width="1.5" class="shadow-sm" />
                            <!-- Date labels -->
                            <text :x="p.x" :y="chartHeight + 10" text-anchor="middle" font-size="9" class="fill-muted-foreground font-medium">
                                {{ p.label }}
                            </text>
                            <!-- Value labels on hover/top -->
                            <text :x="p.x" :y="p.y - 8" text-anchor="middle" font-size="8" class="fill-foreground font-bold">
                                {{ Math.round(p.value) }}
                            </text>
                        </g>
                    </svg>
                </div>
            </CardContent>
        </Card>

        <!-- Low Stock and Recent Sales split view -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Low Stock Warnings -->
            <Card class="rounded-xl shadow-sm border border-border/40">
                <CardHeader class="p-5 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-semibold flex items-center gap-2">
                            Atenção ao Estoque <AlertTriangle class="h-4 w-4 text-amber-500 animate-bounce" />
                        </CardTitle>
                        <CardDescription class="text-xs">Produtos com baixo estoque ou esgotados</CardDescription>
                    </div>
                    <Button as-child variant="ghost" size="sm" class="text-xs h-8 text-rose-600 hover:text-rose-500">
                        <Link href="/products?stock_filter=low">
                            Ver Todos
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-5 pt-0">
                    <div v-if="lowStockProducts.length === 0" class="py-6 text-center text-xs text-muted-foreground">
                        Excelente! Nenhum produto com estoque baixo.
                    </div>
                    <div v-else class="divide-y divide-border/40">
                        <div v-for="prod in lowStockProducts" :key="prod.id" class="flex justify-between items-center py-2.5 first:pt-0 last:pb-0">
                            <div>
                                <p class="text-xs font-semibold text-foreground line-clamp-1">{{ prod.name }}</p>
                                <span class="text-[10px] text-muted-foreground font-medium">{{ prod.brand.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="prod.stock_quantity === 0 ? 'destructive' : 'warning'" class="text-[10px] px-1.5 py-0.5 rounded-md font-bold">
                                    {{ prod.stock_quantity === 0 ? 'Esgotado' : `${prod.stock_quantity} un` }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Sales -->
            <Card class="rounded-xl shadow-sm border border-border/40">
                <CardHeader class="p-5 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-semibold">Vendas Recentes</CardTitle>
                        <CardDescription class="text-xs">Últimos pedidos registrados</CardDescription>
                    </div>
                    <Button as-child variant="ghost" size="sm" class="text-xs h-8 text-rose-600 hover:text-rose-500">
                        <Link href="/sales">
                            Ver Todas
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-5 pt-0">
                    <div v-if="recentSales.length === 0" class="py-6 text-center text-xs text-muted-foreground">
                        Nenhuma venda registrada ainda.
                    </div>
                    <div v-else class="divide-y divide-border/40">
                        <div v-for="sale in recentSales" :key="sale.id" class="flex justify-between items-center py-2.5 first:pt-0 last:pb-0">
                            <div class="flex flex-col">
                                <p class="text-xs font-semibold text-foreground line-clamp-1">
                                    {{ sale.customer ? sale.customer.name : 'Venda Rápida (Avulso)' }}
                                </p>
                                <span class="text-[10px] text-muted-foreground font-medium">
                                    {{ formatDate(sale.sale_date) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="getPaymentMethodBadgeVariant(sale.payment_method)" class="text-[9px] px-1.5 py-0.5 rounded-md font-bold uppercase">
                                    {{ formatPaymentMethod(sale.payment_method) }}
                                </Badge>
                                <span class="text-xs font-bold text-foreground min-w-[70px] text-right">
                                    {{ formatCurrency(sale.total_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
