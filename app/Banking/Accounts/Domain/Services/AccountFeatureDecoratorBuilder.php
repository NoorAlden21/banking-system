<?php

namespace App\Banking\Accounts\Domain\Services;

use App\Banking\Accounts\Domain\Entities\AccountFeature;
use App\Banking\Accounts\Domain\Patterns\Decorator\AccountFeatureComponent;
use App\Banking\Accounts\Domain\Patterns\Decorator\OverdraftProtectionDecorator;
use App\Banking\Accounts\Domain\Patterns\Decorator\PremiumServicesDecorator;
use App\Banking\Accounts\Domain\Patterns\Decorator\InsuranceDecorator;

final class AccountFeatureDecoratorBuilder
{
    /**
     * @param array<int, AccountFeature> $features
     */
    public function apply(AccountFeatureComponent $base, array $features): AccountFeatureComponent
    {
        $decorated = $base;

        foreach ($features as $f) {
            if ($f->status !== 'active') continue;

            $decorated = match ($f->featureKey) {
                'overdraft' => new OverdraftProtectionDecorator(
                    $decorated,
                    (string) ($f->meta['limit'] ?? '0.00')
                ),

                'premium' => new PremiumServicesDecorator(
                    $decorated,
                    (string) ($f->meta['fee_rate_percent'] ?? '0.50') // default 0.5%
                ),

                'insurance' => new InsuranceDecorator(
                    $decorated,
                    (string) ($f->meta['monthly_fee'] ?? '10.00')
                ),

                default => $decorated,
            };
        }

        return $decorated;
    }
}
