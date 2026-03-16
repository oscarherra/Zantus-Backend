<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Ventas', 'type' => 'income'],
            ['name' => 'SINPE recibido', 'type' => 'income'],
            ['name' => 'Otros ingresos', 'type' => 'income'],

            ['name' => 'Compra a proveedor', 'type' => 'expense'],
            ['name' => 'Electricidad', 'type' => 'expense'],
            ['name' => 'Agua', 'type' => 'expense'],
            ['name' => 'Internet', 'type' => 'expense'],
            ['name' => 'Gas', 'type' => 'expense'],
            ['name' => 'Planilla', 'type' => 'expense'],
            ['name' => 'Alquiler', 'type' => 'expense'],
            ['name' => 'Mantenimiento', 'type' => 'expense'],
            ['name' => 'Otros gastos', 'type' => 'expense'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name'], 'type' => $cat['type']],
                ['is_active' => true]
            );
        }
    }
}