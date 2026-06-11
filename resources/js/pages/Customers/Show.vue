<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    ChevronLeft, 
    Phone, 
    Mail, 
    MapPin, 
    DollarSign, 
    Plus, 
    Minus, 
    Calendar,
    Notebook,
    FileText,
    ExternalLink
} from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Sale {
    id: number;
    sale_date: string;
    total_amount: number;
    payment_method: string;
    payment_status: string;
}

interface Transaction {
    id: number;
    sale_id: number | null;
    type: 'debit' | 'credit';
    amount: number;
    transaction_date: string;
    description: string | null;
}

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    balance: number;
    max_credit_limit: number;
    notes: string | null;
    sales: Sale[];
    transactions: Transaction[];
}

const props = defineProps<{
    customer: Customer;
}>();

const activeTab = ref<'ledger' | 'sales'>('ledger');
const isTxModalOpen = ref(false);
const txType = ref<'debit' | 'credit'>('credit'); // credit = payment by default

const txForm = useForm({
    type: 'credit',
    amount: '',
    description: '',
});

const openTxModal = (type: 'debit' | 'credit') => {
    txType.value = type;
    txForm.type = type;
    txForm.amount = '';
    txForm.description = type === 'credit' ? 'Pagamento parcial via PIX' : 'Ajuste de débito manual';
    txForm.clearErrors();
    isTxModalOpen.value = true;
};

const submitTransaction = () => {
    txForm.post(`/customers/${props.customer.id}/transactions`, {
        onSuccess: () => {
            isTxModalOpen.value = false;
            txForm.reset();
        },
    });
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

// WhatsApp link generator (Mobile-First helper)
const getWhatsAppLink = (phone: string | null) => {
    if (!phone) return '#';
    const cleanNumber = phone.replace(/\D/g, '');
    const prefix = cleanNumber.startsWith('55') ? '' : '55';
    return `https://wa.me/${prefix}${cleanNumber}`;
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="`Caderneta - ${customer.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Back navigation & Title -->
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="icon" class="rounded-xl">
                    <Link href="/customers">
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-bold text-foreground">{{ customer.name }}</h1>
                    <p class="text-xs text-muted-foreground mt-0.5">Caderneta e histórico de transações</p>
                </div>
            </div>
            <!-- Action buttons -->
            <div class="flex gap-2 w-full sm:w-auto">
                <Button @click="openTxModal('credit')" class="bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl flex-1 sm:flex-initial">
                    <Minus class="mr-1.5 h-4 w-4" /> Registrar Pagamento
                </Button>
                <Button @click="openTxModal('debit')" variant="outline" class="border-destructive/20 text-destructive hover:bg-destructive/10 rounded-xl flex-1 sm:flex-initial">
                    <Plus class="mr-1.5 h-4 w-4" /> Lançar Débito
                </Button>
            </div>
        </div>

        <!-- Details Grid (Split Profile Info / Balances) -->
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Profile Card -->
            <Card class="rounded-xl shadow-sm border border-border/40 md:col-span-1">
                <CardHeader class="p-5">
                    <CardTitle class="text-base">Informações do Cliente</CardTitle>
                </CardHeader>
                <CardContent class="p-5 pt-0 space-y-4 text-sm">
                    <div class="space-y-3">
                        <div v-if="customer.phone" class="flex items-center gap-2.5">
                            <Phone class="h-4.5 w-4.5 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Telefone</p>
                                <a :href="getWhatsAppLink(customer.phone)" target="_blank" class="text-xs font-semibold text-rose-600 hover:underline flex items-center gap-1">
                                    {{ customer.phone }} <ExternalLink class="h-3 w-3" />
                                </a>
                            </div>
                        </div>
                        <div v-if="customer.email" class="flex items-center gap-2.5">
                            <Mail class="h-4.5 w-4.5 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">E-mail</p>
                                <span class="text-xs font-semibold">{{ customer.email }}</span>
                            </div>
                        </div>
                        <div v-if="customer.address" class="flex items-center gap-2.5">
                            <MapPin class="h-4.5 w-4.5 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Endereço</p>
                                <span class="text-xs font-semibold">{{ customer.address }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border/30 pt-3">
                        <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider mb-1">Notas / Preferências</p>
                        <p class="text-xs text-muted-foreground bg-muted/30 p-2.5 rounded-lg border">
                            {{ customer.notes || 'Nenhuma nota especial adicionada.' }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Balance Dashboard Cards -->
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Outstanding Balance Card -->
                <Card class="rounded-xl shadow-sm border border-border/40 overflow-hidden flex flex-col justify-between" :class="{'bg-rose-500/[0.01] border-rose-500/20': customer.balance > 0}">
                    <CardHeader class="p-5 pb-2">
                        <CardTitle class="text-xs uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                            Saldo Devedor Atual
                            <DollarSign class="h-4.5 w-4.5 text-rose-500" />
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-5 pt-0">
                        <h2 class="text-3xl font-extrabold" :class="{'text-rose-600 dark:text-rose-400': customer.balance > 0, 'text-foreground': customer.balance <= 0}">
                            {{ formatCurrency(customer.balance) }}
                        </h2>
                        <p class="text-[11px] text-muted-foreground mt-2">
                            {{ customer.balance > 0 ? 'Este valor deve ser recebido do cliente.' : 'Cliente não possui débitos em aberto.' }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Remaining Credit Limit Card -->
                <Card class="rounded-xl shadow-sm border border-border/40 overflow-hidden flex flex-col justify-between">
                    <CardHeader class="p-5 pb-2">
                        <CardTitle class="text-xs uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                            Limite de Crédito
                            <Notebook class="h-4.5 w-4.5 text-emerald-500" />
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-5 pt-0">
                        <h2 class="text-3xl font-extrabold text-foreground">
                            {{ formatCurrency(Math.max(0, customer.max_credit_limit - customer.balance)) }}
                        </h2>
                        <div class="flex justify-between items-center text-[11px] text-muted-foreground mt-2">
                            <span>Limite Total: {{ formatCurrency(customer.max_credit_limit) }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Ledger History tabs -->
        <Card class="rounded-xl shadow-sm border border-border/40 overflow-hidden">
            <div class="flex border-b border-border/40 bg-muted/20">
                <button 
                    @click="activeTab = 'ledger'" 
                    class="px-5 py-3.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5"
                    :class="activeTab === 'ledger' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <Notebook class="h-4 w-4" /> Extrato da Caderneta ({{ customer.transactions.length }})
                </button>
                <button 
                    @click="activeTab = 'sales'" 
                    class="px-5 py-3.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5"
                    :class="activeTab === 'sales' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                >
                    <FileText class="h-4 w-4" /> Histórico de Compras ({{ customer.sales.length }})
                </button>
            </div>

            <!-- Tab 1: Ledger -->
            <div v-if="activeTab === 'ledger'" class="p-5">
                <div v-if="customer.transactions.length === 0" class="py-12 text-center text-xs text-muted-foreground">
                    Nenhuma transação financeira registrada nesta caderneta.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-border/40 text-[10px] uppercase font-bold text-muted-foreground tracking-wider">
                                <th class="pb-3">Data</th>
                                <th class="pb-3">Descrição</th>
                                <th class="pb-3 text-right">Lançamento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/30">
                            <tr v-for="tx in customer.transactions" :key="tx.id" class="hover:bg-muted/10">
                                <td class="py-3 font-medium text-muted-foreground">{{ formatDate(tx.transaction_date) }}</td>
                                <td class="py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-foreground">{{ tx.description }}</span>
                                        <Link v-if="tx.sale_id" :href="`/sales/${tx.sale_id}`" class="text-[10px] text-rose-600 hover:underline font-bold mt-0.5">
                                            Ver Venda #{{ tx.sale_id }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="py-3 text-right font-bold" :class="tx.type === 'debit' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                    {{ tx.type === 'debit' ? '+' : '-' }} {{ formatCurrency(tx.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Sales -->
            <div v-if="activeTab === 'sales'" class="p-5">
                <div v-if="customer.sales.length === 0" class="py-12 text-center text-xs text-muted-foreground">
                    Nenhuma compra registrada para este cliente.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-border/40 text-[10px] uppercase font-bold text-muted-foreground tracking-wider">
                                <th class="pb-3">Data</th>
                                <th class="pb-3">ID Venda</th>
                                <th class="pb-3">Pagamento</th>
                                <th class="pb-3 text-right">Total</th>
                                <th class="pb-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/30">
                            <tr v-for="sale in customer.sales" :key="sale.id" class="hover:bg-muted/10">
                                <td class="py-3 text-muted-foreground">{{ formatDate(sale.sale_date) }}</td>
                                <td class="py-3 font-semibold">#{{ sale.id }}</td>
                                <td class="py-3">
                                    <div class="flex gap-1">
                                        <Badge variant="outline" class="text-[9px] font-bold uppercase">{{ sale.payment_method }}</Badge>
                                        <Badge :variant="sale.payment_status === 'paid' ? 'secondary' : 'destructive'" class="text-[9px] font-bold uppercase">
                                            {{ sale.payment_status === 'paid' ? 'Pago' : 'Pendente' }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="py-3 text-right font-bold">{{ formatCurrency(sale.total_amount) }}</td>
                                <td class="py-3 text-right">
                                    <Button as-child variant="ghost" size="sm" class="text-xs h-7 text-rose-600 hover:text-rose-500 font-semibold px-2">
                                        <Link :href="`/sales/${sale.id}`">Detalhes</Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Card>

        <!-- Transaction Dialog (Debit/Credit) -->
        <Dialog :open="isTxModalOpen" @update:open="isTxModalOpen = $event">
            <DialogContent class="sm:max-w-[400px] rounded-2xl">
                <form @submit.prevent="submitTransaction">
                    <DialogHeader>
                        <DialogTitle>{{ txType === 'credit' ? 'Registrar Pagamento' : 'Lançar Débito Manual' }}</DialogTitle>
                        <DialogDescription>
                            {{ txType === 'credit' ? 'Informe o valor pago pelo cliente para abater da caderneta.' : 'Lance uma cobrança ou débito manual diretamente na conta.' }}
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-1.5">
                            <Label for="amount">Valor (R$)</Label>
                            <Input id="amount" type="number" step="0.01" v-model="txForm.amount" required placeholder="0.00" class="rounded-xl" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="description">Descrição</Label>
                            <Input id="description" v-model="txForm.description" required class="rounded-xl" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isTxModalOpen = false" class="rounded-xl">Cancelar</Button>
                        <Button type="submit" :disabled="txForm.processing" class="rounded-xl" :class="txType === 'credit' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'">
                            Confirmar Lançamento
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
