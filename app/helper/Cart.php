<?php


namespace App\helper;


use App\Models\Customer\CustomerCart;
use App\Models\Customer\CustomerCartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSaleInfo;
use App\Models\PromoCode;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use JsonSerializable;


class Cart implements Arrayable, Jsonable, JsonSerializable {

    public $cart;
    protected $defaultShopCart = null;
    protected $cartShop;
    protected $errors = [];
    protected $status = true;

    /**
     * Create a new service provider instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->shop();
    }

    public function shop($shop_id = null) {
        $this->cartShop = $shop_id;
        $this->init();
        return $this;
    }

    protected function init() {
        $this->cart = CustomerCart::where('user_id', $this->getUser()->id())->where('shop_id', $this->cartShop)->first();
        if (!$this->cart) {

            $this->create();
            return true;
        }

        return false;
    }

    public function getUser() {
        $guard = request()->wantsJson() ? 'sanctum' : 'web';
        return auth()->guard($guard);
    }

    protected function create() {

        $cartData = [
            'shop_id' => $this->cartShop
        ];

        if ($this->getUser()->check()) {
            $user = $this->getUser()->user();
            $cartData['user_id'] = $this->getUser()->id();
            $cartData['first_name'] = $user->first_name;
            $cartData['last_name'] = $user->last_name;
            $cartData['mobile'] = $user->mobile;
            $this->cart = CustomerCart::create($cartData);
        } else {
            $cartData['is_guest'] = 1;
        }

        if (!$this->cart) {
            return false;
        }

        return true;
    }

    /**
     * Create a new service provider instance.
     *
     * @return CustomerCartItem|false
     */
    public function addProduct($product_id, $variant_id, $quantity = 1) {

        $product = Product::where('status', Product::APPROVED)
            ->where('id', $product_id)->where('shop_id', $this->cartShop)->first();

        if (!$product) {
            $this->addErrorMessage("محصولی یافت نشد");
            return false;
        }

        $variant = $product->inventories()
            ->where('product_variant_id', $variant_id)
            ->where('quantity', '>', 0)->oldest()->get();

        if (!$variant->count() > 0) {
            $this->addErrorMessage("محصولی با نام {$product->template->name} یافت نشد");
            return false;
        }
        $sum_quantity = $variant->sum('quantity');
        if (!($sum_quantity >= $quantity)) {
            $this->addErrorMessage("موجودی محصول {$product->template->name} کافی نمی باشد");
            return false;
        }

        $cart_item = CustomerCartItem::where('product_id', $product_id)->where('product_variant_id', $variant_id)->first();

        if ($cart_item) {
            if (($cart_item->quantity + $quantity > $sum_quantity)) {
                $this->addErrorMessage("موجودی محصول {$product->template->name} کافی نمی باشد");
                return false;
            }
            $cart_item->increment('quantity', $quantity);
        } else {
            $cart_item = new CustomerCartItem([
                'product_id' => $product_id,
                'product_variant_id' => $variant_id,
                'quantity' => $quantity
            ]);
        }
        if ($this->cart->items()->save($cart_item)) {
            return $this->items();
        }

        return false;
    }

    protected function addErrorMessage($message) {
        $this->errors[] = $message;
    }

    /**
     * get Customer Cart
     *
     * @return CustomerCartItem
     */
    public function items() {
        return $this->getCart()->items;
    }

    /**
     * get Customer Cart
     *
     * @return CustomerCart
     */
    public function getCart() {
        return $this->cart;
    }

    public function removeProduct($cart_item_id) {
        return $this->getCart()->items()->where('id', $cart_item_id)->delete();
    }

    public function increaseProduct($cart_item_id, $quantity = 1) {
        $item = $this->getCart()->items()->where('id', $cart_item_id)->first();
        if ($item) {
            $this->addProduct($item->product_id, $item->product_variant_id, $quantity);
            return true;
        } else {
            $this->addErrorMessage('این آیتم در سبد شما وجود ندارد');
        }
        return false;
    }

    public function decreaseProduct($cart_item_id, $quantity = 1) {
        $item = $this->getCart()->items()->where('id', $cart_item_id)->first();
        if ($item) {
            if ($item->quantity - $quantity > 0) {
                return $item->decrement('quantity', $quantity);
            } else {
                return $item->delete();
            }
        }
        $this->addErrorMessage('محصولی با این عنوان یافت نشد.');
        return false;
    }

    public function address($address) {
        return Cache::put($this->getAddressCacheName(), $address);
    }

    public function getAddressCacheName() {
        return 'cart_order_address_user_' . $this->getUser()->id();
    }

    public function payment_type($type) {
        /*
         * @var $type
         *  $type == 1 then online
         *  $type == 0 then offline
         */
        Cache::put($this->getPaymentTypeCacheName(), $type);
        return true;
    }

    public function getPaymentTypeCacheName() {
        return 'cart_order_payment_type_user_' . $this->getUser()->id();
    }

    public function checkout() {
        $carts = CustomerCart::with('items')->where('user_id', $this->getUser()->id())->get();
        $order = new Order([
            'user_id' => $this->getUser()->id(),
            'address_id' => Cache::get($this->getAddressCacheName())->id,
            'payment_type' => Cache::get($this->getPaymentTypeCacheName(), 0),
            'status' => 0
        ]);

        $orderItems = collect();
        foreach ($carts as $cart) {
            if ($cart->items->count() > 0) {
                foreach ($cart->items as $item) {
                    $all_item_quantity = $item->quantity;
                    $saleInfo = ProductSaleInfo::where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)->oldest()->get();
                    $sum_quantity = $saleInfo->sum('quantity');
                    if (!($sum_quantity >= $item->quantity)) {
                        $this->addErrorMessage("موجودی محصول {$item->product->template->title} کافی نمی باشد");
                        break;
                    }

                    if ($saleInfo->count() > 0) {
                        foreach ($saleInfo as $info) {
                            $orderItem = new OrderItem([
                                'shop_id' => $cart->shop_id,
                                'product_id' => $info->product_id,
                                'product_variant_id' => $info->product_variant_id
                            ]);
                            if (($all_item_quantity - $info->quantity) > 0) {
                                $all_item_quantity -= $info->quantity;
                                if ($info->sale_price
                                    && (is_null($info->sale_start) || (new Carbon($info->getRawOriginal('sale_start')))->isPast())
                                    && (is_null($info->sale_end) || (new Carbon($info->getRawOriginal('sale_end')))->isFuture())) {
                                    $orderItem->price = $info->sale_price * $info->quantity;
                                } else {
                                    $orderItem->price = ($info->price * $info->quantity);
                                }
                                $orderItem->quantity = $info->quantity;
                                $orderItem->total_price = $orderItem->price * $orderItem->quantity;
                                $info->quantity = 0;
                                $info->save();
                            } else {
                                if ($info->sale_price
                                    && (is_null($info->sale_start) || (new Carbon($info->getRawOriginal('sale_start')))->isPast())
                                    && (is_null($info->sale_end) || (new Carbon($info->getRawOriginal('sale_end')))->isFuture())) {
                                    $orderItem->price = $info->sale_price * $all_item_quantity;
                                } else {
                                    $orderItem->price = ($info->price * $all_item_quantity);
                                }
                                $orderItem->quantity = $all_item_quantity;
                                $orderItem->total_price = $orderItem->price * $orderItem->quantity;
                                $info->quantity -= $all_item_quantity;
                                $info->save();
                            }
                            $orderItems[] = $orderItem;
                        }
                    }


                }
            } else {
                $cart->delete();
            }

        }
        $order->total_price = $orderItems->sum('total_price');
        $order->save();
        $order->items()->saveMany($orderItems);

        return $order;
    }

    public function __toString() {
        return $this->toJson();
    }

    /**
     * Convert the Cart instance to JSON.
     *
     * @param int $options
     * @return string
     *
     * @throws Exception
     */
    public function toJson($options = 0) {
        $json = json_encode($this->jsonSerialize(), $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(json_last_error_msg());
        }

        return $json;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        if ($this->cartHasError()) {
            return [
                'status' => false,
                'errors' => $this->getErrorMessages(),
                'cart' => $this->calculateCart()
            ];
        } else {
            return [
                'status' => true,
                'cart' => $this->calculateCart()
            ];
        }

    }

    public function cartHasError() {
        return count($this->errors) > 0;
    }

    public function getErrorMessages() {
        return $this->errors;
    }

    public function calculateCart() {
        $cart = $this->getCart();
        $total = 0;
        $item_count = 0;
        $cart_items = [];
        if ($items = $cart->items()->get()) {
            foreach ($items as $item) {
                $all_item_quantity = $item->quantity;

                $saleInfo = ProductSaleInfo::where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)->oldest()->get();

                $sum_quantity = $saleInfo->sum('quantity');
                if (!($sum_quantity >= $item->quantity)) {
                    $this->addErrorMessage("موجودی محصول {$item->product->template->title} کافی نمی باشد");
                    continue;
                }
                $cart_items[$item->product_id]['image'] = $item->product->template->properties()->with(['property' => function ($q) {
                    $q->where('code', 'main_image')->first();
                }])->first();
                $cart_items[$item->product_id]['product_id'] = $item->product_id;
                $cart_items[$item->product_id]['cart_item_id'] = $item->id;
                $cart_items[$item->product_id]['name'] = $item->product->template->title;
                $cart_items[$item->product_id]['variants'][$item->product_variant_id]['attributes'] = [];
                foreach ($item->productVariant->super_attributes as $attribute) {
                    $cart_items[$item->product_id]['variants'][$item->product_variant_id]['attributes'][] = [
                        'name' => $attribute->property->name,
                        'type' => $attribute->property->type,
                        'value' => $attribute->property->options->firstWhere('id', $attribute->value)->value,
                    ];
                }

                if ($saleInfo->count() > 0) {
                    foreach ($saleInfo as $info) {
                        if (($all_item_quantity - $info->quantity) > 0) {
                            $all_item_quantity -= $info->quantity;
                            $cart_items[$info->product_id]['variants'][$info->product_variant_id]['items'][] = [
                                'product_id' => $info->product_id,
                                'product_variant_id' => $info->product_variant_id,
                                'price' => $info->price,
                                'price_format' => number_format($info->price),
                                'sale_price' => $info->sale_price,
                                'quantity' => $info->quantity
                            ];
                            if ($info->sale_price
                                && (is_null($info->sale_start) || (new Carbon($info->getRawOriginal('sale_start')))->isPast())
                                && (is_null($info->sale_end) || (new Carbon($info->getRawOriginal('sale_end')))->isFuture())) {
                                $total += $info->sale_price * $info->quantity;
                            } else {
                                $total += ($info->price * $info->quantity);
                            }
                            $item_count += $info->quantity;
                            continue;
                        } else {
                            $cart_items[$info->product_id]['variants'][$info->product_variant_id]['items'][] = [
                                'product_id' => $info->product_id,
                                'product_variant_id' => $info->product_variant_id,
                                'price' => $info->price,
                                'price_format' => number_format($info->price),
                                'quantity' => $all_item_quantity
                            ];
                            if ($info->sale_price
                                && (is_null($info->sale_start) || (new Carbon($info->getRawOriginal('sale_start')))->isPast())
                                && (is_null($info->sale_end) || (new Carbon($info->getRawOriginal('sale_end')))->isFuture())) {
                                $total += $info->sale_price * $all_item_quantity;
                            } else {
                                $total += ($info->price * $all_item_quantity);
                            }
                            $item_count += $all_item_quantity;
                            break;
                        }
                    }
                }
            }

            return [
                'products' => $cart_items,
                'total' => $total,
                'total_format' => number_format($total) . ' تومان',
                'product_count' => count($cart_items),
                'item_count' => $item_count
            ];

        }

    }

    public function addPromoCode($code) {
        $promoCode = PromoCode::find($code);
        if ($promoCode) {

        }

        return false;
    }

    protected function setStatusFail() {
        $this->status = false;
    }

    protected function setStatusSuccess() {
        $this->status = true;
    }

    protected function emptyErrors() {
        $this->errors = [];
    }
}
