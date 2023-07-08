<?php

namespace App\Http\Controllers\v2;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JwtAuth;
use Validator;
use App\Models\customerReview;
use App\Models\productAttributeValueMap;
use App\Models\productAttribute;

use App\Models\productAttributeValue;
use App\Models\productVariation;
use App\Models\variationAttributeMap;
use App\Models\productCategory;
use App\Models\Discount;

class ProductController extends Controller
{




   /*
    |--------------------------------------------------------------------------
    | Public Function get_product_listing
    |--------------------------------------------------------------------------
    |
    */
    public function getProductListing(Request $request){

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }
            $search_query  =$request ->search_query;
            $franchise     = $request ->franchise;
            $category_slug = $request ->category;
            $limit         = ($request->limit == null) ? 12 : $request->limit; //number of viewable items  in one page
            $sort          = ($request->sort  == null) ? 'order_desc' : $request->sort;
            $filter_price  = $request ->filter_price;

            $query=$this->get_product_query($franchise);

            if($search_query) {
                $query = $query->where('title','LIKE','%'.$search_query.'%');
            }

            /**product list sorting if sort exist*/
            if(isset($sort)){$query=$this->get_sort_listing($sort, $query);}

            /**product list filter if category slug exist*/
            if(isset($category_slug)){
                $query->join('product_category_map', 'product_category_map.product', '=', 'product.id')
                      ->join('product_category', 'product_category_map.product_category', '=', 'product_category.id')
                      ->where('product_category.slug', $category_slug);
            }

            $query = $query->whereExists(function($q)
            
            {
                $q->select(DB::raw(1))
                      ->from('product_images')
                      ->whereRaw('product_images.product = product.id');
            });

            /**product list filter if filter price exist*/
            $min_price = $query->min('franchise_product_map.price');
            $max_price = $query->max('franchise_product_map.price');

            if(isset($filter_price)){
                $explode_filter_price1 = array_map('intval', explode('-', $filter_price ));
                $request_min_price = $explode_filter_price1[0];
                $request_max_price = $explode_filter_price1[1];
                $query->whereBetween('franchise_product_map.price',[$request_min_price,$request_max_price] );
            }


            $root = (isset($category_slug)) ? false : true;

            /*get all filters of shop page */
            $filters = $this->get_all_filters($category_slug, $root,$min_price,$max_price,$filter_price,$request);

            /**product list filter if attribute filter exist*/
            $attribute_values=array();
            $SerializedFilterValues  = array();
            if (!empty($filter_price)) {
                $SerializedFilterValues['price'] = $filter_price;
            }
            foreach ($filters as $key => $filter) {
                if ($filter['slug']!='categories' && $filter['slug']!='price') {
                    $attribute_values = array_merge($attribute_values,$filter['value']);
                    if (!empty($filter['value'])) {
                        $SerializedFilterValues[$filter['slug']] = $request ->{'filter_'.$filter['slug']};
                    }
                }
            }

            if (!empty($attribute_values)) {
                $query->join('product_attribute_value_map', 'product_attribute_value_map.product', '=', 'product.id')
                      ->join('product_attribute_value', 'product_attribute_value.id', '=', 'product_attribute_value_map.product_attribute_value')
                      ->whereIn('product_attribute_value.slug',$attribute_values);
            }

            $product_list = $query->paginate($limit);

            $allproduct = array();

            foreach ($product_list as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }


            $list = array(
                'items'       => $allproduct,
                'page'        => $product_list->currentPage(),
                'limit'       => (int)$limit,
                'sort'        => $sort ,
                'total'       => $product_list->total(),
                'pages'       => $product_list->lastPage(),
                'from'        => ($product_list->firstItem()) ? $product_list->firstItem() : 0 ,
                'to'          => ($product_list->lastItem()) ? $product_list->lastItem() : 0 ,
                'filters'     => $filters,
                'filterValues'=> $SerializedFilterValues
            );

            if($query){
                return response(['success' => true,'data'=> $list,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }


        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Private function / get search data V2
    |--------------------------------------------------------------------------
    */
    private function getSearchData(Request $request){

        return 0;

    }






   /*
    |--------------------------------------------------------------------------
    | Public Function get_product
    |--------------------------------------------------------------------------
    |
    */
    public function getProduct(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('slug')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. slug is required.",], 500);
            }

            $product=$this->get_product_query($request ->franchise)->where('slug', $request ->slug)->first();

            $product_array = $this->site_product($product);

            if($product){
                return response(['success' => true,'data'=> $product_array,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }







    /*
    |--------------------------------------------------------------------------
    | Public Function get_bestsellers
    |--------------------------------------------------------------------------
    |
    */
    public function getBestsellers(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $limit     = ($request->limit == null) ? 5 : $request->limit;
            $franchise = $request ->franchise;

            $query = DB::table('order')
                        ->join  ('order_items', 'order.id', '=', 'order_items.order')
                        ->join  ('product', 'product.id', '=', 'order_items.product')
                        ->where('franchise', $franchise)
                        ->where('product.status', 'published')
                        ->whereNotNull('product.cover_image')
                        ->whereDate('order.created_date', '>', Carbon::now()->subDays(31))
                        ->select('order_items.product', DB::raw('COUNT(order_items.product) AS occurrences'))
                        ->groupBy('order_items.product')
                        ->orderBy('occurrences', 'DESC')
                        ->limit($limit)
                        ->get();

            $allproduct=array();

            foreach ($query as $key => $product) {

                $product_query =$this->get_product_query($franchise)->where('product.id', $product->product)->first();
                if($product_query){
                    $allproduct[$key] = $this->site_product($product_query);
                }
            }

            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }



    }
    /*
    |--------------------------------------------------------------------------
    | Public FunctionSpecialOffers
    |--------------------------------------------------------------------------
    |
    */
    public function getSpecialOffers(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $limit     = ($request->limit == null) ? 5 : $request->limit;
            $franchise = $request ->franchise;

            $query=$this->get_product_query($franchise)->where('product.old_price', '!=' , 0 );

            $product_list = $query->limit($limit)->get();

            $allproduct = array();

            foreach ($product_list as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }

            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }







    /*
    |--------------------------------------------------------------------------
    | Public Function get_related_products
    |--------------------------------------------------------------------------
    |
    */
    public function getRelatedProductsOld(Request $request)
    {

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('slug')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. slug is required.",], 500);
            }

            $query = DB::table('product')
                        ->join  ('product_category_map', 'product.id', '=', 'product_category_map.product')
                        ->join  ('product_category', 'product_category.id', '=', 'product_category_map.product_category')
                        ->whereNotNull('product_category.category_parent')
                        ->where('product.slug', $request->slug)
                        ->select('product_category_map.product_category')
                        ->get();
            $test =array();

            foreach ($query as $key => $product) {
                $test[$key] = $product->product_category;
            }

            $query2 = DB::table('product_category_map')
                        ->where('product_category_map.product_category', $test)
                        ->select('product_category_map.product')
                        ->limit(8)
                        ->get();

            $allproduct=array();
            foreach ($query2 as $key => $product) {

                $product_query =$this->get_product_query($request ->franchise)->where('product.id', $product->product)->first();

                $allproduct[$key] = $this->site_product($product_query);

            }

            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }



    public function getRelatedProducts(Request $request){

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('slug')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. slug is required.",], 500);
            }

            $product_categories = DB::table('product_category')
                        ->join  ('product_category_map', 'product_category.id', '=', 'product_category_map.product_category')
                        ->join  ('product', 'product.id', '=', 'product_category_map.product')
                        ->join  ('franchise_category_map', 'product_category.id', '=', 'franchise_category_map.product_category')
                        ->where('product.slug', $request->slug)
                        ->where('franchise_category_map.franchise', $request->franchise)
                        ->pluck('product_category.id');


            $query = DB::table('product')
                        ->join  ('product_category_map', 'product.id', '=', 'product_category_map.product')
                        ->join  ('product_category', 'product_category.id', '=', 'product_category_map.product_category')
                        ->join  ('franchise_product_map', 'product.id', '=', 'franchise_product_map.product')
                        ->whereIn('product_category.id', $product_categories)
                        ->where('franchise_product_map.franchise', $request->franchise)
                        ->where('product.status', 'published')
                        ->distinct()
                        ->limit(8)
                        ->inRandomOrder()
                        ->pluck('product.id');

            $allproduct=array();

            foreach ($query as $key => $product) {

                $product_query =$this->get_product_query($request ->franchise)->where('product.id', $product)->first();

                $allproduct[$key] = $this->site_product($product_query);

            }

            if($product_categories){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }


    }






    /*
    |--------------------------------------------------------------------------
    | Public Function get_featured_product
    |--------------------------------------------------------------------------
    |
    */
    public function getFeaturedProduct(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $limit        =$request->limit;
            $category_slug=$request->category;

            $query=$this->get_product_query($request ->franchise)->where('product.featured', 1);

            if(isset($category_slug)){
                $query->join('product_category_map', 'product_category_map.product', '=', 'product.id')
                      ->join('product_category', 'product_category_map.product_category', '=', 'product_category.id')
                      ->where('product_category.slug', $category_slug);
            }

            $product_list = $query->limit($limit)->get();

            $allproduct = array();

            foreach ($product_list as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }


            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }

        /*
    |--------------------------------------------------------------------------
    | Public Function get_TopRated
    |--------------------------------------------------------------------------
    |
    */
    public function getTopRated(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $limit     = ($request->limit == null) ? 5 : $request->limit;
            $franchise = $request ->franchise;

            $top_rated= DB::table('customer_review')
                       ->select(DB::raw('`customer_review`.`product` AS id,CEILING(AVG(`customer_review`.`rating`)) AS rating'))
                       ->join('product', 'customer_review.product', '=', 'product.id')
                       ->join('franchise_product_map', 'franchise_product_map.product', '=', 'product.id')
                       ->where('franchise_product_map.franchise', $franchise)
                       ->groupBy('customer_review.product')
                       ->orderBy(DB::raw('CEILING(AVG(`customer_review`.`rating`))'),'desc')
                       ->limit($limit)
                       ->get();

            $allproduct=array();

            foreach ($top_rated as $key => $product) {
                $query=$this->get_product_query($franchise)->where('product.id', $product->id)->first();
                if(!empty($query)){
                   array_push($allproduct,$this->site_product($query));
                }

            }

            if($top_rated){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Public Function get_related_products
    |--------------------------------------------------------------------------
    |
    */
    public function getNewArrivals(Request $request)
    {
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $limit        =$request->limit;
            $category_slug=$request->category;

            $query=$this->get_product_query($request ->franchise)->whereNotNull('product.cover_image')->orderBy('product.id', 'desc');

            if(isset($category_slug)){
                $query->join('product_category_map', 'product_category_map.product', '=', 'product.id')
                      ->join('product_category', 'product_category_map.product_category', '=', 'product_category.id')
                      ->where('product_category.slug', $category_slug);
            }

            $product_list = $query->limit($limit)->get();

            $allproduct = array();

            foreach ($product_list as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }


            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Public Function get_search_Suggestions
    |--------------------------------------------------------------------------
    |
    */
    public function get_search_Suggestions(Request $request){

        $search_query =$request ->search_query;
        $limit        =$request ->limit;
        $category     =$request ->category;

        $query =$this->get_product_query()->WhereRaw("MATCH(title) AGAINST('.$search_query.')")
                     ->take($limit)->get();

        if($query){
            $allproduct=array();

            foreach ($query as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }
            return response()->json($allproduct);
        }

        return null;
    }












  /*
    |--------------------------------------------------------------------------
    | Public Function  get All
    |--------------------------------------------------------------------------
    |
    */
    public function getAll(Request $request){

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            $franchise = $request ->franchise;

            $query=$this->get_product_query($franchise);

            if($request->has('limit')){
                $query = $query->limit($request->limit);
            }

            if($request->has('order_by') && $request->order_by == 'ASC' || $request->order_by == 'DESC'){
                $query = $query->orderBy($request->order_by);
            }else if($request->has('order_by') && $request->order_by == 'RAND'){
                $query = $query->inRandomOrder();
            }

            $query = $query->get();

            foreach ($query as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }

            if($query){
                return response(['success' => true,'data'=> $allproduct,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }


    }











    /*
    |--------------------------------------------------------------------------
    | Public function / create customer
    |--------------------------------------------------------------------------
    |
    */
    public function createCustomerReviews(Request $request){

        try {

            $validation_array = [
                "review_body"     => 'required',
                "product"         => 'required',
                "rating"          => 'required',
                "email"           => 'required',
                "author"          => 'required',

            ];

            $validator = Validator::make($request->all(), $validation_array);

            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);
            }


            $rating   =$request->rating;
            $product  =$request->product;
            $author   =$request->author;
            $email    =$request->email;
            $review   =$request->review_body;


            $newReview = customerReview::create(
                ['customer_review' => $review,
                 'product'         => $product,
                 'rating'          => $rating,
                 'customer_name'   => $author,
                 'email'           => $email]);

            $review = array(
                'avatar'=>      'http://webstoresl.s3.ap-southeast-1.amazonaws.com/webstore/product-images/no-product-image.png',
                'author'=>      $newReview->customer_name,
                'rating'=>      $newReview->rating,
                'date'=>        date('d-M,Y', strtotime($newReview->created_at)),
                'review_body'=> $newReview->customer_review,
            );

            if($newReview){
                return response(['success' => true,'data'=> $review,'message' => "Everything is good!. Review successfully created"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }
















    /*
    |--------------------------------------------------------------------------
    | All private Functions
    |--------------------------------------------------------------------------
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Private Function site_product
    |--------------------------------------------------------------------------
    |
    */
    private function site_product($product)
    {
        if ($product->stock>0) {
            $availability = 'in-stock';
        }else{
            $availability = 'sold-out';
        }

        $product_images = $this->get_product_image_by_id($product->id);
        if(count($product_images)==0){
            $product_images =array('http://webstoresl.s3.ap-southeast-1.amazonaws.com/webstore/product-images/no-product-image.png');
        }

        $categories = $this->get_product_category_by_id($product->id);

        $attribute = $this->get_product_attribute($product);

        $variations  = $this->get_product_variations($product);

        //  return response()->json($variations);

        $product_review  = $this->get_product_reviews_by_id($product->id);



        $customFields =array(
            'short_description'=>$product->short_description,
            'long_description'=>$product->long_description,
            'sinhala_long_description'=>$product->sinhala_long_description,
            'weight'=>$product->weight
        );

        if($product->meta_title){
            $product_title = $product->meta_title;
        }else{
            $product_title = $product->title;
        }

        if($product->meta_description){
            $product_meta_description = $product->meta_description;
        }else{
            $product_meta_description =$product->short_description;
        }



        $product_array = array(
            'id'              => $product->id,
            'slug'            => $product->slug,
            'name'            => $product->title,
            'sku'             => $product->sku,
            'price'           => $product->price,
            'compareAtPrice'  => ($product->old_price > $product->price) ? $product->old_price : null,
            'images'          => $product_images,
            'badges'          => array(),
            'rating'          => $product_review['rating'],
            'reviews'         => $product_review['reviews'],
            'availability'    => $availability,
            'stock'           => $product->stock,
            'brand'           => $product->brand,
            'product_type'    => $product->product_type,
            'categories'      => $categories,
            'attributes'      => $attribute,
            'variations'      => $variations,
            'customFields'    => $customFields,
            'allow_backorder' => $product->allow_backorder,
            'meta_title'      => $product_title,
            'meta_description'=> $product_meta_description,
            'coupon'          => null,
            'coupon_code'     => null
        );

        return $product_array;
    }

    /*
    |--------------------------------------------------------------------------
    | Public function / get Products review V2
    |--------------------------------------------------------------------------
    */
    public function getProductReview(Request $request){

        try {

            if(!$request->has('product')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. Product is required.",], 500);
            }

            $product = $request ->product;

            $customerReview = customerReview::where('customer_review.product', $product)->get();
            $reviews=array();

            foreach ( $customerReview as $key => $review) {
                $reviews[$key] = array(
                    'avatar'   => config('myapp.base_url').'no-product-image.png',
                    'author'   => $review->customer_name,
                    'rating'   => $review->rating,
                    'date'     => date('d M,Y', strtotime($review->created_at)),
                    'text'     => $review->customer_review,
                );

            }

            $data['reviews'] = $reviews;
            $data['reviewSummary'] = $this->getProductReviewSummary($product);

            if($customerReview){
                return response(['success' => true,'data'=> $data,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Private function / get products review summary V2
    |--------------------------------------------------------------------------
    */
    private function getProductReviewSummary($id){

        $product_review =array('rating'=>0,'reviews'=>0);

        $query= customerReview::where('product', $id);

        $product_review['rating']    = number_format((float)$query->groupBy('product')->avg('rating'), 1, '.', '');
        $product_review['reviews']   = $query->count();
        $product_review['fiveStar']  = $this->getProductReviewsStarCount($id,5);
        $product_review['fourStar']  = $this->getProductReviewsStarCount($id,4);
        $product_review['threeStar'] = $this->getProductReviewsStarCount($id,3);
        $product_review['twoStar']   = $this->getProductReviewsStarCount($id,2);
        $product_review['oneStar']   = $this->getProductReviewsStarCount($id,1);

        return $product_review;

    }

    /*
    |--------------------------------------------------------------------------
    | Private function / get products review star count V2
    |--------------------------------------------------------------------------
    */
    private function getProductReviewsStarCount($id,$value){

        $query  = customerReview::where('product', $id);
        $count  = ($query->count()) ? $query->count() : 0;
        $rating = ($query->where('rating', $value)->count()) ? $query->where('rating', $value)->count() : 0;

        if($count===0){
            $pracantage = 0;
        }else{
            $pracantage = round($rating / $count * 100);
        }

        return $pracantage;

    }



    private function get_product_reviews_by_id($id){

        $product_review =array('rating'=>0,'reviews'=>0,'product'=>0);

        $query= DB::table('customer_review')->where('customer_review.product', $id);

        $product_review['rating']   =$query->groupBy('customer_review.product')->avg('rating');
        $product_review['reviews'] = $query->count();
        $product_review['product'] = $id;

        return $product_review;

    }




    /*
    |--------------------------------------------------------------------------
    | Private Function Filters
    |--------------------------------------------------------------------------
    |
    */
    private function get_all_filters($category_slug,$root,$min_price,$max_price,$filter_price,$request)
    {

    $FilterItem1 = array(
        'slug'=> 'health-n-beauty',
        'name'=> 'Health & Beauty',
        'count'=> 3,
    );

    $ColorFilterItem =array(
        'slug'=> 'health-n-beauty',
        'name'=> 'Health n Beauty',
        'count'=> 15,
        'color'=> 'red'
    );

    $CustomFields =array(
        'short_description' => 'short_description'
    );

    $CategoryFilter =$this->get_filter_category($category_slug,$root);

    $RangeFilter =$this->get_filter_price($min_price,$max_price,$filter_price);

    $attribute_filters =$this->get_attribute_filters($category_slug,$request);

    $ColorFilter = array(
        'slug'=> 'health-n-beauty',
        'name'=> 'Color',
        'type'=> 'color',
        'value'=> '10',
        'items'=> array($ColorFilterItem)
    );

    $RadioFilter =array(
        'slug'=> 'health-n-beauty',
        'name'=> 'With Discount',
        'type'=> 'radio',
        'value'=> '3',
        'items'=> array($FilterItem1)
    );

    $filters = array($CategoryFilter,$RangeFilter);

    $filters = array_merge($filters,$attribute_filters);

    return $filters;
    }








    /*
    |--------------------------------------------------------------------------
    | Private Function Get Attribute Filters
    |--------------------------------------------------------------------------
    |
    */
    private function get_attribute_filters($category_slug,$response){
        if(!isset($category_slug)){
            return array();
        }

        $query = "SELECT DISTINCT `product_attribute`.`id`,`product_attribute`.`name`,`product_attribute`.`slug`
        FROM `product`
        JOIN `product_category_map` ON `product_category_map`.`product`=`product`.`id`
        JOIN `product_category` ON `product_category`.`id`=`product_category_map`.`product_category`
        JOIN `product_attribute_value_map` ON `product_attribute_value_map`.`product`=`product`.`id`
        JOIN `product_attribute_value` ON `product_attribute_value_map`.`product_attribute_value`=`product_attribute_value`.`id`
        JOIN `product_attribute` ON `product_attribute`.`id` = `product_attribute_value`.`product_attribute`
        WHERE `product_category`.`slug`=:slug";

        $attributes = DB::select($query,['slug'=>$category_slug]);
        $CheckFilters =array();
        if(!isset($attributes)){
            return $CheckFilters;
        }

        foreach ($attributes as $key => $attribute) {
            $selected_values = $response->{"filter_".$attribute->slug};
            if(isset($selected_values)){
                $selected_values = explode(',', $selected_values );
            }else{
                $selected_values = array();
            }
            $CheckFilters[$key] = array(
                'slug'=> $attribute->slug,
                'name'=> $attribute->name,
                'type'=> 'check',
                'value'=> $selected_values,
                'items'=> $this->get_attribute_filters_values($category_slug,$attribute->id)
             );
        }

        return $CheckFilters;
        }







    /*
    |--------------------------------------------------------------------------
    | Private Function Get Attribute Filter Values
    |--------------------------------------------------------------------------
    |
    */
        private function get_attribute_filters_values($category_slug,$attribute_id){
            $query = "SELECT DISTINCT `product_attribute_value`.`value`,`product_attribute_value`.`slug`
            FROM `product`
            JOIN `product_category_map` ON `product_category_map`.`product`=`product`.`id`
            JOIN `product_category` ON `product_category`.`id`=`product_category_map`.`product_category`
            JOIN `product_attribute_value_map` ON `product_attribute_value_map`.`product`=`product`.`id`
            JOIN `product_attribute_value` ON `product_attribute_value_map`.`product_attribute_value`=`product_attribute_value`.`id`
            WHERE `product_category`.`slug`=:slug
            AND `product_attribute_value`.`product_attribute`=:attribute";

            $attributes_values = DB::select($query,['slug'=>$category_slug,'attribute'=>$attribute_id]);

            $values = array();
            foreach ($attributes_values as $key => $attributes_value) {

                $values[$key] = array(
                    'slug'=> $attributes_value->slug,
                    'name'=> $attributes_value->value,
                    'count'=> '5',
                );
            }

            return $values;
            }








  /*
    |--------------------------------------------------------------------------
    | Private Function filter_category
    |--------------------------------------------------------------------------
    |
    */
    private function get_filter_category($category_slug,$root)
    {
        if(isset($category_slug)){

            $parent_category = DB::table('product_category')
                            ->where('product_category.slug', $category_slug)
                            ->select('product_category.id','product_category.product_category','product_category.slug')
                            ->get();

            $child_category = DB::table('product_category')
                            ->where('product_category.category_parent', $parent_category[0]->id)
                            ->select('product_category.id','product_category.product_category','product_category.slug')
                            ->get();

            $category_merge = $parent_category->merge($child_category)->all();

            foreach ($category_merge as $key => $category) {

                $categories = $this->categories($category);
                $type = ($key==0) ? 'current' : 'child';
                $get_categories[$key] = $this->get_CategoryFilterItem($category , $categories, $type);
            }

            }else{

            $parent_category = DB::table('product_category')
                                        ->where('product_category.category_parent', null)
                                        ->select('product_category.id','product_category.product_category','product_category.slug')
                                        ->get();

            foreach ($parent_category as $key => $category) {

                $categories = $this->categories($category);
                $get_categories[$key] = $this->get_CategoryFilterItem($category , $categories);

            }}

            $CategoryFilter = array(
                'slug'=> 'categories',
                'name'=> 'Categories',
                'type'=> 'categories',
                'root'=> $root,
                'items'=> $get_categories
            );

            return $CategoryFilter;
    }








  /*
    |--------------------------------------------------------------------------
    | Private Function filter_price
    |--------------------------------------------------------------------------
    |
    */
    private function get_filter_price($min_price,$max_price,$filter_price)
    {
        if(isset($filter_price))
        {
            $explode_filter_price = array_map('intval', explode('-', $filter_price ));
            $request_min_price = $explode_filter_price[0];
            $request_max_price = $explode_filter_price[1];

        }else{
            $request_min_price = $min_price;
            $request_max_price = $max_price;
        }

        $RangeFilterValue = array($request_min_price,$request_max_price);

        $RangeFilter =array(
            'slug'=> 'price',
            'name'=> 'Price',
            'type'=> 'range',
            'value'=> $RangeFilterValue,
            'min'=> $min_price,
            'max'=> $max_price,
        );

        return $RangeFilter;
    }







  /*
    |--------------------------------------------------------------------------
    | Private Function filter_brand
    |--------------------------------------------------------------------------
    |
    */
    public function get_filter_brand()
    {
        $query_brand = DB::table('product_attribute_value')
                         ->join  ('product_attribute', 'product_attribute.id', '=', 'product_attribute_value.product_attribute')
                         ->where('product_attribute.id', 1)
                         ->select('product_attribute_value.id','product_attribute_value.value', 'product_attribute_value.slug')
                         ->get();


        foreach ($query_brand as $key => $brand) {
            $query_brand2 = DB::table('product_attribute_value_map')
                               ->Join('product_attribute_value', 'product_attribute_value_map.product_attribute_value', '=', 'product_attribute_value.id')
                               ->where('product_attribute_value.id', $brand->id)->count();

            $FilterItem[$key] = array(
                'slug'=> $brand->slug,
                'name'=> $brand->value,
                'count'=> $query_brand2,
            );
        }

        if(isset($filter_brand)){
            $explode_filter_brand = explode(',', $filter_brand );

            foreach ($explode_filter_brand as $key => $brand) {
                $filter_brand1[$key] =  $brand;
            }
            $ListFilterValue = $filter_brand1;

        }else {
            $ListFilterValue = array();
        }


        $CheckFilter = array(
            'slug'=> 'brand',
            'name'=> 'Brand',
            'type'=> 'check',
            'value'=> $ListFilterValue,
            'items'=> $FilterItem
         );

         return $CheckFilter;
    }
































    /*
    |--------------------------------------------------------------------------
    | Private Get Product Query
    |--------------------------------------------------------------------------
    |
    */
    private function get_product_query($franchise)
    {
        $query = Product::join  ('franchise_product_map', 'franchise_product_map.product', '=', 'product.id')
                            ->where('franchise_product_map.franchise', $franchise)
                            ->where('product.status', 'published')
                            ->select('product.id','product.title', 'product.slug', 'product.brand'
                                    ,'product.sku','franchise_product_map.price','product.stock' ,'product.product_type'
                                    ,'product.short_description','product.long_description'
                                    ,'product.sinhala_long_description','product.old_price','product.weight','product.meta_title','product.meta_description','product.allow_backorder');
                            // ->distinct('product.id');

         return $query;
    }

    private function get_product_query_model()
    {
        $result = Product::with('Franchise')
                    ->orWhereHas('Franchise', function($query){
                        $query->where('franchise',config('myapp.franchise'));
                    });

         return $result;
    }







    /*
    |--------------------------------------------------------------------------
    | Private Function image
    |--------------------------------------------------------------------------
    |
    */
    private function get_product_image_by_id($id)
    {
        $product_images = DB::table('product_images')
                        ->join('image', 'image.id', '=', 'product_images.image')
                        ->select(DB::raw('CONCAT("'.config('myapp.base_url').'", image.image_name) AS image_name'))
                        ->where('product', $id)
                        ->pluck('image.image_name');

        return ($product_images);
    }







    /*
    |--------------------------------------------------------------------------
    | Private Function category
    |--------------------------------------------------------------------------
    |
    */
    private function get_product_category_by_id($id)
    {
        $product_category = DB::table('product_category_map')
                        ->join('product_category', 'product_category.id', '=', 'product_category_map.product_category')
                        ->where('product_category_map.product', $id)
                        ->select('product_category.id','product_category.product_category','product_category.slug')
                        ->get();

        $categories = array();

        foreach ($product_category as $key => $category) {
            $categories[$key] = array(
            'id'  => $category->id,
            'type'=> 'shop',
            'name'=> $category->product_category,
            'slug'=> $category->slug,
            'path'=> 'adfadfadf',
            'image'=> null,
            'items'=> 0,
            'customFields'=> null,
            'parents'=> null,
            'children'=> null
            );
        }
        return ($categories);
    }








    /*
    |--------------------------------------------------------------------------
    | Default Function attribute
    |--------------------------------------------------------------------------
    |
    */
    public function get_product_attribute($product)
    {
        // $attribute_value_1 = array(
        //     'name'=> 'Red',
        //     'slug'=> 'red',
        //     'customFields'=> null
        // );

        // $attribute_value_2 = array(
        //     'name'=> 'Blue',
        //     'slug'=> 'blue',
        //     'customFields'=> null
        // );

        // $attribute = array(
        //     'name'=> 'Color',
        //     'slug'=> 'color',
        //     'featured'=> 0,
        //     'values'=> array($attribute_value_1,$attribute_value_2),
        //     'customFields'=> null
        // );


        $product_attribute_value_map =productAttributeValueMap::where('product', '=', $product->id)
                                                                ->join('product_attribute_value', 'product_attribute_value.id', '=', 'product_attribute_value_map.product_attribute_value')
                                                                ->join('product_attribute', 'product_attribute.id', '=', 'product_attribute_value.product_attribute')
                                                                ->distinct()
                                                                ->get(['product_attribute','product_attribute.slug','product_attribute.name']);

        // return response()->json($product_attribute_value_map);

        $attributes =array();

        foreach ($product_attribute_value_map as $key => $product_attribute) {

            $attribute_value =productAttribute::with('value')->where('id', '=', $product_attribute->product_attribute)->first();

            $attribute_value_arr=array();

            foreach ($attribute_value->Value as $key1 => $val) {

                $attribute_value_arr[$key1] = array(
                    'id'  => $val->id,
                    'name'=> $val->value,
                    'slug'=> $val->slug,
                    'customFields'=> null
                );

            }

            $attributes[$key] = array(
                'name'=> $product_attribute->name,
                'slug'=> $product_attribute->slug,
                'featured'=> 0,
                'values'=> $attribute_value_arr,
                'customFields'=> null
            );

        }

        // return ($product_attribute_value_map);

        return ($attributes);
    }


    /*
    |--------------------------------------------------------------------------
    | Default Function variations
    |--------------------------------------------------------------------------
    |
    */
    public function get_product_variations($product){

        $variations = productVariation::with('Attributemap')->where('product',$product->id)->get();

        if(count($variations)==0){ return false;}

        $variation=array();


        foreach ($variations as $key => $val) {

            $attribute_value_arr=array();

            foreach ($val->attributemap as $key1 => $item) {



                $attribute_value =productAttributeValue::find($item->attribute_value);
                $attribute_value_arr[$key1] = array(
                    'id'  => $attribute_value->id,
                    'value'=> $attribute_value->value,
                    'slug'=> $attribute_value->slug
                );

            }


            $variation[$key] = array(
                'id' => $val->id,
                'title'=> $val->title,
                'price'=> $val->price,
                'cost'=> $val->cost,
                'sku'=> $val->sku,
                'stock'=> $val->stock,
                'values'=> $attribute_value_arr
            );

        }

        return ($variation);
    }








  /*
    |--------------------------------------------------------------------------
    | Default Function categories
    |--------------------------------------------------------------------------
    |
    */
    public function categories($category)
    {
        $categories = array(
            'id'  => $category->id,
            'type'=>"shop",
            'name'=> $category->product_category,
            'slug'=>$category->slug,
            'path'=> $category->slug,
            'image'=> null,
            'items'=> 10,
            'customFields'=> null,
            'parents'=> null,
            'children'=> null
        );

        return $categories;
    }








  /*
    |--------------------------------------------------------------------------
    | Private Function get_CategoryFilterItem
    |--------------------------------------------------------------------------
    |
    */
    private function get_CategoryFilterItem($category , $categories, $type='parent')
    {
        $CategoryFilterItem = array(
            'slug'=> $category->slug,
            'name'=> $category->product_category,
            'count'=> 15,
            'type'=> $type,
            'category'=>$categories
        );

        return $CategoryFilterItem;
    }







  /*
    |--------------------------------------------------------------------------
    | Private Function get_sort_listing
    |--------------------------------------------------------------------------
    |
    */
    private function get_sort_listing($sort, $query){

        if($sort=="name_asc"){
            $query->orderBy('product.title', 'asc');
        }elseif($sort=="name_desc"){
            $query->orderBy('product.title', 'desc');
        }elseif($sort=="price_min"){
            $query->orderBy('franchise_product_map.price', 'asc');
        }elseif($sort=="price_max"){
            $query->orderBy('franchise_product_map.price', 'desc');
        }elseif($sort=="order_asc"){
            $query->orderBy('product.id', 'asc');
        }elseif($sort=="order_desc"){
            $query->orderBy('product.id', 'desc');
        }else{
            $query->orderBy('franchise_product_map.product');
        }

        return $query;
    }



    public function loadMoreproduct(Request $request){
        try {

            $franchise     = config('myapp.franchise');

            $product_list=$this->get_product_query($franchise)->whereNotNull('product.cover_image')->inRandomOrder()->paginate(10);


            $allproduct = array();

            foreach ($product_list as $key => $product) {
                $allproduct[$key] = $this->site_product($product);
            }

            $list = array(
                'product_items'=> $allproduct,
                'page'         => $product_list->currentPage(),

            );

            if($product_list){
                return response(['success' => true,'data'=> $list,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        }
        catch(\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }


    public function applyCouponCode(Request $request){
        try {

            $validation_array = [
                "coupon_code"     => 'required',
                "franchise"       => 'required',
            ];

            $validator = Validator::make($request->all(), $validation_array);

            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 422);
            }

            $coupon_code   = $request->coupon_code;
            $franchise     = $request->franchise;

            $discount = Discount::with('getDiscountProductMap')->where('valid_until','>=',date("Y-m-d"))->where('discount_code',$coupon_code)->first();

            if($discount && count($discount->getDiscountProductMap) > 0){
                return response(['success' => true,'data'=> $discount->getDiscountProductMap,'message' => "Everything is good!. Discount Found!"], 200);
            }
            else{
                return response(['success' => false,'data'=> null,'message' => "Everything is good!. Discount Not Found!"], 402);
            }

        } catch (\Throwable $e) {
            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }







//...............................................................................................................................................
//.PPPPPPPPP..........riii......................ttt................. FFFFFFFFF..................................ttt..tiii........................
//.PPPPPPPPPP.........riii.....................attt................. FFFFFFFFF.................................cttt..tiii........................
//.PPPPPPPPPPP.................................attt................. FFFFFFFFF.................................cttt..............................
//.PPPP...PPPPPPrrrrrrriiiiivv..vvvv..aaaaaa.aaattttt.eeeeee........ FFF......FFuu..uuuuu.unnnnnnn....cccccc.cccttttttiii...oooooo...onnnnnnn....
//.PPPP...PPPPPPrrrrrrriiiiivv..vvvv.vaaaaaaaaaatttttteeeeeee....... FFF......FFuu..uuuuu.unnnnnnnn..nccccccccccttttttiii.ioooooooo..onnnnnnnn...
//.PPPPPPPPPPPPPrrr...riiiiivv.vvvv.vvaa.aaaaa.attt.ttee.eeee....... FFFFFFFF.FFuu..uuuuu.unnn.nnnnnnnccc.cccc.cttt..tiii.iooo.ooooo.onnn.nnnnn..
//.PPPPPPPPPP.PPrr....riii.ivvvvvvv.....aaaaaa.attt.ttee..eeee...... FFFFFFFF.FFuu..uuuuu.unnn..nnnnnncc..ccc..cttt..tiiiiioo...oooo.onnn..nnnn..
//.PPPPPPPPP..PPrr....riii.ivvvvvvv..vaaaaaaaa.attt.tteeeeeeee...... FFFFFFFF.FFuu..uuuuu.unnn..nnnnnncc.......cttt..tiiiiioo...oooo.onnn..nnnn..
//.PPPP.......PPrr....riii.ivvvvvv..vvaaaaaaaa.attt.tteeeeeeee...... FFF......FFuu..uuuuu.unnn..nnnnnncc.......cttt..tiiiiioo...oooo.onnn..nnnn..
//.PPPP.......PPrr....riii..vvvvvv..vvaa.aaaaa.attt.ttee............ FFF......FFuu..uuuuu.unnn..nnnnnncc..ccc..cttt..tiiiiioo...oooo.onnn..nnnn..
//.PPPP.......PPrr....riii..vvvvvv..vvaa.aaaaa.attt.ttee..eeee...... FFF......FFuuu.uuuuu.unnn..nnnnnnccc.cccc.cttt..tiii.iooo.ooooo.onnn..nnnn..
//.PPPP.......PPrr....riii..vvvvv...vvaaaaaaaa.atttttteeeeeee....... FFF.......Fuuuuuuuuu.unnn..nnnn.ncccccccc.cttttttiii.ioooooooo..onnn..nnnn..
//.PPPP.......PPrr....riii...vvvv....vaaaaaaaa.attttt.eeeeee........ FFF........uuuuuuuuu.unnn..nnnn..cccccc...cttttttiii...oooooo...onnn..nnnn..
//...............................................................................................................................................


    /*
    |--------------------------------------------------------------------------
    | Public Function  get categories
    |--------------------------------------------------------------------------
    |
    */
    public function get_categories(Request $request){

        if ($request->parent!=null) {
            $parent = DB::table('product_category')
                            ->where('product_category.slug', $request->parent)
                            ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent')
                            ->first();
            if (isset($parent)) {
                $parent_id = $parent->id;
            }else{
                $parent_id = null;
            }
        }else{
            $parent_id = null;
        }

        $categories = DB::table('product_category')
                        ->where('product_category.category_parent', $parent_id)
                        ->leftJoin('image', 'product_category.image', '=', 'image.id')
                        ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                        ->get();

        $result = array();
        foreach ($categories as $key => $category) {
           $result[$key] = $this->get_category_obj($category,false);
        }

        return response()->json($result);
    }

    public function get_popularCategories(Request $request){

        $category_slugs=$request ->slugs;
        $pieces = explode(',', $category_slugs);

        foreach ($pieces as $key => $category) {

            $category = DB::table('product_category')
                        ->where('product_category.slug', $category)
                        ->leftJoin('image', 'product_category.image', '=', 'image.id')
                        ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                        ->first();

            $result[$key] = $this->get_category_obj($category);
         }

        return response()->json($result);
    }

        /*
    |--------------------------------------------------------------------------
    | Public function Get Category By Slug
    |--------------------------------------------------------------------------
    |
    */
    public function getCategoryBySlug(Request $request){
        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('slug')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. slug is required.",], 500);
            }

            $franchise = $request ->franchise;
            $slug      = $request ->slug;

            $category = DB::table('product_category')
                        ->where('product_category.slug', $slug)
                        ->where('franchise_category_map.franchise', $franchise)
                        ->leftJoin('image', 'product_category.image', '=', 'image.id')
                        ->join('franchise_category_map', 'product_category.id', '=', 'franchise_category_map.product_category')
                        ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                        ->first();



            if($category){
                $product_array =  $this->get_category_obj($category);

                return response(['success' => true,'data'=> $product_array,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }

    public function get_navigation_ink_item(Request $request){

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('depth')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. Depth is required.",], 500);
            }


            $franchise = $request ->franchise;
            $depth     = $request ->depth;

            $parents = productCategory::where('product_category.category_parent', null)
                                ->where('franchise_category_map.franchise', $franchise)
                                ->leftJoin('image', 'product_category.image', '=', 'image.id')
                                ->join('franchise_category_map', 'product_category.id', '=', 'franchise_category_map.product_category')
                                ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                                ->limit(10)
                                ->get();

            if(!$parents->isEmpty()){

                $result = array();

                foreach($parents as $key => $parent){

                   $link = array(
                        'label'=> $parent->product_category,
                        'slug'  => $parent->slug,
                        'image'=> config('myapp.base_url').$parent->image_name,
                        'sub'=> null
                    );

                    if($depth == 2 || $depth == 3 ){
                        $link['sub'] =  $this->get_sub_category_links($parent->id, $depth);
                    }

                    $result[$key] = $link;
                }

                return response(['success' => true,'data'=> $result,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

        } catch (\Exception $e) {

            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }

    public function get_mobile_navigation_ink_category()
    {
        $categories = DB::table('product_category')
                            ->where('product_category.category_parent', null)
                            ->select('product_category.id','product_category.product_category','product_category.path',)
                            ->get();

                foreach($categories as $key => $parent){

                    $children=array(
                        'type'=> 'link',
                        'label'=> $parent->product_category,
                        'url'=>'shop/catalog/'.$parent->path,
                        'children'=>$this->get_sub_category_links_mobile($parent->id)
                    );

                    $result[$key] =  $children;
                }
        return response()->json($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Private function get_category_obj
    |--------------------------------------------------------------------------
    |
    */
    private function get_category_obj($category_obj,$get_children=true){

        if($category_obj->image_name){
            $image = config('myapp.base_url').$category_obj->image_name;
        }else{
            $image = config('myapp.base_url').'no-product-image.png';
        }

        $category = array(
            "label"    => $category_obj->product_category,
            "slug"     => $category_obj->slug,
            "image"    => $image,
            "children" => null,
        );

        if ($get_children) {
            $category["children"] = $this->get_child_categories($category_obj->id);
        }

        return $category;
    }

        /*
    |--------------------------------------------------------------------------
    | Private function get_category_by_id
    |--------------------------------------------------------------------------
    |
    */
    private function get_category_by_id($id,$get_children=true){
        $category = DB::table('product_category')
                        ->where('product_category.id', $id)
                        ->leftJoin('image', 'product_category.image', '=', 'image.id')
                        ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                        ->first();

        if (isset($category)) {
            return $this->get_category_obj($category,$get_children);
        }else{
            return null;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Private function get_child_categories
    |--------------------------------------------------------------------------
    |
    */
    private function get_child_categories($id){
        $categories = DB::table('product_category')
                        ->where('product_category.category_parent', $id)
                        ->leftJoin('image', 'product_category.image', '=', 'image.id')
                        ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent','image.image_name')
                        ->get();

        $child_categories = array();

        foreach ($categories as $key => $category) {
            $child_categories[$key] = $this->get_category_obj($category);
        }

        if (isset($child_categories)) {
            return $child_categories;
        }else{
            return null;
        }
    }


    private function get_sub_category_links($parent, $depth ){
        $categories = productCategory::where('product_category.category_parent', $parent)
                            ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent')
                            ->get();
        $result = array();

        foreach($categories as $key=> $category){
            $item =array(
                'label'=> $category->product_category,
                'slug'=> $category->slug,
                'sub'=> null
            );

            if($depth == 3 ){
                $item['sub'] =  $this->get_grand_sub_category_links($category->id);
            }


           $result[$key] = $item;
        }

        return $result;
    }

    private function get_grand_sub_category_links($parent){
        $categories = productCategory::where('product_category.category_parent', $parent)
                            ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent')
                            ->get();
        $result = array();

        foreach($categories as $key=> $category){
            $item =array(
                'label'=> $category->product_category,
                'slug'=> $category->slug,
                );
           $result[$key] = $item;
        }

        return $result;
    }



    private function get_sub_category_links_mobile($parent){
        $categories = DB::table('product_category')
                            ->where('product_category.category_parent', $parent)
                            ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent')
                            ->get();
        $result = array();

        foreach($categories as $key=> $category){
            $item =array(
                'type'=> 'link',
                'label'=> $category->product_category,
                'url'=> '/shop?category='.$category->slug,
                'children'=> $this->get_grand_sub_category_links_mobile($category->id)
                );

        $result[$key] =$item;
        }

        return $result;
    }

    private function get_grand_sub_category_links_mobile($parent){
        $categories = DB::table('product_category')
                            ->where('product_category.category_parent', $parent)
                            ->select('product_category.id','product_category.product_category','product_category.slug','product_category.category_parent')
                            ->get();
        $result = array();

        foreach($categories as $key=> $category){
                $item =array(
                    'type'=> 'link',
                    'label'=> $category->product_category,
                    'url'=> '/shop?category='.$category->slug,
                    );
        $result[$key] = $item;
        }

        return $result;
    }

}


