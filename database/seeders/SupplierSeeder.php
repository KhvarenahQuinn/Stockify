<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Vendor records. Codes are the lookup key for ProductImport (which matches a
 * spreadsheet's `supplier` column by code or name) and for SupplierService's
 * next-code generator, so they follow the SUP-0000 series it produces.
 *
 * Emails use the .test TLD (RFC 6761 reserves it) so seeded addresses can never
 * reach a real mail server.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-0001',
                'name' => 'Nusantara Packaging',
                'email' => 'orders@nusantarapackaging.test',
                'phone' => '+62-21-555-0101',
                'address' => 'Jl. Industri Raya No. 10, Jakarta Timur, Jakarta 13920',
            ],
            [
                'code' => 'SUP-0002',
                'name' => 'Prima Safety Supply',
                'email' => 'sales@primasafety.test',
                'phone' => '+62-22-555-0202',
                'address' => 'Jl. Gudang Utama No. 25, Gedebage, Bandung 40294',
            ],
            [
                'code' => 'SUP-0003',
                'name' => 'Bersih Sentosa',
                'email' => 'contact@bersihsentosa.test',
                'phone' => '+62-31-555-0303',
                'address' => 'Jl. Logistik Barat No. 8, Surabaya 60126',
            ],
            [
                'code' => 'SUP-0004',
                'name' => 'Elektro Mandiri',
                'email' => 'orders@elektromandiri.test',
                'phone' => '+62-21-555-0404',
                'address' => 'Jl. Pigeon No. 12, Kemayoran, Jakarta 10510',
            ],
            [
                'code' => 'SUP-0005',
                'name' => 'Kertas Stationery Works',
                'email' => 'hello@kertasworks.test',
                'phone' => '+62-27-555-0505',
                'address' => 'Jl. Malioboro No. 77, Gondokusuman, Yogyakarta 55223',
            ],
            [
                'code' => 'SUP-0006',
                'name' => 'Bahan Baku Nusantara',
                'email' => 'sales@bahanbakunusantara.test',
                'phone' => '+62-271-555-0606',
                'address' => 'Jl. Slamet Riyadi No. 118, Surakarta 57123',
            ],
            [
                'code' => 'SUP-0007',
                'name' => 'Cold Chain Solutions',
                'email' => 'support@coldchainsolutions.test',
                'phone' => '+62-21-555-0707',
                'address' => 'Jl. Cooling Plant No. 3, Cakung, Jakarta 13920',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['code' => $supplier['code']], $supplier);
        }
    }
}