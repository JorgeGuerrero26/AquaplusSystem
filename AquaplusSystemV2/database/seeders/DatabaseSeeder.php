<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

       //\App\Models\Cliente::factory(10)->create();
        \App\Models\Entrega::factory(100)->create();
        //\App\Models\Proveedor::factory(10)->create();
        \App\Models\Tipo_usuario::factory(2)->create();
        \App\Models\Usuario::factory(10)->create();
        //\App\Models\Material::factory(10)->create();
        \App\Models\Venta::factory(15000)->create();
        \App\Models\Detalle_venta::factory(30000)->create();
        \App\Models\Compra::factory(15000)->create();
        \App\Models\Detalle_compra::factory(30000)->create(); 

    }
}
