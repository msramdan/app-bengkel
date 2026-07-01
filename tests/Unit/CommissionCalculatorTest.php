<?php

namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    #[Test]
    public function it_splits_service_revenue_using_technician_commission_percent(): void
    {
        $calc = new CommissionCalculator;

        $result = $calc->calculate(0, 100000, 0, 20);

        $this->assertSame(20000.0, $result['technician_commission']);
        $this->assertSame(80000.0, $result['owner_service_share']);
        $this->assertSame(0.0, $result['owner_items_share']);
        $this->assertSame(80000.0, $result['owner_total_share']);
        $this->assertSame(100000.0, $result['total']);
    }

    #[Test]
    public function it_uses_config_default_when_percent_not_given(): void
    {
        $calc = new CommissionCalculator;

        $result = $calc->calculate(0, 100000);

        $this->assertSame(20000.0, $result['technician_commission']);
        $this->assertSame(80000.0, $result['owner_service_share']);
    }

    #[Test]
    public function sparepart_revenue_goes_entirely_to_owner(): void
    {
        $calc = new CommissionCalculator;

        $result = $calc->calculate(150000, 0);

        $this->assertSame(0.0, $result['technician_commission']);
        $this->assertSame(0.0, $result['owner_service_share']);
        $this->assertSame(150000.0, $result['owner_items_share']);
        $this->assertSame(150000.0, $result['owner_total_share']);
    }

    #[Test]
    public function combined_transaction_calculates_commission_only_from_services(): void
    {
        $calc = new CommissionCalculator;

        $result = $calc->calculate(65000, 75000, 5000, 20);

        $this->assertSame(15000.0, $result['technician_commission']);
        $this->assertSame(60000.0, $result['owner_service_share']);
        $this->assertSame(65000.0, $result['owner_items_share']);
        $this->assertSame(125000.0, $result['owner_total_share']);
        $this->assertSame(135000.0, $result['total']);
    }

    #[Test]
    public function discount_cannot_exceed_gross_total(): void
    {
        $calc = new CommissionCalculator;

        $result = $calc->calculate(50000, 50000, 999999);

        $this->assertSame(0.0, $result['total']);
    }
}
