<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    )
    {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param Merchant $merchant
     * @param string $email
     * @param string $name
     * @param float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        $user = User::query()->where('email', $email)->first();

        if ($user) {

            if ($user->type == USER::TYPE_MERCHANT) {
                throw new AffiliateCreateException();
            }

            $affiliate = Affiliate::query()->where('merchant_id', $merchant->id)->first();
        } else {
            $user = User::query()->create([
                'name'     => $name,
                'email'    => $email,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'type'     => User::TYPE_AFFILIATE,
            ]);

            $discountCode = $this->apiService->createDiscountCode($merchant);
            /* @var Affiliate $affiliate */
            $affiliate = Affiliate::query()->create([
                'user_id'         => $user->id,
                'merchant_id'     => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code'   => $discountCode['code'],
            ]);
        }

        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
