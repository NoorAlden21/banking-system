<?php

namespace App\Banking\Accounts\Domain\Patterns\Composite;

interface AccountComponent
{
    public function publicId(): string;
    public function type(): string;
    public function state(): string;

    /** رصيد هذا العنصر فقط (leaf = رصيده، group = اختياري) */
    public function balance(): string;

    /** رصيد إجمالي (leaf = نفسه، group = مجموع الأبناء) */
    public function totalBalance(): string;

    /** @return AccountComponent[] */
    public function children(): array;
}
