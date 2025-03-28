<template>
  <router-link
      @click.prevent="useScrolling().scrollToTop()"
      class="w-272 h-auto flex flex-col gap-4 group hover:shadow-md rounded-md p-3 cursor-pointer transition-all duration-200"
      :to="`/shop/products/${product.id}/${product.slug}`"
  >
    <div class="relative overflow-hidden">
      <img
          :src="product.image"
          class="rounded"
          :alt="product.title"
      />
      <button
          @click.prevent="addToWishlist"
          :class="{ active: isInWishlist }"
          class="wishlist w-9 h-9 rounded-full flex items-center justify-center absolute top-3 right-3 bg-white"
      >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="size-6"
        >
          <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"
          />
        </svg>
      </button>
      <button
          @click.prevent="addToCart"
          :class="{ 'bg-[#db4444]': isInCart, 'bg-black': !isInCart }"
          class="absolute bottom-0 left-0 w-full text-center py-2 text-white opacity-0 translate-y-full transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100"
      >
        {{ isInCart ? "Remove From Cart" : "Add To Cart" }}
      </button>
    </div>
    <div class="flex flex-col space-y-2">
      <p class="font-medium">{{ truncatedTitle }}</p>
      <p class="font-medium">{{ '$' + product.price }}</p>
      <star-rating :rating="4" :review-number="50"/>
    </div>
  </router-link>
</template>

<script setup>
import StarRating from "@/components/Card/StarRating.vue";
import {computed} from "vue";
import {useAddToWishlist} from "@/composables/useAddToWishlist.js";
import {useAddToCart} from "@/composables/useAddToCart.js";
import {useScrolling} from "@/composables/useScrolling.js";

const props = defineProps({
  product: Object
});

const {isInWishlist, addToWishlist} = useAddToWishlist();

const {isInCart, addToCart} = useAddToCart();

const truncatedTitle = computed(() => {
  const title = props.product?.title || "";
  return title.length > 25 ? title.substring(0, 25) + "..." : title;
});
</script>

<style scoped>
.wishlist svg {
  stroke: currentColor;
}

.wishlist.active svg {
  fill: red;
  stroke: red;
}
</style>
