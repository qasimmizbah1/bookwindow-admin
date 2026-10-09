<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Services\ShippingCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingSettingController extends Controller
{
    /**
     * Get public shipping & COD settings and slabs
     */
    public function index(): JsonResponse
    {
        $setting = ShippingSetting::current();

        $codSlabs = [];
        if (!empty($setting->cod_slabs) && is_array($setting->cod_slabs)) {
            $codSlabs = collect($setting->cod_slabs)
                ->map(function ($slab) {
                    return [
                        'label' => (string) ($slab['label'] ?? ''),
                        'min_amount' => (float) ($slab['min_amount'] ?? 0),
                        'max_amount' => (float) ($slab['max_amount'] ?? 999999),
                        'cod_charge' => (float) ($slab['cod_charge'] ?? 0),
                    ];
                })
                ->sortBy('min_amount')
                ->values()
                ->all();
        }

        $setting = ShippingSetting::find(1) ?? ShippingSetting::firstOrCreate(['id' => 1]);

        return response()->json([
            'success' => true,
            'data' => [
                'cod' => [
                    'is_enabled' => (bool) $setting->is_cod_enabled,
                    'max_order_amount' => !empty($setting->max_cod_order_amount) ? (float) $setting->max_cod_order_amount : 0.00,
                    'default_charge' => (float) ($setting->default_cod_charge ?? 49.00),
                    'slabs' => $codSlabs,
                ],
                'shipping' => [
                    'is_free_shipping_enabled' => (bool) $setting->is_free_shipping_enabled,
                    'min_order_for_free_shipping' => (float) ($setting->min_order_for_free_shipping ?? 999.00),
                    'default_flat_shipping' => (float) ($setting->default_flat_shipping ?? 49.00),
                    'is_weight_shipping_enabled' => (bool) $setting->is_weight_shipping_enabled,
                    'packaging_buffer_weight' => (float) ($setting->packaging_buffer_weight ?? 0.100),
                    'weight_slabs' => $setting->weight_slabs ?? [],
                ],
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Calculate dynamic shipping & COD fee for given subtotal and parcel weight
     */
    public function calculate(Request $request): JsonResponse
    {
        $subtotal = (float) $request->input('subtotal', 0);
        $weight = (float) $request->input('weight', 0.5);
        $paymentMethod = (string) $request->input('payment_method', 'cod');

        /** @var ShippingCalculationService $service */
        $service = app(ShippingCalculationService::class);
        $result = $service->calculate($subtotal, $weight, $paymentMethod);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
