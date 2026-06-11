<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { Plus, Edit2, Trash2, Users, Search, AlertCircle, Phone, Mail, BookOpen, MapPin } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
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

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    balance: number;
    max_credit_limit: number;
    notes: string | null;
}

const props = defineProps<{
    customers: Customer[];
    filters: {
        search?: string;
        balance_filter?: string;
    };
}>();

// Filter states
const search = ref(props.filters.search || '');
const balanceFilter = ref(props.filters.balance_filter || '');

// Debounce filtering
const applyFilters = useDebounceFn(() => {
    router.get('/customers', {
        search: search.value || undefined,
        balance_filter: balanceFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 350);

watch([search, balanceFilter], () => {
    applyFilters();
});

// CRUD states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const currentCustomer = ref<Customer | null>(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    max_credit_limit: 500.00,
    notes: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isCreateOpen.value = true;
};

const submitCreate = () => {
    form.post('/customers', {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
};

const openEditModal = (customer: Customer) => {
    currentCustomer.value = customer;
    form.name = customer.name;
    form.phone = customer.phone || '';
    form.email = customer.email || '';
    form.address = customer.address || '';
    form.max_credit_limit = Number(customer.max_credit_limit);
    form.notes = customer.notes || '';
    form.clearErrors();
    isEditOpen.value = true;
};

const submitEdit = () => {
    if (!currentCustomer.value) return;
    form.put(`/customers/${currentCustomer.value.id}`, {
        onSuccess: () => {
            isEditOpen.value = false;
            currentCustomer.value = null;
            form.reset();
        },
    });
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Gerenciamento de Clientes" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    <Users class="h-6 w-6 text-rose-500" /> Clientes
                </h1>
                <p class="text-sm text-muted-foreground mt-1">Controle de clientes e saldos de caderneta (fiado).</p>
            </div>
            <Button @click="openCreateModal" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">
                <Plus class="mr-2 h-4 w-4" /> Novo Cliente
            </Button>
        </div>

        <!-- Filters Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-muted/20 p-4 rounded-2xl border">
            <!-- Search field -->
            <div class="relative">
                <Search class="absolute left-3 top-2.5 h-4.5 w-4.5 text-muted-foreground" />
                <Input v-model="search" placeholder="Buscar por Nome, Celular..." class="pl-10 rounded-xl" />
            </div>
            <!-- Balance Filter -->
            <select v-model="balanceFilter" class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <option value="">Todos os Clientes</option>
                <option value="debt">Clientes com Saldo Devedor</option>
                <option value="clean">Clientes sem Dívidas</option>
            </select>
        </div>

        <!-- Customers Grid -->
        <div v-if="customers.length === 0" class="flex flex-col items-center justify-center p-12 border border-dashed rounded-2xl bg-muted/20">
            <Users class="h-12 w-12 text-muted-foreground stroke-1 mb-4" />
            <h3 class="text-base font-semibold">Nenhum cliente encontrado</h3>
            <p class="text-sm text-muted-foreground text-center max-w-xs mt-1">Refine sua busca ou cadastre um novo cliente.</p>
            <Button @click="openCreateModal" variant="outline" class="mt-4 border-rose-500/30 text-rose-600 hover:text-rose-500">
                Cadastrar Primeiro Cliente
            </Button>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="customer in customers" :key="customer.id" class="rounded-xl shadow-sm border border-border/40 flex flex-col justify-between" :class="{'border-rose-500/20 bg-rose-500/[0.01]': customer.balance > 0}">
                <CardHeader class="p-5 pb-3">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <CardTitle class="text-base font-bold text-foreground">{{ customer.name }}</CardTitle>
                            <span v-if="customer.phone" class="text-xs text-muted-foreground flex items-center gap-1.5 mt-1.5"><Phone class="h-3.5 w-3.5" /> {{ customer.phone }}</span>
                        </div>
                        <Badge :variant="customer.balance > 0 ? 'destructive' : 'secondary'" class="text-[10px] px-1.5 py-0.5 rounded-md font-bold shrink-0">
                            {{ customer.balance > 0 ? `Deve ${formatCurrency(customer.balance)}` : 'Em Dia' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-5 pt-0">
                    <!-- Caderneta Quick info -->
                    <div class="bg-muted/40 p-3 rounded-xl mb-4 text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Limite Caderneta:</span>
                            <span class="font-bold">{{ formatCurrency(customer.max_credit_limit) }}</span>
                        </div>
                        <div class="flex justify-between" v-if="customer.balance > 0">
                            <span class="text-muted-foreground">Limite Disponível:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(Math.max(0, customer.max_credit_limit - customer.balance)) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center border-t border-border/30 pt-3 gap-2">
                        <Button as-child variant="outline" size="sm" class="text-xs h-8 text-rose-600 hover:text-rose-500 border-rose-500/20 hover:border-rose-500/30 rounded-lg flex-1">
                            <Link :href="`/customers/${customer.id}`">
                                <BookOpen class="mr-1.5 h-3.5 w-3.5" /> Ver Caderneta
                            </Link>
                        </Button>
                        <div class="flex gap-1">
                            <Button @click="openEditModal(customer)" variant="ghost" size="sm" class="text-xs h-8 text-foreground hover:bg-muted/80 px-2.5">
                                <Edit2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-[450px] rounded-2xl">
                <form @submit.prevent="submitCreate">
                    <DialogHeader>
                        <DialogTitle>Novo Cliente</DialogTitle>
                        <DialogDescription>Cadastre as informações básicas e defina o limite de compras fiado do cliente.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-1.5">
                            <Label for="name">Nome Completo</Label>
                            <Input id="name" v-model="form.name" required placeholder="Ex: Maria de Souza" class="rounded-xl" />
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="phone">Celular/WhatsApp</Label>
                                <Input id="phone" v-model="form.phone" placeholder="Ex: (11) 99999-9999" class="rounded-xl" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="email">E-mail</Label>
                                <Input id="email" type="email" v-model="form.email" placeholder="Opcional" class="rounded-xl" />
                            </div>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="address">Endereço de Entrega</Label>
                            <Input id="address" v-model="form.address" placeholder="Ex: Rua A, 123" class="rounded-xl" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="max_credit_limit">Limite na Caderneta (R$)</Label>
                            <Input id="max_credit_limit" type="number" step="0.01" v-model="form.max_credit_limit" required class="rounded-xl" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="notes">Notas / Preferências</Label>
                            <Input id="notes" v-model="form.notes" placeholder="Ex: Prefere perfume doce, pagar dia 10" class="rounded-xl" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isCreateOpen = false" class="rounded-xl">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Salvar Cliente</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-[450px] rounded-2xl">
                <form @submit.prevent="submitEdit">
                    <DialogHeader>
                        <DialogTitle>Editar Cliente</DialogTitle>
                        <DialogDescription>Modifique os dados cadastrais ou o limite do cliente selecionado.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-1.5">
                            <Label for="edit-name">Nome Completo</Label>
                            <Input id="edit-name" v-model="form.name" required class="rounded-xl" />
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="edit-phone">Celular/WhatsApp</Label>
                                <Input id="edit-phone" v-model="form.phone" class="rounded-xl" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-email">E-mail</Label>
                                <Input id="edit-email" type="email" v-model="form.email" class="rounded-xl" />
                            </div>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="edit-address">Endereço de Entrega</Label>
                            <Input id="edit-address" v-model="form.address" class="rounded-xl" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="edit-max_credit_limit">Limite na Caderneta (R$)</Label>
                            <Input id="edit-max_credit_limit" type="number" step="0.01" v-model="form.max_credit_limit" required class="rounded-xl" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="edit-notes">Notas / Preferências</Label>
                            <Input id="edit-notes" v-model="form.notes" class="rounded-xl" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditOpen = false" class="rounded-xl">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Salvar Alterações</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
