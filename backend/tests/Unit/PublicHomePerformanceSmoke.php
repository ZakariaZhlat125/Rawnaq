<?php

// Runs without a database: php tests/Unit/PublicHomePerformanceSmoke.php
require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariation;
use Modules\Product\Transformers\ProductVariationResource;

$product = new Product();
$product->id = 12;
$product->discount_start_date = time() - 3600;
$product->discount_end_date = time() + 3600;
$product->discount_type = 'percent';
$product->discount_value = 20;

$variation = new ProductVariation();
$variation->id = 34;
$variation->product_id = 12;
$variation->price = 100;
$variation->setRelation('product', $product);
$variation->setRelation('product_variation_stock', null);
$variation->setRelation('combinations', collect());

$result = (new ProductVariationResource($variation))->toArray(Request::create('/api/Home/all'));
if ($result['discounted_product_price'] !== 80.0) {
    throw new RuntimeException('Product discount changed while removing redundant queries');
}
if ((float) getDiscountedProductPrice(50, 12, $product) !== 40.0) {
    throw new RuntimeException('A loaded product should be enough to compute its price');
}
echo "Loaded product discount checks passed.\n";
