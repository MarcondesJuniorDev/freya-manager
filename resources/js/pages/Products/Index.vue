<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { Plus, Edit2, Trash2, Package, Search, Eye, EyeOff, Barcode } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

interface Brand {
    id: number;
    name: string;
}

interface Product {
    id: number;
    brand_id: number;
    name: string;
    description: string | null;
    ean: string | null;
    sku: string | null;
    cost_price: number;
    catalog_price: number;
    sale_price: number;
    stock_quantity: number;
    min_stock_quantity: number;
    is_active: boolean;
    brand: Brand;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProducts {
    data: Product[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    products: PaginatedProducts;
    brands: Brand[];
    filters: {
        search?: string;
        brand_id?: string;
        stock_filter?: string;
    };
}>();

// Filter states
const search = ref(props.filters.search || '');
const brandId = ref(props.filters.brand_id || '');
const stockFilter = ref(props.filters.stock_filter || '');

// Debounce filtering
const applyFilters = useDebounceFn(() => {
    router.get('/products', {
        search: search.value || undefined,
        brand_id: brandId.value || undefined,
        stock_filter: stockFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 350);

watch([search, brandId, stockFilter], () => {
    applyFilters();
});

// CRUD states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const currentProduct = ref<Product | null>(null);
const showSensitiveData = ref(false); // Profit/cost security ACL check

const form = useForm({
    brand_id: '',
    name: '',
    description: '',
    ean: '',
    sku: '',
    cost_price: 0,
    catalog_price: 0,
    sale_price: 0,
    stock_quantity: 0,
    min_stock_quantity: 0,
    is_active: true,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();

    if (props.brands.length > 0) {
        form.brand_id = props.brands[0].id.toString();
    }

    isCreateOpen.value = true;
};

const submitCreate = () => {
    form.post('/products', {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
};

const openEditModal = (product: Product) => {
    currentProduct.value = product;
    form.brand_id = product.brand_id.toString();
    form.name = product.name;
    form.description = product.description || '';
    form.ean = product.ean || '';
    form.sku = product.sku || '';
    form.cost_price = Number(product.cost_price);
    form.catalog_price = Number(product.catalog_price);
    form.sale_price = Number(product.sale_price);
    form.stock_quantity = product.stock_quantity;
    form.min_stock_quantity = product.min_stock_quantity;
    form.is_active = product.is_active;
    form.clearErrors();
    isEditOpen.value = true;
};

const submitEdit = () => {
    if (!currentProduct.value) {
return;
}

    form.put(`/products/${currentProduct.value.id}`, {
        onSuccess: () => {
            isEditOpen.value = false;
            currentProduct.value = null;
            form.reset();
        },
    });
};

const deleteForm = useForm({});
const deleteProduct = (product: Product) => {
    if (confirm(`Tem certeza que deseja excluir o produto "${product.name}"?`)) {
        deleteForm.delete(`/products/${product.id}`);
    }
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Estoque de Produtos" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    <Package class="h-6 w-6 text-rose-500" /> Produtos e Estoque
                </h1>
                <p class="text-sm text-muted-foreground mt-1">Gerencie seu catálogo de cosméticos e níveis de estoque.</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <Button @click="showSensitiveData = !showSensitiveData" variant="outline" class="rounded-xl flex-1 sm:flex-initial">
                    <span class="flex items-center gap-2">
                        <component :is="showSensitiveData ? EyeOff : Eye" class="h-4 w-4" />
                        {{ showSensitiveData ? 'Ocultar Custos' : 'Ver Lucro/Custos' }}
                    </span>
                </Button>
                <Button @click="openCreateModal" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl flex-1 sm:flex-initial">
                    <Plus class="mr-2 h-4 w-4" /> Novo Produto
                </Button>
            </div>
        </div>

        <!-- Filters Bar (Mobile First layout) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-muted/20 p-4 rounded-2xl border">
            <!-- Search field -->
            <div class="relative">
                <Search class="absolute left-3 top-2.5 h-4.5 w-4.5 text-muted-foreground" />
                <Input v-model="search" placeholder="Buscar por Nome ou Código (EAN)..." class="pl-10 rounded-xl" />
            </div>
            <!-- Brand Filter -->
            <select v-model="brandId" class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <option value="">Todas as Marcas</option>
                <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <!-- Stock Filter -->
            <select v-model="stockFilter" class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <option value="">Filtro de Estoque</option>
                <option value="low">Atenção/Baixo Estoque</option>
                <option value="out">Esgotados</option>
            </select>
        </div>

        <!-- Products List -->
        <div v-if="products.data.length === 0" class="flex flex-col items-center justify-center p-12 border border-dashed rounded-2xl bg-muted/20">
            <Package class="h-12 w-12 text-muted-foreground stroke-1 mb-4" />
            <h3 class="text-base font-semibold">Nenhum produto encontrado</h3>
            <p class="text-sm text-muted-foreground text-center max-w-xs mt-1">Refine seus filtros de busca ou crie um novo produto.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="product in products.data" :key="product.id" class="rounded-xl shadow-sm border border-border/40 flex flex-col justify-between" :class="{'border-amber-500/20 bg-amber-500/[0.02]': product.stock_quantity <= product.min_stock_quantity && product.stock_quantity > 0, 'border-destructive/20 bg-destructive/[0.02]': product.stock_quantity === 0}">
                <CardHeader class="p-4 pb-2">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-rose-500 tracking-wider">{{ product.brand.name }}</span>
                            <h3 class="text-sm font-bold text-foreground line-clamp-2 mt-0.5">{{ product.name }}</h3>
                        </div>
                        <Badge :variant="product.stock_quantity === 0 ? 'destructive' : (product.stock_quantity <= product.min_stock_quantity ? 'warning' : 'secondary')" class="text-[9px] px-1.5 py-0.5 rounded-md font-bold shrink-0">
                            {{ product.stock_quantity === 0 ? 'Esgotado' : `${product.stock_quantity} un` }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <!-- Pricing Details -->
                    <div class="grid grid-cols-3 gap-2 bg-muted/40 p-2.5 rounded-xl text-center my-3">
                        <div v-if="showSensitiveData">
                            <p class="text-[9px] uppercase tracking-wider text-muted-foreground font-semibold">Custo</p>
                            <p class="text-xs font-bold text-muted-foreground">{{ formatCurrency(product.cost_price) }}</p>
                        </div>
                        <div :class="{'col-span-2': !showSensitiveData}">
                            <p class="text-[9px] uppercase tracking-wider text-muted-foreground font-semibold">Revista</p>
                            <p class="text-xs font-bold text-foreground">{{ formatCurrency(product.catalog_price) }}</p>
                        </div>
                        <div v-if="showSensitiveData">
                            <p class="text-[9px] uppercase tracking-wider text-rose-500 font-semibold">Lucro</p>
                            <p class="text-xs font-bold text-rose-600 dark:text-rose-400">
                                {{ formatCurrency(product.sale_price - product.cost_price) }}
                            </p>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-1 text-[11px] text-muted-foreground border-b border-border/30 pb-3 mb-3">
                        <p class="flex items-center gap-1"><Barcode class="h-3.5 w-3.5" /> EAN: {{ product.ean || 'Não informado' }}</p>
                        <p>SKU: {{ product.sku || 'Não informado' }}</p>
                    </div>

                    <div class="flex justify-end gap-1.5">
                        <Button @click="openEditModal(product)" variant="ghost" size="sm" class="text-xs h-8 text-foreground hover:bg-muted/80">
                            <Edit2 class="mr-1 h-3 w-3" /> Editar
                        </Button>
                        <Button @click="deleteProduct(product)" variant="ghost" size="sm" class="text-xs h-8 text-destructive hover:bg-destructive/10">
                            <Trash2 class="mr-1 h-3 w-3" /> Excluir
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Pagination -->
        <div v-if="products.last_page > 1" class="flex justify-center items-center gap-2 mt-4">
            <template v-for="link in products.links" :key="link.label">
                <Button v-if="link.url" as-child :variant="link.active ? 'default' : 'outline'" size="sm" class="rounded-xl min-w-[36px]" :class="{'bg-rose-600 hover:bg-rose-500 text-white': link.active}">
                    <Link :href="link.url"><span v-html="link.label"></span></Link>
                </Button>
                <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-muted-foreground" />
            </template>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-[500px] rounded-2xl max-h-[90vh] overflow-y-auto">
                <form @submit.prevent="submitCreate">
                    <DialogHeader>
                        <DialogTitle>Novo Produto</DialogTitle>
                        <DialogDescription>Cadastre um produto no catálogo com preço de custo, preço de revista e código de barras.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="brand_id">Marca</Label>
                                <select id="brand_id" v-model="form.brand_id" required class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="name">Nome do Produto</Label>
                                <Input id="name" v-model="form.name" required placeholder="Ex: Batom Matte Coral" class="rounded-xl" />
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description">Descrição</Label>
                            <Input id="description" v-model="form.description" placeholder="Ex: Batom FPS 15 textura suave" class="rounded-xl" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="ean">Código de Barras (EAN)</Label>
                                <Input id="ean" v-model="form.ean" placeholder="Utilizado para escaneamento" class="rounded-xl" />
                                <span v-if="form.errors.ean" class="text-xs text-destructive">{{ form.errors.ean }}</span>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="sku">SKU (Código Interno)</Label>
                                <Input id="sku" v-model="form.sku" placeholder="Ex: AVO-BAT-COR" class="rounded-xl" />
                                <span v-if="form.errors.sku" class="text-xs text-destructive">{{ form.errors.sku }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 bg-rose-500/5 dark:bg-rose-500/[0.02] p-3 rounded-2xl border border-rose-500/10">
                            <div class="grid gap-1.5">
                                <Label for="cost_price" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">P. Custo (R$)</Label>
                                <Input id="cost_price" type="number" step="0.01" v-model="form.cost_price" required class="rounded-xl text-xs" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="catalog_price" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">P. Revista (R$)</Label>
                                <Input id="catalog_price" type="number" step="0.01" v-model="form.catalog_price" required class="rounded-xl text-xs" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="sale_price" class="text-[11px] font-semibold uppercase tracking-wider text-rose-500">P. Venda (R$)</Label>
                                <Input id="sale_price" type="number" step="0.01" v-model="form.sale_price" required class="rounded-xl text-xs" />
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="stock_quantity">Quantidade Inicial em Estoque</Label>
                                <Input id="stock_quantity" type="number" v-model="form.stock_quantity" required class="rounded-xl" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="min_stock_quantity">Alerta de Estoque Mínimo</Label>
                                <Input id="min_stock_quantity" type="number" v-model="form.min_stock_quantity" required class="rounded-xl" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mt-1">
                            <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 h-4 w-4" />
                            <Label for="is_active" class="text-sm font-medium cursor-pointer">Produto Ativo para Vendas</Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isCreateOpen = false" class="rounded-xl">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Salvar Produto</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-[500px] rounded-2xl max-h-[90vh] overflow-y-auto">
                <form @submit.prevent="submitEdit">
                    <DialogHeader>
                        <DialogTitle>Editar Produto</DialogTitle>
                        <DialogDescription>Modifique os preços, stock ou códigos do produto selecionado.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="edit-brand_id">Marca</Label>
                                <select id="edit-brand_id" v-model="form.brand_id" required class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-name">Nome do Produto</Label>
                                <Input id="edit-name" v-model="form.name" required class="rounded-xl" />
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="edit-description">Descrição</Label>
                            <Input id="edit-description" v-model="form.description" class="rounded-xl" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="edit-ean">Código de Barras (EAN)</Label>
                                <Input id="edit-ean" v-model="form.ean" placeholder="Utilizado para escaneamento" class="rounded-xl" />
                                <span v-if="form.errors.ean" class="text-xs text-destructive">{{ form.errors.ean }}</span>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-sku">SKU (Código Interno)</Label>
                                <Input id="edit-sku" v-model="form.sku" placeholder="Ex: AVO-BAT-COR" class="rounded-xl" />
                                <span v-if="form.errors.sku" class="text-xs text-destructive">{{ form.errors.sku }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 bg-rose-500/5 dark:bg-rose-500/[0.02] p-3 rounded-2xl border border-rose-500/10">
                            <div class="grid gap-1.5">
                                <Label for="edit-cost_price" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">P. Custo (R$)</Label>
                                <Input id="edit-cost_price" type="number" step="0.01" v-model="form.cost_price" required class="rounded-xl text-xs" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-catalog_price" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">P. Revista (R$)</Label>
                                <Input id="edit-catalog_price" type="number" step="0.01" v-model="form.catalog_price" required class="rounded-xl text-xs" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-sale_price" class="text-[11px] font-semibold uppercase tracking-wider text-rose-500">P. Venda (R$)</Label>
                                <Input id="edit-sale_price" type="number" step="0.01" v-model="form.sale_price" required class="rounded-xl text-xs" />
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="grid gap-1.5">
                                <Label for="edit-stock_quantity">Quantidade em Estoque</Label>
                                <Input id="edit-stock_quantity" type="number" v-model="form.stock_quantity" required class="rounded-xl" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="edit-min_stock_quantity">Alerta de Estoque Mínimo</Label>
                                <Input id="edit-min_stock_quantity" type="number" v-model="form.min_stock_quantity" required class="rounded-xl" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mt-1">
                            <input type="checkbox" id="edit-is_active" v-model="form.is_active" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 h-4 w-4" />
                            <Label for="edit-is_active" class="text-sm font-medium cursor-pointer">Produto Ativo para Vendas</Label>
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
