<?php

declare(strict_types=1);

namespace Modules\Order\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Cart\Dto\ProductCartDto;
use Modules\Order\Cart\Exceptions\ProductCannotBeAddedToCartException;
use Modules\Order\Cart\Http\Requests\AddToCartRequest;
use Modules\Order\Cart\Http\Resources\UserCartResource;
use Modules\Order\Cart\Services\ClientCartService;
use Modules\Product\Models\Product;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

class CartController extends Controller
{
    public function __construct(
        private ClientCartService $service,
    ) {}

    /**
     * Retrieve cart info for user.
     *
     * Usage place - Shop.
     *
     * @param  Request  $request
     * @return JsonApiResourceCollection|JsonResponse
     */
    public function getClientCart(Request $request): JsonApiResourceCollection|JsonResponse
    {
        $cart = $this->service->getCart($request->user());

        if (! $cart) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        return UserCartResource::collection(collect($cart));
    }

    /**
     * Save product to user cart.
     *
     * Usage place - Shop.
     *
     * @param  AddToCartRequest  $request
     * @return JsonResponse
     * @throws ProductCannotBeAddedToCartException
     */
    public function addProductToCart(AddToCartRequest $request): JsonResponse
    {
        $this->service->addToCart(
            user: $request->user(),
            dto: ProductCartDto::fromRequest($request),
        );

        return response()->json(['message' => 'Product was added to cart.']);
    }

    public function removeFromCart(Product $product) {}

    /**
     * Clear all cart data.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function clearCart(Request $request): JsonResponse
    {
        if ($this->service->isCartEmpty($request->user())) {
            return response()->json(['message' => 'Cart is empty.'], 404);
        }

        $this->service->clearCart($request->user());

        return response()->json(['message' => 'Cart was cleared.']);
    }
}