<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create default test user
        User::factory()->create([
            'name' => 'Developer',
            'email' => 'dev@admin.com',
            'password' => bcrypt('M4rc0nd35'),
        ]);

        // 2. Seed Brands
        $brandsData = [
            [
                'name' => 'Natura',
                'slug' => 'natura',
                'description' => 'Produtos de beleza, cuidados corporais, perfumaria e sustentabilidade.',
                'logo_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'O Boticário',
                'slug' => 'o-boticario',
                'description' => 'Fragrâncias, cosméticos, maquiagem e cuidados pessoais de alta qualidade.',
                'logo_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Avon',
                'slug' => 'avon',
                'description' => 'Maquiagem icônica, cuidados com a pele Renew e fragrâncias acessíveis.',
                'logo_path' => null,
                'is_active' => true,
            ],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['slug']] = Brand::create($b);
        }

        // 3. Seed Products with realistic EAN barcodes and pricing
        $productsData = [
            // Natura
            [
                'brand_slug' => 'natura',
                'name' => 'Tododia Desodorante Hidratante Corporal Algodão 400ml',
                'description' => 'Nutrição profunda com textura cremosa que protege e hidrata a pele.',
                'ean' => '7891528059082',
                'sku' => 'NAT-TODO-ALG',
                'cost_price' => 32.50,
                'catalog_price' => 64.90,
                'stock' => 12,
                'min_stock' => 3,
            ],
            [
                'brand_slug' => 'natura',
                'name' => 'Ekos Castanha Polpa Hidratante para as Mãos 75g',
                'description' => 'Nutre a pele e fortalece as unhas. Textura encorpada e absorção rápida.',
                'ean' => '7891528045610',
                'sku' => 'NAT-EKOS-CAS',
                'cost_price' => 21.00,
                'catalog_price' => 42.00,
                'stock' => 18,
                'min_stock' => 4,
            ],
            [
                'brand_slug' => 'natura',
                'name' => 'Kaiak Desodorante Colônia Masculino 100ml',
                'description' => 'Fragrância hiperaquática clássica da Natura.',
                'ean' => '7891528073538',
                'sku' => 'NAT-KAI-MASC',
                'cost_price' => 74.90,
                'catalog_price' => 149.90,
                'stock' => 8,
                'min_stock' => 2,
            ],
            [
                'brand_slug' => 'natura',
                'name' => 'Natura Homem Sagaz Colônia Masculino 100ml',
                'description' => 'Amadeirado intenso com notas quentes de sândalo e cedro.',
                'ean' => '7891528114422',
                'sku' => 'NAT-HOM-SAG',
                'cost_price' => 89.90,
                'catalog_price' => 179.90,
                'stock' => 5,
                'min_stock' => 2,
            ],

            // O Boticário
            [
                'brand_slug' => 'o-boticario',
                'name' => 'Malbec Desodorante Colônia 100ml',
                'description' => 'A primeira colônia do mundo fabricada com álcool vínico macerado em barris de carvalho.',
                'ean' => '7891033123456',
                'sku' => 'BOT-MALB-REG',
                'cost_price' => 99.90,
                'catalog_price' => 199.90,
                'stock' => 6,
                'min_stock' => 2,
            ],
            [
                'brand_slug' => 'o-boticario',
                'name' => 'Lily Eau de Parfum 75ml',
                'description' => 'Fragrância sofisticada obtida através da enfleurage de lírios.',
                'ean' => '7891033654321',
                'sku' => 'BOT-LILY-EDP',
                'cost_price' => 149.50,
                'catalog_price' => 299.00,
                'stock' => 4,
                'min_stock' => 1,
            ],
            [
                'brand_slug' => 'o-boticario',
                'name' => 'Floratta Red Desodorante Colônia 75ml',
                'description' => 'Fragrância marcante inspirada na flor da maçã de Vermont.',
                'ean' => '7891033987654',
                'sku' => 'BOT-FLOR-RED',
                'cost_price' => 69.90,
                'catalog_price' => 139.90,
                'stock' => 10,
                'min_stock' => 3,
            ],

            // Avon
            [
                'brand_slug' => 'avon',
                'name' => 'Avon Renew Clinical Antissinais FPS 25 50g',
                'description' => 'Creme facial antissinais dia, ajuda a preencher rugas e linhas de expressão.',
                'ean' => '7891022112233',
                'sku' => 'AVO-REN-DIA',
                'cost_price' => 45.00,
                'catalog_price' => 89.90,
                'stock' => 7,
                'min_stock' => 2,
            ],
            [
                'brand_slug' => 'avon',
                'name' => 'Avon Color Trend Batom Efeito Matte FPS 15',
                'description' => 'Batom matte com cores vibrantes e hidratação ideal para o dia a dia.',
                'ean' => '7891022445566',
                'sku' => 'AVO-COL-BAT',
                'cost_price' => 9.50,
                'catalog_price' => 19.90,
                'stock' => 25,
                'min_stock' => 5,
            ],
            [
                'brand_slug' => 'avon',
                'name' => 'Avon Far Away Glamour Deo Parfum 50ml',
                'description' => 'Fragrância adocicada floral com toque marcante de baunilha de Madagascar.',
                'ean' => '7891022778899',
                'sku' => 'AVO-FAR-GLAM',
                'cost_price' => 48.00,
                'catalog_price' => 95.00,
                'stock' => 6,
                'min_stock' => 2,
            ],
        ];

        $products = [];
        foreach ($productsData as $p) {
            $brand = $brands[$p['brand_slug']];
            $products[] = Product::create([
                'brand_id' => $brand->id,
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'description' => $p['description'],
                'ean' => $p['ean'],
                'sku' => $p['sku'],
                'cost_price' => $p['cost_price'],
                'catalog_price' => $p['catalog_price'],
                'sale_price' => $p['catalog_price'],
                'stock_quantity' => $p['stock'],
                'min_stock_quantity' => $p['min_stock'],
                'is_active' => true,
            ]);
        }

        // 4. Seed Customers with varying statuses
        $customersData = [
            [
                'name' => 'Ana Silva Santos',
                'phone' => '(11) 98765-4321',
                'email' => 'ana.silva@gmail.com',
                'address' => 'Rua das Flores, 123, São Paulo - SP',
                'max_credit_limit' => 300.00,
                'notes' => 'Cliente assídua. Prefere perfumes adocicados.',
            ],
            [
                'name' => 'Beatriz Costa Oliveira',
                'phone' => '(11) 97654-3210',
                'email' => 'beatriz.costa@hotmail.com',
                'address' => 'Av. Paulista, 1000, Apto 42, São Paulo - SP',
                'max_credit_limit' => 500.00,
                'notes' => 'Paga sempre em dia via PIX.',
            ],
            [
                'name' => 'Camila Souza Ferreira',
                'phone' => '(11) 96543-2109',
                'email' => 'camila.souza@yahoo.com.br',
                'address' => 'Rua dos Pinheiros, 456, São Paulo - SP',
                'max_credit_limit' => 400.00,
                'notes' => 'Geralmente compra presentes de última hora.',
            ],
            [
                'name' => 'Débora Lima Rocha',
                'phone' => '(11) 95432-1098',
                'email' => 'debora.lima@outlook.com',
                'address' => 'Rua Augusta, 888, São Paulo - SP',
                'max_credit_limit' => 600.00,
                'notes' => 'Costuma pedir fiado ("caderneta"). Monitorar limite.',
            ],
            [
                'name' => 'Eduarda Mendes',
                'phone' => '(11) 94321-0987',
                'email' => 'eduarda.mendes@gmail.com',
                'address' => 'Rua Oscar Freire, 1200, São Paulo - SP',
                'max_credit_limit' => 800.00,
                'notes' => 'Compra produtos da linha Ekos frequentemente.',
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::create($c);
        }

        // 5. Seed historical Sales and build up Fiado balances dynamically
        // We will seed sales over the last 30 days.
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 15; $i++) {
            DB::transaction(function () use ($customers, $products, $i, $faker) {
                $customer = $customers[array_rand($customers)];
                $saleDate = Carbon::now()->subDays(15 - $i)->subHours(rand(1, 10));

                // Select 1 to 3 random products
                $numProducts = rand(1, 3);
                $selectedProducts = (array) array_rand($products, $numProducts);

                $items = [];
                $subtotal = 0;
                $cost = 0;

                foreach ($selectedProducts as $idx) {
                    $prod = $products[$idx];
                    $qty = rand(1, 2);
                    $price = $prod->sale_price;
                    $unitCost = $prod->cost_price;

                    $items[] = [
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'unit_cost' => $unitCost,
                        'subtotal' => $price * $qty,
                    ];

                    $subtotal += $price * $qty;
                    $cost += $unitCost * $qty;

                    // Deduct stock
                    $prod->decrement('stock_quantity', $qty);
                }

                $discount = rand(0, 1) ? $faker->randomElement([5.00, 10.00, 15.00, 0.00]) : 0.00;
                // Avoid negative total
                $discount = min($discount, $subtotal - 1);
                $total = $subtotal - $discount;

                // Alternate payment methods
                $method = $faker->randomElement(['cash', 'card', 'pix', 'fiado']);
                $status = $method === 'fiado' ? 'pending' : 'paid';

                $sale = Sale::create([
                    'customer_id' => $customer->id,
                    'sale_date' => $saleDate,
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'total_cost' => $cost,
                    'payment_status' => $status,
                    'payment_method' => $method,
                    'notes' => $method === 'fiado' ? 'Adicionado à caderneta.' : 'Pago na entrega.',
                ]);

                foreach ($items as $item) {
                    $sale->items()->create($item);
                }

                // If sale was fiado, register debit in ledger (and automatically update customer balance)
                if ($method === 'fiado') {
                    $customer->addDebit(
                        $total,
                        "Compra a prazo (Fiado) - Venda #{$sale->id}",
                        $sale->id
                    );
                }
            });
        }

        // 6. Seed some direct payment (credit) transactions to simulate payments on account
        // This makes the caderneta history realistic (some debits have been partially or fully paid)
        foreach ($customers as $c) {
            // Reload customer to get updated balance from seeded sales
            $c->refresh();
            if ($c->balance > 0) {
                // Customer has debt. Let's make them pay part of it
                $payment = round($c->balance * rand(30, 80) / 100, 2);
                if ($payment > 1.00) {
                    $c->addCredit($payment, "Abono/Pagamento parcial da caderneta via PIX");
                }
            }
        }
    }
}

