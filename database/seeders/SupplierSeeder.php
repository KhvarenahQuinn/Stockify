<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Seed the application's suppliers.
     */
    public function run(): void
    {
        foreach ([
            [
                'code' => 'SUP-001',
                'name' => 'Nusantara Packaging',
                'email' => 'orders@nusantarapack.example',
                'phone' => '+62-21-555-0101',
                'address' => 'Jl. Industri Raya No. 10, Jakarta, Indonesia',
            ],
            [
                'code' => 'SUP-002',
                'name' => 'Prima Safety Supply',
                'email' => 'sales@primasafety.example',
                'phone' => '+62-22-555-0202',
                'address' => 'Jl. Gudang Utama No. 25, Bandung, Indonesia',
            ],
            [
                'code' => 'SUP-003',
                'name' => 'Bersih Sentosa',
                'email' => 'contact@bersihsentosa.example',
                'phone' => '+62-31-555-0303',
                'address' => 'Jl. Logistik Barat No. 8, Surabaya, Indonesia',
            ],
        ] as $supplier) {
            Supplier::updateOrCreate(['code' => $supplier['code']], $supplier);
        }
    }
}
