<?php

namespace App\Services;

class CommissionCalculator
{
    /**
     * @return array{
     *   technician_commission: float,
     *   owner_service_share: float,
     *   owner_items_share: float,
     *   owner_total_share: float,
     *   items_cost: float,
     *   total: float
     * }
     */
    public function calculate(
        float $subtotalItems,
        float $subtotalServices,
        float $discount = 0,
        ?float $technicianCommissionPercent = null,
        float $itemsCost = 0,
    ): array {
        $techPercent = $technicianCommissionPercent ?? (float) config('workshop.default_technician_commission_percent', 20);
        $techPercent = max(0, min(100, $techPercent));
        $ownerPercent = 100 - $techPercent;

        $itemsCost = max(0, $itemsCost);
        $technicianCommission = round($subtotalServices * ($techPercent / 100), 2);
        $ownerServiceShare = round($subtotalServices * ($ownerPercent / 100), 2);
        // Margin sparepart = harga jual − harga beli (bukan 100% omzet).
        $ownerItemsShare = round($subtotalItems - $itemsCost, 2);
        $ownerTotalShare = round($ownerServiceShare + $ownerItemsShare, 2);

        $gross = $subtotalItems + $subtotalServices;
        $discount = max(0, min($discount, $gross));
        $total = round($gross - $discount, 2);

        return [
            'technician_commission' => $technicianCommission,
            'owner_service_share' => $ownerServiceShare,
            'owner_items_share' => $ownerItemsShare,
            'owner_total_share' => $ownerTotalShare,
            'items_cost' => round($itemsCost, 2),
            'total' => $total,
        ];
    }
}
