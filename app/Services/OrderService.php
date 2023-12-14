<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

class OrderService
{
    use WithFaker;

    public function __construct(
        protected AffiliateService $affiliateService
    )
    {
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method
        $order = Order::query()->where('external_order_id', $data['order_id'])->first();
        if (!$order) {
            $affiliate = Affiliate::with('merchant')->where('discount_code', $data['discount_code'])->first();
            $merchant = Merchant::query()->where('domain', $data['merchant_domain'])->first();
            if (!$merchant) {
                $user = User::query()->create([
                    'name'     => $this->faker->name(),
                    'email'    => $this->faker->email(),
                    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                    'type'     => User::TYPE_MERCHANT,
                ]);
                $merchant = Merchant::query()->create([
                    'user_id'      => $user->id,
                    'domain'       => $data['merchant_domain'],
                    'display_name' => $this->faker->name()
                ]);
            }

            $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);

            $commissionOwed = $data['subtotal_price'] * $affiliate->commission_rate;
            
            $order = Order::create([
                'subtotal'          => $data['subtotal_price'],
                'affiliate_id'      => $affiliate->id,
                'merchant_id'       => $merchant->id,
                'commission_owed'   => $commissionOwed,
                'external_order_id' => $data['order_id'],
            ]);

            return $order;
        }
        return null;
    }
}
