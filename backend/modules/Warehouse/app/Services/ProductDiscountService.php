<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Warehouse\Contracts\DiscountRelationInterface;
use Modules\Warehouse\DTO\ProductDiscountDto as DiscountDto;
use Modules\Warehouse\Http\Exceptions\CannotAddDiscountToProductWithoutPriceException;
use Modules\Warehouse\Http\Exceptions\VariationToApplyDiscountDoesNotExists;
use Modules\Warehouse\Models\Discount;

class ProductDiscountService
{
    public function __construct(
        protected WarehouseProductInfoService $warehouseService,
    ) {}

    public function getProductWithDiscounts(int $productId): Product
    {
        $product = $this->warehouseService->getWarehouseInfoAboutProduct($productId);

        if (is_null($product->hasCombinedAttributes())) {
            $product->load('lastDiscount');

            return $product;
        }

        $product->productAttributes->load('lastDiscount');

        return $product;
    }

    public function addDiscount(Product $product, DiscountDto $dto): void
    {
        if (is_null($product->hasCombinedAttributes())) {
            $this->forProductWithoutVariations($product, $dto);

            return;
        }

        if ($product->hasCombinedAttributes()) {
            $this->forProductWithComboVariations($product, $dto);

            return;
        }

        $this->forProductWithSingleVariation($product, $dto);
    }

    /**
     * Add discount for product with single type of variations.
     *
     * @param  Product  $product
     * @param  DiscountDto  $dto
     * @return void
     * @throws VariationToApplyDiscountDoesNotExists
     */
    protected function forProductWithSingleVariation(Product $product, DiscountDto $dto): void
    {
        if (! $product->relationLoaded('singleAttributes')) {
            $product->load('singleAttributes');
        }

        $variation = $product->singleAttributes->where('id', $dto->variationId)->first();

        if (! $variation) {
            throw new VariationToApplyDiscountDoesNotExists();
        }

        $this->createDiscount(
            relation: $variation,
            dto: $dto,
            oldPrice: (float) $variation->getRawOriginal('price'),
        );
    }

    /**
     * Add discount for product with combined type of variations.
     *
     * @param  Product  $product
     * @param  DiscountDto  $dto
     * @return void
     * @throws VariationToApplyDiscountDoesNotExists
     */
    protected function forProductWithComboVariations(Product $product, DiscountDto $dto): void
    {
        if (! $product->relationLoaded('combinedAttributes')) {
            $product->load('combinedAttributes');
        }

        $variation = $product->combinedAttributes->where('id', $dto->variationId)->first();

        if (! $variation) {
            throw new VariationToApplyDiscountDoesNotExists();
        }

        $this->createDiscount(
            relation: $variation,
            dto: $dto,
            oldPrice: (float) $variation->getRawOriginal('price'),
        );
    }

    /**
     * Add discount for product, that does not have variations.
     *
     * @param  Product  $product
     * @param  DiscountDto  $dto
     * @return void
     */
    protected function forProductWithoutVariations(Product $product, DiscountDto $dto): void
    {
        if (! $product->relationLoaded('warehouse')) {
            $product->load('warehouse');
        }

        $this->createDiscount(
            relation: $product,
            dto: $dto,
            oldPrice: (float) $product->warehouse->getRawOriginal('default_price'),
        );
    }

    protected function createDiscount(DiscountRelationInterface $relation, DiscountDto $dto, float $oldPrice): void
    {
        DB::transaction(function () use ($relation, $dto, $oldPrice) {
            $discount = Discount::query()->create([
                'percentage' => $dto->percentage,
                'original_price' => $oldPrice,
                'discount_price' => $this->calculateDiscountPrice($oldPrice, $dto),
                'start_date' => $dto->startFrom,
                'end_date' => $dto->endAt,
            ]);

            $relation->discount()->attach($discount);
        });
    }

    protected function calculateDiscountPrice(float $originalPrice, DiscountDto $dto): float
    {
        if ((int) $originalPrice === 0) {
            throw new CannotAddDiscountToProductWithoutPriceException();
        }

        return round(
            num: $originalPrice * (1 - ($dto->percentage / 100)),
            precision: 2,
        );
    }
}