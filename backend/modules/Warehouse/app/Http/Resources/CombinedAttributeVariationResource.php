<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Modules\Warehouse\Models\ProductAttribute;
use TiMacDonald\JsonApi\JsonApiResource;

class CombinedAttributeVariationResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'attributes' => $this->productAttributes->map(function (ProductAttribute $attribute) {
                $attributes = [
                    'id' => $attribute->pivot->attribute_id,
                    'value' => $attribute->pivot->value,
                    'name' => $attribute->name,
                    'type' => [
                        'id' => $attribute->type->value,
                        'name' => $attribute->type->toTypes(),
                    ],
                ];

                if ($attribute->view_type) {
                    $attributes['attribute_view_type'] = $attribute->view_type->value;
                }

                return $attributes;
            }),
        ];
    }

    public function toType(Request $request): string
    {
        return 'variations';
    }
}