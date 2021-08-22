<?php

namespace App\Http\Livewire;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Cart;

class CartComponent extends Component
{
    public $haveCouponCode;
    public $couponCode;
    public $discount;
    public $subtotalAfterDiscount;
    public $taxAfterDiscount;
    public $totalAfterDiscount;
    public $fake = null;
    public $fakeDiscount;

    public function increaseQuantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);

        $qty = $product->qty + 1;

        Cart::update($rowId, $qty);

        $this->emitTo('cart-count-component', 'refreshComponent');
    }

    public function decreaseQuantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);

        $qty = $product->qty - 1;

        Cart::instance('cart')->update($rowId, $qty);

        $this->emitTo('cart-count-component', 'refreshComponent');
    }

    public function destroy($rowId)
    {
        Cart::instance('cart')->remove($rowId);
        $this->emitTo('cart-count-component', 'refreshComponent');
        session()->flash('success_message', 'Item has been removed');
    }

    public function destroyAll()
    {
        Cart::instance('cart')->destroy();
        $this->emitTo('cart-count-component', 'refreshComponent');
        session()->flash('success_message', 'All Items has been removed');
    }

    public function switchToSaveForLater($rowId)
    {
        $item = Cart::instance('cart')->get($rowId);
        Cart::instance('cart')->remove($rowId);
        Cart::instance('saveForLater')->add($item->id, $item->name, 1, $item->price)->associate('App\Models\Product');
        $this->emitTo('cart-count-component', 'refreshComponent');
        session()->flash('success_message', 'Item has been saved for later !!!');
    }

    public function moveToCart($rowId)
    {
        $item = Cart::instance('saveForLater')->get($rowId);
        Cart::instance('saveForLater')->remove($rowId);
        Cart::instance('cart')->add($item->id, $item->name, 1, $item->price)->associate('App\Models\Product');
        $this->emitTo('cart-count-component', 'refreshComponent');
        session()->flash('s_success_message', 'Item has been move to cart !!!');
    }

    public function deleteFromSaveForLater($rowId)
    {
        Cart::instance('saveForLater')->remove($rowId);
        session()->flash('s_success_message', 'Item has been removed from save for later !!!');
    }

    public function applyCouponCode()
    {
        $coupon = Coupon::where('code', $this->couponCode)->where('expiry_date','>=',Carbon::today())->where('cart_value', '<=', Cart::instance('cart')->subtotal())->first();

        if (!$coupon) {
            session()->flash('coupon_message', 'Coupon code is invalid !!!');
            return;
        }

        session()->put('coupon', [
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'cart_value' => $coupon->cart_value
        ]);

        $this->fake = [
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'cart_value' => $coupon->cart_value
        ];

        $this->fakeDiscount = $coupon->value;

        if (session()->get('coupon')['type'] == 'fixed') {
            $this->discount = session()->get('coupon')['value'];
        } else {
            $this->discount = (Cart::instance('cart')->subtotal() * session()->get('coupon')['value']) / 100;
        }
        session()->put('discount', $this->discount);
        $this->subtotalAfterDiscount = Cart::instance('cart')->subtotal() - $this->discount;
        session()->put('subtotalAfterDiscount', $this->subtotalAfterDiscount);
        $this->taxAfterDiscount = ($this->subtotalAfterDiscount * config('cart.tax')) / 100;
        session()->put('taxAfterDiscount', $this->taxAfterDiscount);
        $this->totalAfterDiscount = $this->subtotalAfterDiscount + $this->taxAfterDiscount;
        session()->put('totalAfterDiscount', $this->totalAfterDiscount);
    }


    public function removeCoupon()
    {
        session()->forget('coupon');
    }

    public function checkout(){
        if(Auth::check()){
            return redirect()->route('checkout');
        }else{
            return redirect()->route('login');
        }
    }

    public function setAmountForCheckout(){
        if(!Cart::instance('cart')->count() > 0){
            session()->forget('checkout');
            return;
        }
        if(session()->has('coupon')){
            session()->put('checkout', [
                'discount' => $this->discount,
                'subtotal' => $this->subtotalAfterDiscount,
                'tax' => $this->taxAfterDiscount,
                'total' => $this->totalAfterDiscount
            ]);
        }else{
            session()->put('checkout', [
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax' => Cart::instance('cart')->tax(),
                'total' => Cart::instance('cart')->total()
            ]);
        }
    }

    public function render()
    {
        if (session()->has('coupon')) {
            $this->fake = Session::get('coupon');
        }

        $this->discount = session()->get('discount');
        $this->subtotalAfterDiscount = session()->get('subtotalAfterDiscount');
        $this->taxAfterDiscount = session()->get('taxAfterDiscount');
        $this->totalAfterDiscount = session()->get('totalAfterDiscount');

        $this->setAmountForCheckout();

        return view('livewire.cart-component')->layout("layouts.base");
    }
}
