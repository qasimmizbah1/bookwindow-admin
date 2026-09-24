<?php

namespace App\Services;

use App\Models\ShippingSetting;
use App\Models\ShippingMethod;

class ShippingCalculationService
{
    /**
     * Calculate Shipping and COD charges based on Admin configurations
     *
     * @param float $cartSubtotal Cart subtotal amount in INR
     * @param float $totalWeight Total parcel weight in KG (defaults to 0.5kg)
     * @param string $paymentMethod 'prepaid', 'razorpay', or 'cod'
     * @param ShippingSetting|null $setting Optional custom or preview settings
     * @return array
     */
    public function calculate(
        float $cartSubtotal,
        float $totalWeight = 0.5,
        string $paymentMethod = 'prepaid',
        ?ShippingSetting $setting = null
    ): array {
        $setting = $setting ?? ShippingSetting::current();

        $isCod = in_array(strtolower(trim($paymentMethod)), ['cod', 'cash_on_delivery', '1']);

        // 1. Calculate Effective Weight (Item Weight + Packaging Buffer)
        $bufferWeight = (float) ($setting->packaging_buffer_weight ?? 0.100);
        $effectiveWeight = max(0.05, (float)$totalWeight + $bufferWeight);

        // 2. Calculate Base Shipping Cost
        $baseShippingCost = (float) ($setting->default_flat_shipping ?? 49.00);
        $matchedWeightSlab = null;
        $weightExplanation = '';

        if ($setting->is_weight_shipping_enabled && !empty($setting->weight_slabs) && is_array($setting->weight_slabs)) {
            // Sort slabs by min_weight ascending
            $slabs = collect($setting->weight_slabs)->sortBy('min_weight')->values()->all();

            $matched = false;
            $highestMaxWeight = 0;
            $highestSlabPrice = 0;

            foreach ($slabs as $slab) {
                $minW = (float) ($slab['min_weight'] ?? 0);
                $maxW = (float) ($slab['max_weight'] ?? 9999);
                $price = (float) ($slab['price'] ?? 0);

                if ($maxW > $highestMaxWeight) {
                    $highestMaxWeight = $maxW;
                    $highestSlabPrice = $price;
                }

                if ($effectiveWeight >= $minW && $effectiveWeight <= $maxW) {
                    $baseShippingCost = $price;
                    $matchedWeightSlab = $slab;
                    $weightExplanation = "Matched Slab: {$slab['label']} ({$minW}kg - {$maxW}kg @ ₹{$price})";
                    $matched = true;
                    break;
                }
            }

            // If effective weight exceeds the highest slab
            if (!$matched && $effectiveWeight > $highestMaxWeight && $highestMaxWeight > 0) {
                $extraWeight = $effectiveWeight - $highestMaxWeight;
                $extraUnits = ceil($extraWeight); // Per 1 KG
                $perKgRate = (float) ($setting->extra_weight_per_kg_rate ?? 30.00);
                $extraCost = $extraUnits * $perKgRate;
                $baseShippingCost = $highestSlabPrice + $extraCost;
                $weightExplanation = "Exceeds {$highestMaxWeight}kg slab (+{$extraUnits}kg @ ₹{$perKgRate}/kg = ₹{$extraCost})";
                $matchedWeightSlab = [
                    'label' => 'Above ' . $highestMaxWeight . 'kg',
                    'price' => $baseShippingCost,
                    'is_overflow' => true,
                ];
            }
        } else {
            $weightExplanation = "Flat Shipping Rate Applied (@ ₹{$baseShippingCost})";
        }

        // 3. Free Shipping Threshold Check
        $isFreeShipping = false;
        $freeShippingThreshold = (float) ($setting->min_order_for_free_shipping ?? 999.00);
        $amountNeededForFreeShipping = max(0, $freeShippingThreshold - $cartSubtotal);

        if ($setting->is_free_shipping_enabled && $cartSubtotal >= $freeShippingThreshold) {
            $isFreeShipping = true;
            $baseShippingCost = 0.00;
        }

        // 4. Calculate COD Charges Slabs
        $codAllowed = true;
        $codRejectionReason = null;
        $codFee = 0.00;
        $matchedCodSlab = null;
        $codExplanation = 'Prepaid Order - No COD Charges';

        if ($isCod) {
            if (!$setting->is_cod_enabled) {
                $codAllowed = false;
                $codRejectionReason = 'Cash on Delivery is currently disabled by store administration.';
                $codExplanation = 'COD Disabled';
            } elseif ($cartSubtotal > (float) ($setting->max_cod_order_amount ?? 5000.00)) {
                $codAllowed = false;
                $maxLimit = (float) $setting->max_cod_order_amount;
                $codRejectionReason = "COD is not permitted on orders exceeding ₹" . number_format($maxLimit, 2);
                $codExplanation = "Order exceeds max COD limit of ₹{$maxLimit}";
            } else {
                // Determine COD Fee from Slabs
                $defaultCodFee = (float) ($setting->default_cod_charge ?? 49.00);
                $codFee = $defaultCodFee;
                $codMatched = false;

                if (!empty($setting->cod_slabs) && is_array($setting->cod_slabs)) {
                    $codSlabs = collect($setting->cod_slabs)->sortBy('min_amount')->values()->all();

                    foreach ($codSlabs as $slab) {
                        $minA = (float) ($slab['min_amount'] ?? 0);
                        $maxA = (float) ($slab['max_amount'] ?? 999999);
                        $charge = (float) ($slab['cod_charge'] ?? 0);

                        if ($cartSubtotal >= $minA && $cartSubtotal <= $maxA) {
                            $codFee = $charge;
                            $matchedCodSlab = $slab;
                            $codExplanation = "Matched COD Slab: {$slab['label']} (₹{$minA} to ₹{$maxA} @ ₹{$charge})";
                            $codMatched = true;
                            break;
                        }
                    }
                }

                if (!$codMatched) {
                    $codExplanation = "Default COD Charge Applied (@ ₹{$defaultCodFee})";
                }
            }
        }

        $totalDeliveryCost = $baseShippingCost + ($codAllowed ? $codFee : 0.00);
        $finalTotal = $cartSubtotal + $totalDeliveryCost;

        return [
            'cart_subtotal' => round($cartSubtotal, 2),
            'item_weight_kg' => round($totalWeight, 3),
            'packaging_buffer_kg' => round($bufferWeight, 3),
            'effective_weight_kg' => round($effectiveWeight, 3),
            'base_shipping_amount' => round($baseShippingCost, 2),
            'is_free_shipping' => $isFreeShipping,
            'free_shipping_threshold' => round($freeShippingThreshold, 2),
            'amount_needed_for_free_shipping' => round($amountNeededForFreeShipping, 2),
            'matched_weight_slab' => $matchedWeightSlab,
            'weight_explanation' => $weightExplanation,
            'payment_method' => $isCod ? 'cod' : 'prepaid',
            'is_cod' => $isCod,
            'is_cod_allowed' => $codAllowed,
            'cod_rejection_reason' => $codRejectionReason,
            'cod_amount' => round($codAllowed ? $codFee : 0.00, 2),
            'matched_cod_slab' => $matchedCodSlab,
            'cod_explanation' => $codExplanation,
            'total_delivery_cost' => round($totalDeliveryCost, 2),
            'final_order_total' => round($finalTotal, 2),
        ];
    }

    /**
     * Calculate Shipping and COD charges for a specific ShippingMethod
     */
    public function calculateForMethod(
        string|ShippingMethod $method,
        float $cartSubtotal,
        float $totalWeight = 0.5,
        string $paymentMethod = 'prepaid',
        ?ShippingSetting $setting = null
    ): array {
        $shippingMethod = $method instanceof ShippingMethod
            ? $method
            : ShippingMethod::where('code', $method)->first();

        // If not found, fallback to standard calculation
        if (!$shippingMethod) {
            return $this->calculate($cartSubtotal, $totalWeight, $paymentMethod, $setting);
        }

        $setting = $setting ?? ShippingSetting::current();
        $isCod = in_array(strtolower(trim($paymentMethod)), ['cod', 'cash_on_delivery', '1']);

        // Check calculation type
        if ($shippingMethod->calculation_type === 'flat_rate') {
            $baseCost = (float) $shippingMethod->price;
            $weightExplanation = "Flat Rate for {$shippingMethod->name} (@ ₹{$baseCost})";

            // Check if free shipping threshold is allowed for this method
            $isFreeShipping = false;
            $freeThreshold = (float) ($setting->min_order_for_free_shipping ?? 999.00);
            if ($shippingMethod->is_free_shipping_eligible && $setting->is_free_shipping_enabled && $cartSubtotal >= $freeThreshold) {
                $isFreeShipping = true;
                $baseCost = 0.00;
            }

            $result = $this->calculate($cartSubtotal, $totalWeight, $paymentMethod, $setting);
            $result['shipping_method_name'] = $shippingMethod->name;
            $result['shipping_method_code'] = $shippingMethod->code;
            $result['delivery_time'] = $shippingMethod->delivery_time;
            $result['base_shipping_amount'] = $baseCost;
            $result['is_free_shipping'] = $isFreeShipping;
            $result['weight_explanation'] = $weightExplanation;
            if (!$shippingMethod->is_cod_allowed && $isCod) {
                $result['is_cod_allowed'] = false;
                $result['cod_rejection_reason'] = "Cash on Delivery is not available with {$shippingMethod->name}.";
                $result['cod_amount'] = 0.00;
            }
            $result['total_delivery_cost'] = round($baseCost + ($result['is_cod_allowed'] ? $result['cod_amount'] : 0), 2);
            $result['final_order_total'] = round($cartSubtotal + $result['total_delivery_cost'], 2);

            return $result;
        }

        if ($shippingMethod->calculation_type === 'free') {
            $result = $this->calculate($cartSubtotal, $totalWeight, $paymentMethod, $setting);
            $result['shipping_method_name'] = $shippingMethod->name;
            $result['shipping_method_code'] = $shippingMethod->code;
            $result['delivery_time'] = $shippingMethod->delivery_time;
            $result['base_shipping_amount'] = 0.00;
            $result['is_free_shipping'] = true;
            $result['weight_explanation'] = "Always Free Delivery";
            if (!$shippingMethod->is_cod_allowed && $isCod) {
                $result['is_cod_allowed'] = false;
                $result['cod_rejection_reason'] = "Cash on Delivery is not available with {$shippingMethod->name}.";
                $result['cod_amount'] = 0.00;
            }
            $result['total_delivery_cost'] = round(($result['is_cod_allowed'] ? $result['cod_amount'] : 0), 2);
            $result['final_order_total'] = round($cartSubtotal + $result['total_delivery_cost'], 2);

            return $result;
        }

        // Default: weight_based
        $result = $this->calculate($cartSubtotal, $totalWeight, $paymentMethod, $setting);
        $result['shipping_method_name'] = $shippingMethod->name;
        $result['shipping_method_code'] = $shippingMethod->code;
        $result['delivery_time'] = $shippingMethod->delivery_time;

        if (!$shippingMethod->is_cod_allowed && $isCod) {
            $result['is_cod_allowed'] = false;
            $result['cod_rejection_reason'] = "Cash on Delivery is not available with {$shippingMethod->name}.";
            $result['cod_amount'] = 0.00;
            $result['total_delivery_cost'] = $result['base_shipping_amount'];
            $result['final_order_total'] = round($cartSubtotal + $result['total_delivery_cost'], 2);
        }

        return $result;
    }
}
