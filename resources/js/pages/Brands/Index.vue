<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Edit2, Trash2, Tag, Check, AlertCircle } from '@lucide/vue';
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

interface Brand {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    products_count: number;
}

const props = defineProps<{
    brands: Brand[];
}>();

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const currentBrand = ref<Brand | null>(null);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isCreateOpen.value = true;
};

const submitCreate = () => {
    form.post('/brands', {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
};

const openEditModal = (brand: Brand) => {
    currentBrand.value = brand;
    form.name = brand.name;
    form.description = brand.description || '';
    form.is_active = brand.is_active;
    form.clearErrors();
    isEditOpen.value = true;
};

const submitEdit = () => {
    if (!currentBrand.value) return;
    form.put(`/brands/${currentBrand.value.id}`, {
        onSuccess: () => {
            isEditOpen.value = false;
            currentBrand.value = null;
            form.reset();
        },
    });
};

const deleteForm = useForm({});
const deleteBrand = (brand: Brand) => {
    if (confirm(`Tem certeza que deseja excluir a marca "${brand.name}"?`)) {
        deleteForm.delete(`/brands/${brand.id}`);
    }
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Marcas de Cosméticos" />

    <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    <Tag class="h-6 w-6 text-rose-500" /> Marcas
                </h1>
                <p class="text-sm text-muted-foreground mt-1">Gerencie as marcas de cosméticos do seu catálogo.</p>
            </div>
            <Button @click="openCreateModal" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">
                <Plus class="mr-2 h-4 w-4" /> Nova Marca
            </Button>
        </div>

        <!-- Brands Grid/List (Mobile Friendly) -->
        <div v-if="brands.length === 0" class="flex flex-col items-center justify-center p-12 border border-dashed rounded-2xl bg-muted/20">
            <Tag class="h-12 w-12 text-muted-foreground stroke-1 mb-4" />
            <h3 class="text-base font-semibold">Nenhuma marca cadastrada</h3>
            <p class="text-sm text-muted-foreground text-center max-w-xs mt-1">Adicione marcas como Natura, Avon ou O Boticário para começar.</p>
            <Button @click="openCreateModal" variant="outline" class="mt-4 border-rose-500/30 text-rose-600 hover:text-rose-500">
                Criar Primeira Marca
            </Button>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="brand in brands" :key="brand.id" class="rounded-xl shadow-sm border border-border/40 hover:border-rose-500/20 transition-all flex flex-col justify-between">
                <CardHeader class="p-5 pb-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <CardTitle class="text-lg font-bold text-foreground">{{ brand.name }}</CardTitle>
                            <Badge class="text-[9px] px-1.5 py-0.5 rounded-md mt-1.5 uppercase font-bold" :variant="brand.is_active ? 'secondary' : 'destructive'">
                                {{ brand.is_active ? 'Ativa' : 'Inativa' }}
                            </Badge>
                        </div>
                        <Badge variant="outline" class="text-xs px-2 py-0.5 rounded-full font-semibold border-rose-500/20 text-rose-600 dark:text-rose-400 bg-rose-500/5">
                            {{ brand.products_count }} {{ brand.products_count === 1 ? 'produto' : 'produtos' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-5 pt-0">
                    <p class="text-xs text-muted-foreground line-clamp-2 min-h-[32px] mb-4">
                        {{ brand.description || 'Nenhuma descrição adicionada.' }}
                    </p>
                    <div class="flex justify-end gap-2 border-t border-border/30 pt-3">
                        <Button @click="openEditModal(brand)" variant="ghost" size="sm" class="text-xs h-8 text-foreground hover:bg-muted/80">
                            <Edit2 class="mr-1.5 h-3.5 w-3.5" /> Editar
                        </Button>
                        <Button @click="deleteBrand(brand)" variant="ghost" size="sm" class="text-xs h-8 text-destructive hover:bg-destructive/10">
                            <Trash2 class="mr-1.5 h-3.5 w-3.5" /> Excluir
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Create Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-2xl">
                <form @submit.prevent="submitCreate">
                    <DialogHeader>
                        <DialogTitle>Nova Marca</DialogTitle>
                        <DialogDescription>Preencha os dados abaixo para cadastrar uma nova marca no sistema.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Nome da Marca</Label>
                            <Input id="name" v-model="form.name" placeholder="Ex: Natura, Avon..." required class="rounded-xl" />
                            <span v-if="form.errors.name" class="text-xs text-destructive flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.name }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <Input id="description" v-model="form.description" placeholder="Opcional" class="rounded-xl" />
                            <span v-if="form.errors.description" class="text-xs text-destructive flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.description }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 h-4 w-4" />
                            <Label for="is_active" class="text-sm font-medium cursor-pointer">Marca Ativa para Vendas</Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isCreateOpen = false" class="rounded-xl">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Salvar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-2xl">
                <form @submit.prevent="submitEdit">
                    <DialogHeader>
                        <DialogTitle>Editar Marca</DialogTitle>
                        <DialogDescription>Atualize as informações da marca selecionada.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-name">Nome da Marca</Label>
                            <Input id="edit-name" v-model="form.name" required class="rounded-xl" />
                            <span v-if="form.errors.name" class="text-xs text-destructive flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.name }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-description">Descrição</Label>
                            <Input id="edit-description" v-model="form.description" class="rounded-xl" />
                            <span v-if="form.errors.description" class="text-xs text-destructive flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.description }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <input type="checkbox" id="edit-is_active" v-model="form.is_active" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 h-4 w-4" />
                            <Label for="edit-is_active" class="text-sm font-medium cursor-pointer">Marca Ativa para Vendas</Label>
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
