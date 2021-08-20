<div class="wrap-icon-section wishlist">
    <a href="#" class="link-direction">
        <i class="fa fa-heart" aria-hidden="true"></i>
        <div class="left-info">
            @if(Cart::instance('wishlist')->count() == 1)
                <span class="index">{{Cart::instance('wishlist')->count()}} item</span>
            @elseif(Cart::instance('wishlist')->count() > 1)
                <span class="index">{{Cart::instance('wishlist')->count()}} items</span>
            @else
                <span class="index">0 item</span>
            @endif
            <span class="title">Wishlist</span>
        </div>
    </a>
</div>
