<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Session;

class SalesByPaymentMethodChart extends ChartWidget
{
    protected static ?string $heading = 'Sales by Payment Method';
    
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';


    public ?string $filter = 'all';

    public function mount(): void
    {
        $this->filter = Session::get('payment_method_filter', 'all');
    }
    

    protected function getData(): array
    {

        
        $activeFilter = $this->filter ?? 'all';
        
        if ($activeFilter === 'all') {
            // Show both payment methods as separate lines
            $codData = Trend::query(Order::where('payment_method', 'cod'))
                ->between(
                    start: now()->subMonth(),
                    end: now(),
                )
                ->perDay()
                ->sum('total_amount');
            
            $razorpayData = Trend::query(Order::where('payment_method', 'razorpay'))
                ->between(
                    start: now()->subMonth(),
                    end: now(),
                )
                ->perDay()
                ->sum('total_amount');
            
            return [
                'datasets' => [
                    [
                        'label' => 'COD',
                        'data' => $codData->map(fn (TrendValue $value) => $value->aggregate),
                        'backgroundColor' => '#f59e0b', // Orange for COD
                        'borderColor' => '#f59e0b',
                        'fill' => false,
                    ],
                    [
                        'label' => 'Razor Pay',
                        'data' => $razorpayData->map(fn (TrendValue $value) => $value->aggregate),
                        'backgroundColor' => '#3b82f6', // Blue for Razor Pay
                        'borderColor' => '#3b82f6',
                        'fill' => false,
                    ],
                ],
                'labels' => $codData->map(fn (TrendValue $value) => $value->date),
            ];
        } else {
            // Show single payment method
            $query = Order::where('payment_method', $activeFilter);
            
            $data = Trend::query($query)
                ->between(
                    start: now()->subMonth(),
                    end: now(),
                )
                ->perDay()
                ->sum('total_amount');
            
            return [
                'datasets' => [
                    [
                        'label' => ucfirst(str_replace('_', ' ', $activeFilter)),
                        'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                        'backgroundColor' => $activeFilter === 'cod' ? '#f59e0b' : '#3b82f6',
                        'borderColor' => $activeFilter === 'cod' ? '#f59e0b' : '#3b82f6',
                        'fill' => false,
                    ],
                ],
                'labels' => $data->map(fn (TrendValue $value) => $value->date),
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'all' => 'All',
            'cod' => 'COD',
            'razorpay' => 'Razor Pay',
        ];
    }

    

    public function updatedFilter(): void
    {
        // Store the filter value in session when it changes
        Session::put('payment_method_filter', $this->filter);
        
        // Refresh the page to apply the filter to both chart and table
        $this->redirect(request()->header('Referer'));
    }

      protected function getOptions(): array
    {
        return [
            'animation' => [
                'duration' => 1500, // Animation duration in milliseconds
                'easing' => 'easeOutQuart', // Animation easing function

            ],
            'responsive' => true,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => true,
                    ],
                ],
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => true,
                    ],
                ],
            ],
        ];
    }
    
}