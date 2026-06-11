<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    ChevronLeft, 
    ShoppingCart, 
    Search, 
    Plus, 
    Minus, 
    Trash2, 
    Camera, 
    AlertTriangle,
    Barcode
} from '@lucide/vue';
import { ref, computed, onBeforeUnmount } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    balance: number;
    max_credit_limit: number;
}

const props = defineProps<{
    customers: Customer[];
    products: Product[];
}>();

// Cart state
interface CartItem {
    product: Product;
    quantity: number;
}
const cart = ref<CartItem[]>([]);

// Form state
const form = useForm({
    customer_id: '',
    discount_amount: 0.00,
    payment_method: 'cash',
    notes: '',
    items: [] as { product_id: number; quantity: number }[],
});

// Scanning state
const isScanning = ref(false);
const stream = ref<MediaStream | null>(null);
const videoElement = ref<HTMLVideoElement | null>(null);
const manualEan = ref('');

// Filter state
const search = ref('');
const filteredProducts = computed(() => {
    if (!search.value) {
return props.products.slice(0, 8);
}

    const query = search.value.toLowerCase();

    return props.products.filter(p => 
        p.name.toLowerCase().includes(query) || 
        (p.ean && p.ean.includes(query)) ||
        p.brand.name.toLowerCase().includes(query)
    );
});

// Selected customer info
const selectedCustomer = computed(() => {
    if (!form.customer_id) {
return null;
}

    return props.customers.find(c => c.id === Number(form.customer_id)) || null;
});

// Cart calculations
const subtotalAmount = computed(() => {
    return cart.value.reduce((acc, item) => acc + (item.product.sale_price * item.quantity), 0);
});

const totalAmount = computed(() => {
    return Math.max(0.00, subtotalAmount.value - Number(form.discount_amount));
});

// Business rule check: limit verification for fiado
const isOverLimit = computed(() => {
    if (form.payment_method !== 'fiado' || !selectedCustomer.value) {
return false;
}

    const currentDebt = Number(selectedCustomer.value.balance);
    const limit = Number(selectedCustomer.value.max_credit_limit);

    return (currentDebt + totalAmount.value) > limit;
});

// Cart helpers
const addToCart = (product: Product) => {
    // Check stock
    const existing = cart.value.find(item => item.product.id === product.id);
    const currentQty = existing ? existing.quantity : 0;
    
    if (product.stock_quantity <= currentQty) {
        alert(`Estoque insuficiente! Apenas ${product.stock_quantity} unidades disponíveis.`);

        return;
    }

    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({ product, quantity: 1 });
    }
};

const removeFromCart = (productId: number) => {
    cart.value = cart.value.filter(item => item.product.id !== productId);
};

const decreaseQty = (productId: number) => {
    const item = cart.value.find(item => item.product.id === productId);

    if (!item) {
return;
}

    if (item.quantity > 1) {
        item.quantity--;
    } else {
        removeFromCart(productId);
    }
};

const increaseQty = (productId: number) => {
    const item = cart.value.find(item => item.product.id === productId);

    if (!item) {
return;
}

    if (item.product.stock_quantity > item.quantity) {
        item.quantity++;
    } else {
        alert(`Estoque máximo atingido!`);
    }
};

// Checkout
const submitSale = () => {
    if (cart.value.length === 0) {
        alert('O carrinho está vazio.');

        return;
    }

    if (form.payment_method === 'fiado' && !form.customer_id) {
        alert('Selecione um cliente para a opção Fiado.');

        return;
    }

    if (isOverLimit.value) {
        if (!confirm('ATENÇÃO: Esta venda excederá o limite de crédito do cliente. Deseja prosseguir mesmo assim?')) {
            return;
        }
    }

    form.items = cart.value.map(item => ({
        product_id: item.product.id,
        quantity: item.quantity
    }));

    form.post('/sales');
};

// Web Audio API Beep Generator (Vibrant Micro-animation/Interaction)
const playBeep = () => {
    try {
        const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(1000, audioCtx.currentTime); // High beep
        gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);

        oscillator.start();
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.12);
        oscillator.stop(audioCtx.currentTime + 0.12);
    } catch (e) {
        console.error('Audio context beep failed', e);
    }
};

// Camera Scanning functionality
const startCamera = async () => {
    isScanning.value = true;

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });

        if (videoElement.value) {
            videoElement.value.srcObject = stream.value;

            // Native barcode detection loop if supported
            if ('BarcodeDetector' in window) {
                requestAnimationFrame(detectBarcodes);
            }
        }
    } catch (err) {
        console.error('Failed to open camera:', err);
        alert('Não foi possível acessar a câmera. Certifique-se de que está usando HTTPS e deu as devidas permissões.');
        isScanning.value = false;
    }
};

const stopCamera = () => {
    isScanning.value = false;

    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
};

// Barcode Detector loop
const detectBarcodes = async () => {
    if (!videoElement.value || !isScanning.value) {
return;
}

    try {
        const detector = new (window as any).BarcodeDetector({ formats: ['ean_13', 'ean_8'] });
        const barcodes = await detector.detect(videoElement.value);

        if (barcodes.length > 0) {
            const rawValue = barcodes[0].rawValue;
            handleBarcodeFound(rawValue);

            return;
        }
    } catch {
        // Fallback or ignore
    }

    if (isScanning.value) {
        requestAnimationFrame(detectBarcodes);
    }
};

const handleBarcodeFound = (barcode: string) => {
    playBeep();
    const product = props.products.find(p => p.ean === barcode);

    if (product) {
        addToCart(product);
        alert(`Sucesso: "${product.name}" adicionado ao carrinho!`);
    } else {
        alert(`Produto com código de barras "${barcode}" não cadastrado ou fora de estoque.`);
    }

    stopCamera();
};

// Simulation helper for testing without physical barcodes
const simulateScan = (barcode: string) => {
    handleBarcodeFound(barcode);
};

const handleManualEanSubmit = () => {
    if (!manualEan.value) {
return;
}

    handleBarcodeFound(manualEan.value);
    manualEan.value = '';
};

onBeforeUnmount(() => {
    stopCamera();
});

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Venda Rápida" />

    <div class="flex flex-col lg:flex-row gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Left Side: Product Selector & Camera Trigger -->
        <div class="flex-1 flex flex-col gap-4">
            <!-- Header -->
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="icon" class="rounded-xl">
                    <Link href="/dashboard">
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-bold text-foreground">Checkout Móvel</h1>
                    <p class="text-xs text-muted-foreground mt-0.5">Bipe com a câmera ou pesquise itens</p>
                </div>
            </div>

            <!-- Scanner Box (Mobile First prominence) -->
            <Card class="rounded-xl overflow-hidden border-rose-500/20 bg-rose-500/[0.01]">
                <CardContent class="p-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-rose-500/10 rounded-xl flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                            <Barcode class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold">Escaneamento de Barcode</h3>
                            <p class="text-[11px] text-muted-foreground">Bipe o EAN do produto com a câmera do celular.</p>
                        </div>
                    </div>
                    <Button @click="startCamera" class="w-full sm:w-auto bg-rose-600 hover:bg-rose-500 text-white rounded-xl shadow-md">
                        <Camera class="mr-1.5 h-4 w-4" /> Ativar Câmera
                    </Button>
                </CardContent>
            </Card>

            <!-- Manual Barcode Search / Input -->
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-2.5 h-4.5 w-4.5 text-muted-foreground" />
                    <Input v-model="search" placeholder="Pesquisar por nome ou marca..." class="pl-10 rounded-xl" />
                </div>
                <div class="flex gap-1">
                    <Input v-model="manualEan" placeholder="Digitar EAN" class="w-[120px] rounded-xl text-xs" @keydown.enter.prevent="handleManualEanSubmit" />
                    <Button @click="handleManualEanSubmit" variant="outline" class="rounded-xl">Bipar</Button>
                </div>
            </div>

            <!-- Products Results List -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <Card v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)" class="rounded-xl border border-border/40 hover:border-rose-500/30 transition-all cursor-pointer p-3 select-none flex flex-col justify-between">
                    <div>
                        <span class="text-[8px] uppercase font-bold text-rose-500">{{ product.brand.name }}</span>
                        <h4 class="text-[11px] font-bold text-foreground line-clamp-2 mt-0.5 leading-tight">{{ product.name }}</h4>
                    </div>
                    <div class="mt-2.5 flex justify-between items-center">
                        <span class="text-xs font-extrabold text-foreground">{{ formatCurrency(product.sale_price) }}</span>
                        <Badge variant="outline" class="text-[8px] px-1 py-0 rounded font-semibold text-muted-foreground">
                            {{ product.stock_quantity }} un
                        </Badge>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Right Side: Cart Summary & Checkout -->
        <div class="w-full lg:w-[380px] flex flex-col gap-4">
            <Card class="rounded-xl shadow-sm border border-border/40 flex flex-col justify-between h-full min-h-[450px]">
                <CardHeader class="p-4 pb-2 border-b border-border/30">
                    <CardTitle class="text-sm font-bold flex items-center justify-between">
                        Carrinho
                        <Badge class="bg-rose-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                            {{ cart.reduce((acc, item) => acc + item.quantity, 0) }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                
                <CardContent class="p-4 pt-3 flex-1 flex flex-col justify-between">
                    <!-- Cart Items List -->
                    <div class="space-y-2 flex-1 max-h-[220px] overflow-y-auto pr-1">
                        <p v-if="cart.length === 0" class="text-xs text-muted-foreground text-center py-12">
                            Carrinho vazio. Bipe ou selecione produtos ao lado.
                        </p>
                        <div v-for="item in cart" :key="item.product.id" class="flex justify-between items-center text-xs bg-muted/20 p-2.5 rounded-xl border">
                            <div class="max-w-[60%]">
                                <p class="font-bold text-foreground line-clamp-1">{{ item.product.name }}</p>
                                <p class="text-[10px] text-muted-foreground mt-0.5">{{ formatCurrency(item.product.sale_price) }}</p>
                            </div>
                            <!-- Quantity adjuster -->
                            <div class="flex items-center gap-2">
                                <Button @click="decreaseQty(item.product.id)" variant="ghost" size="icon" class="h-6 w-6 rounded-md hover:bg-muted/80">
                                    <Minus class="h-3 w-3" />
                                </Button>
                                <span class="font-bold text-xs w-4 text-center">{{ item.quantity }}</span>
                                <Button @click="increaseQty(item.product.id)" variant="ghost" size="icon" class="h-6 w-6 rounded-md hover:bg-muted/80">
                                    <Plus class="h-3 w-3" />
                                </Button>
                                <Button @click="removeFromCart(item.product.id)" variant="ghost" size="icon" class="h-6 w-6 rounded-md text-destructive hover:bg-destructive/10">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Payment details -->
                    <div class="border-t border-border/40 pt-4 space-y-3.5 mt-4">
                        <!-- Customer dropdown -->
                        <div class="grid gap-1.5">
                            <Label for="customer" class="text-xs font-semibold text-muted-foreground">Cliente (Opcional, Obrigatório p/ Fiado)</Label>
                            <select id="customer" v-model="form.customer_id" class="flex h-9 w-full rounded-xl border border-input bg-background px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <option value="">Venda Rápida (Avulso)</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <!-- Customer credit ledger alert -->
                        <div v-if="selectedCustomer" class="bg-muted/30 p-2.5 rounded-xl text-[11px] space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Saldo na Caderneta:</span>
                                <span class="font-bold" :class="{'text-rose-600 dark:text-rose-400': selectedCustomer.balance > 0}">
                                    {{ formatCurrency(selectedCustomer.balance) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Limite Disponível:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(Math.max(0, selectedCustomer.max_credit_limit - selectedCustomer.balance)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Payment Method Toggle -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-semibold text-muted-foreground">Forma de Pagamento</Label>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button v-for="method in ['cash', 'card', 'pix', 'fiado']" :key="method" type="button" @click="form.payment_method = method" class="h-10 text-[10px] uppercase font-bold border rounded-xl flex flex-col items-center justify-center transition-all" :class="form.payment_method === method ? 'bg-rose-600 text-white border-rose-600 shadow-md shadow-rose-600/10' : 'bg-background hover:bg-muted text-muted-foreground'">
                                    {{ method === 'cash' ? 'Dinheiro' : (method === 'card' ? 'Cartão' : (method === 'pix' ? 'PIX' : 'Fiado')) }}
                                </button>
                            </div>
                        </div>

                        <!-- Credit limit caution alarm -->
                        <div v-if="isOverLimit" class="bg-destructive/10 border border-destructive/20 text-destructive p-2.5 rounded-xl flex items-start gap-2 text-[10px]">
                            <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-bold">Aviso de Limite Excedido!</p>
                                <p>Esta venda ultrapassa o limite restante de crédito do cliente.</p>
                            </div>
                        </div>

                        <!-- Cash discount input -->
                        <div class="flex gap-3 justify-between items-center text-xs">
                            <Label for="discount">Desconto (R$)</Label>
                            <Input id="discount" type="number" step="0.01" v-model="form.discount_amount" class="w-[90px] h-8 rounded-lg text-right" />
                        </div>

                        <!-- Summary -->
                        <div class="space-y-1.5 pt-2 border-t border-border/30 text-xs">
                            <div class="flex justify-between text-muted-foreground">
                                <span>Subtotal:</span>
                                <span>{{ formatCurrency(subtotalAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-extrabold text-foreground">
                                <span>Total Final:</span>
                                <span>{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>

                        <!-- Confirm checkout button -->
                        <Button @click="submitSale" :disabled="form.processing || cart.value === 0" class="w-full h-11 bg-rose-600 hover:bg-rose-500 text-white rounded-xl shadow-lg shadow-rose-600/15 font-bold mt-1 text-xs">
                            <ShoppingCart class="mr-1.5 h-4.5 w-4.5" /> Registrar e Finalizar Venda
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Camera Scanner Overlay Dialog (WhatsApp Web scanner feel) -->
        <Dialog :open="isScanning" @update:open="stopCamera">
            <DialogContent class="sm:max-w-[450px] p-0 overflow-hidden rounded-3xl bg-black border-0">
                <div class="relative aspect-[3/4] w-full bg-black flex items-center justify-center">
                    <!-- Video feed -->
                    <video ref="videoElement" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>
                    
                    <!-- Scanner overlay grid -->
                    <div class="absolute inset-0 border-4 border-black/40 flex items-center justify-center p-8">
                        <div class="w-full aspect-square border-2 border-dashed border-rose-500/70 rounded-2xl relative shadow-[0_0_0_9999px_rgba(0,0,0,0.6)]">
                            <!-- Scanning line anim -->
                            <div class="absolute left-0 right-0 h-0.5 bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)] animate-pulse" style="animation: scan 2s linear infinite;"></div>
                        </div>
                    </div>
                    
                    <!-- Control buttons -->
                    <div class="absolute bottom-6 left-0 right-0 flex flex-col items-center gap-3 px-6">
                        <!-- Scanner Simulation tools for testing -->
                        <div class="w-full flex gap-1.5 justify-center overflow-x-auto py-1">
                            <Button v-for="p in products.slice(0, 3)" :key="p.id" @click="simulateScan(p.ean || '')" size="sm" variant="secondary" class="bg-white/10 hover:bg-white/20 text-white text-[9px] h-7 px-2 rounded-lg border border-white/10 font-bold backdrop-blur-md">
                                Simular Bip: {{ p.name.split(' ')[0] }}
                            </Button>
                        </div>
                        <Button @click="stopCamera" variant="ghost" class="text-white bg-white/10 hover:bg-white/20 hover:text-white rounded-full font-bold px-5 text-xs py-1.5 backdrop-blur-md border border-white/10">
                            Fechar Câmera
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
@keyframes scan {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
</style>
