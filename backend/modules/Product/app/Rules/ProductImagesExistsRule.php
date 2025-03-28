<?php

declare(strict_types=1);

namespace Modules\Product\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;

class ProductImagesExistsRule implements ValidationRule
{
    public function __construct(
        private int $productId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        Log::info('r', $value);
        $images = collect($value)
            ->filter(fn(array $image) => $image['id'] && $image['id'] !== null)
            ->pluck('id');

        foreach ($images as $image) {
            if (! Str::isUuid($value)) {
                $fail('Invalid image id');

                return;
            }
        }

        $productImages = Product::query()
            ->where('id', $this->productId)
            ->first(['images']);

        if (! $productImages->images) {
            return;
        }

        $productImages = collect($productImages->images)->pluck('id');

        if ($images->diff($productImages)->isNotEmpty()) {
            $fail('Some images do not belong to this product.');
        }
    }
}