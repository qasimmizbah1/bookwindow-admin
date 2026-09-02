<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    public function collection(Collection $rows)
    {
        $customers = [];

        foreach ($rows as $row) {

            if (empty($row['email'])) {
                continue;
            }

            $customers[] = [
                'first_name' => $row['firstname'] ?? '',
                'last_name'  => $row['lastname'] ?? '',
                'email'      => trim($row['email']),
                'phone'      => $row['phone'] ?? '',
                'address'    => $row['address'] ?? '',
                'address_2'  => $row['address_2'] ?? '',
                'city'       => $row['city'] ?? '',
                'state'      => $row['state'] ?? '',
                'zip_code'   => $row['zipcode'] ?? '',
                'country'    => 'India',
                'password'   => Hash::make(Str::random(10)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Customer::upsert(
            $customers,
            ['email'], // Unique column
            [
                'first_name',
                'last_name',
                'phone',
                'address',
                'address_2',
                'city',
                'state',
                'zip_code',
                'country',
                'updated_at'
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}