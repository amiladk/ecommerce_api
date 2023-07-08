<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\contactUsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'namespace'=>'App\Http\Controllers',
    'prefix' => 'auth'

], function ($router) {
/*
|--------------------------------------------------------------------------
| Web Route / AuthController
|--------------------------------------------------------------------------
*/
    Route::post('login'      , 'AuthController@login');

    Route::post('signup'     , 'AuthController@signup');

    Route::post('register'   , 'AuthController@register');

    Route::post('logout'     , 'AuthController@logout');

    Route::post('refresh'    , 'AuthController@refresh');

    Route::get('user-profile', 'AuthController@userProfile');

    Route::post('socialsignup', 'AuthController@social_signup');


/*
|--------------------------------------------------------------------------
| Web Route / ResetPasswordController
|--------------------------------------------------------------------------
*/
    Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');



/*
|--------------------------------------------------------------------------
| Web Route / ChangePasswordController
|--------------------------------------------------------------------------
*/
    Route::post('resetPassword', 'ChangePasswordController@process');



/*
|--------------------------------------------------------------------------
| Web Route / ProductController
|--------------------------------------------------------------------------
*/
    Route::get('/shop/categories'             , 'ProductController@get_categories');

    Route::get('/catalog'                     , 'ProductController@get_product_listing');

    Route::get('/shop/products/special-offers', 'ProductController@get_SpecialOffers');

    Route::get('/shop/products/bestsellers'   , 'ProductController@get_bestsellers');

    Route::get('/shop/products/top-rated'     , 'ProductController@get_TopRated');

    Route::get('/shop/{productSlug}'          , 'ProductController@get_product');

    Route::get('/shop/products/related'       , 'ProductController@get_related_products');

    Route::get('/search/suggestions'          , 'ProductController@get_search_Suggestions');

    Route::get('/shop/category/{slug}'        , 'ProductController@get_category_by_slug');

    Route::get('/shop/products/featured'      , 'ProductController@get_featured_product');

    Route::get('/shop/products/latest'        , 'ProductController@get_new_arrivals');

    Route::post('/customer_reviews'           , 'ProductController@get_customer_reviews');

    Route::get('/categories'                  , 'ProductController@get_popularCategories');

    Route::get('/productreview/{id}'          , 'ProductController@get_productReview');

    Route::get('/shop/products/all'                         , 'ProductController@get_all');

    Route::get('/departmentlink'              , 'ProductController@get_navigation_ink_item');


    Route::get('/mobilelink'                  , 'ProductController@get_mobile_navigation_ink_category');




/*
|--------------------------------------------------------------------------
| Web Route / contactUsController
|--------------------------------------------------------------------------
*/
    Route::post('/contactus', 'contactUsController@contactUsSubmit');



/*
|--------------------------------------------------------------------------
| Web Route / ProfileController
|--------------------------------------------------------------------------
*/
    Route::get('/customer'               , 'ProfileController@get_customer');

    Route::get('/address'                , 'ProfileController@get_address');

    Route::get('/recentorders'           , 'ProfileController@get_RecentOrders');

    Route::get('/orderhistory'           , 'ProfileController@get_OrderHistory');

    Route::get('/orderdetails/{order_id}', 'ProfileController@get_OrderDetails');

    Route::get('/getaddress'             , 'ProfileController@get_PageAddresses');

    Route::post('/removeaddress'         , 'ProfileController@removeAddress');

    Route::post('/removePhone'           , 'ProfileController@removePhone');

    Route::get('/male_data' , 'ProfileController@male_data');

    Route::post('/vote_me' , 'ProfileController@vote_me');



/*
|--------------------------------------------------------------------------
| Web Route / CheckoutController
|--------------------------------------------------------------------------
*/
    Route::get('/checkoutcity'                 , 'CheckoutController@get_CheckoutCity');

    Route::post('/orderCheckoutForm'           , 'CheckoutController@place_order');

    Route::get('/ordersuccessdetails/{orderId}', 'CheckoutController@ordersuccessdetails');

    Route::get('/ordertrackingdetails/{searchcode}/{phone}', 'CheckoutController@ordertrackingdetails');

    Route::get('/checkoutaddress'              , 'CheckoutController@get_address');

    Route::get('/checkoutgetaddress'           , 'CheckoutController@get_PageAddresses');

    Route::get('/checkoutphonenumbers'         , 'CheckoutController@get_checkoutphonenumbers');

    Route::get('/checkoutphonenumber'          , 'CheckoutController@get_checkoutphonenumber');

    Route::get('/checkoutget'                  , 'CheckoutController@get_PageAddresses');

    Route::post('/changedefaultaddress'        , 'CheckoutController@submitChangeDefaultAddress');

    Route::post('/addnewaddress'               , 'CheckoutController@addnewaddress');

    Route::post('/addnewphone'                 , 'CheckoutController@addnewphone');

    Route::post('/changedefaultphonenumber'    , 'CheckoutController@submitChangeDefaultPhoneNumber');

    Route::post('/payherenotify'    , 'CheckoutController@payhereNotify');

    Route::post('/getcoupon'    , 'CheckoutController@getCoupon');


});

//.................................................................................................
//........AAAAAAAA........PPPPPPPPPPPPPPP.....IIIIII......... VVVVV.........VVVVVV...222222222.....
//........AAAAAAAA........PPPPPPPPPPPPPPPP....IIIIII......... VVVVV.........VVVVVV..222222222222...
//.......AAAAAAAAA........PPPPPPPPPPPPPPPPP...IIIIII......... VVVVVV.......VVVVVV..22222222222222..
//.......AAAAAAAAAA.......PPPPPPPPPPPPPPPPPP..IIIIII..........VVVVVV.......VVVVVV.222222222222222..
//.......AAAAAAAAAA.......PPPPPP...PPPPPPPPP..IIIIII..........VVVVVV.......VVVVVV.222222222222222..
//......AAAAAAAAAAA.......PPPPPP......PPPPPP..IIIIII..........VVVVVVV.....VVVVVV..222222...222222..
//......AAAAAAAAAAAA......PPPPPP......PPPPPP..IIIIII...........VVVVVV.....VVVVVV..222222....22222..
//.....AAAAAA.AAAAAA......PPPPPP......PPPPPP..IIIIII...........VVVVVV....VVVVVV.............22222..
//.....AAAAAA.AAAAAA......PPPPPP......PPPPPP..IIIIII...........VVVVVVV...VVVVVV............222222..
//.....AAAAAA..AAAAAA.....PPPPPP...PPPPPPPPP..IIIIII............VVVVVV...VVVVVV...........2222222..
//....AAAAAA...AAAAAA.....PPPPPPPPPPPPPPPPPP..IIIIII............VVVVVV..VVVVVV...........2222222...
//....AAAAAA...AAAAAAA....PPPPPPPPPPPPPPPPP...IIIIII............VVVVVVV.VVVVVV..........22222222...
//....AAAAAA....AAAAAA....PPPPPPPPPPPPPPPP....IIIIII.............VVVVVV.VVVVVV.........22222222....
//...AAAAAAAAAAAAAAAAA....PPPPPPPPPPPPPPP.....IIIIII.............VVVVVVVVVVVV.........22222222.....
//...AAAAAAAAAAAAAAAAAA...PPPPPP..............IIIIII..............VVVVVVVVVVV........22222222......
//...AAAAAAAAAAAAAAAAAA...PPPPPP..............IIIIII..............VVVVVVVVVV........2222222........
//..AAAAAAAAAAAAAAAAAAA...PPPPPP..............IIIIII..............VVVVVVVVVV.......2222222.........
//..AAAAAA.......AAAAAAA..PPPPPP..............IIIIII...............VVVVVVVVV.......222222..........
//.AAAAAA.........AAAAAA..PPPPPP..............IIIIII...............VVVVVVVV.......222222222222222..
//.AAAAAA.........AAAAAA..PPPPPP..............IIIIII...............VVVVVVVV.......222222222222222..
//.AAAAAA.........AAAAAAA.PPPPPP..............IIIIII................VVVVVVV.......222222222222222..
//.AAAAA...........AAAAAA.PPPPPP..............IIIIII................VVVVVV.......V222222222222222..
//.................................................................................................

Route::group([
    'middleware' => 'api',
    'namespace'  => 'App\Http\Controllers\v2',
    'prefix'     => 'auth/v2'

], function ($router) {
/*
|--------------------------------------------------------------------------
| Web Route / AuthController
|--------------------------------------------------------------------------
*/

    /****************************************************************************************
    *  User signup.
    *
    *  Required param:
    *          = customer_name -  Customer name.
    *          = email         -  Customer email .
    *          = password      -  Password
    *          = password_confirmation - Confirm password.
    *          = franchise     -  Which franchise customer should be signup. (franchise id)
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer signup.
    /****************************************************************************************/
    Route::post('/signup'     , 'AuthController@signup');





    /****************************************************************************************
    *  User login.
    *
    *  Required param:
    *          = email         -  Customer email .
    *          = password      -  Password
    *          = franchise     -  Which franchise customer should be login. (franchise id)
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer login.
    /****************************************************************************************/
    Route::post('/login'      , 'AuthController@login');





    /****************************************************************************************
    *  User logout.
    *
    *  Required param - NO
    *
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer logout.
    /****************************************************************************************/
    Route::post('/logout'     , 'AuthController@logout');



    // Route::post('refresh'    , 'AuthController@refresh');

    // Route::get('user-profile', 'AuthController@userProfile');

    // Route::post('socialsignup', 'AuthController@socialSignup');


/*
|--------------------------------------------------------------------------
| Web Route / ResetPasswordController
|--------------------------------------------------------------------------
*/
    // Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');



/*
|--------------------------------------------------------------------------
| Web Route / ChangePasswordController
|--------------------------------------------------------------------------
*/
    // Route::post('resetPassword', 'ChangePasswordController@process');



/*
|--------------------------------------------------------------------------
| Web Route / ProductController
|--------------------------------------------------------------------------
*/

    /****************************************************************************************
    *  Get category by slug.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = slug      - Which product should be returned. (product slug)
    *  Optional param - NO
    *
    *  Usage  - commonly used to get category by slug in any place.
    /****************************************************************************************/
    Route::get('/get-category-by-slug'        , 'ProductController@getCategoryBySlug');





    /****************************************************************************************
    *  Get all categories.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = depth     - Which level categories should be returned.
    *                        (1 = parent only / 2 parent & sub only / 3 All levels)
    *  Optional param - NO
    *
    *  Usage  - commonly used to get categories in any place.
    /****************************************************************************************/
    Route::get('/get-categories'              , 'ProductController@get_navigation_ink_item');





    /****************************************************************************************
    *  Get special offers.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = limit     - Maximum number of items returned at one time.
    *
    *  Usage  - commonly used for get special offers in any place.
    /****************************************************************************************/
    Route::get('/get-product-special-offers', 'ProductController@getSpecialOffers');





    /****************************************************************************************
    *  Get best sellers from past 31 days.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = limit     - Maximum number of items returned at one time.
    *
    *  Usage  - commonly used for get best sellers in any place.
    /****************************************************************************************/
    Route::get('/get-product-best-sellers'   , 'ProductController@getBestsellers');





    /****************************************************************************************
    *  Get top rated products.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = limit     - Maximum number of items returned at one time.
    *
    *  Usage  - commonly used for get top rated product in any place.
    /****************************************************************************************/
    Route::get('/get-product-top-rated'     , 'ProductController@getTopRated');





    /****************************************************************************************
    *  Get featured product.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = limit     - Maximum number of items returned at one time.
    *          = category  - Which category item should be returned.
    *
    *  Usage  - commonly used to featured product in any place.
    /****************************************************************************************/
    Route::get('get-featured-product'      , 'ProductController@getFeaturedProduct');





    /****************************************************************************************
    *  Get new arrivals .
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = limit     - Maximum number of items returned at one time.
    *          = category  - Which category item should be returned.
    *
    *  Usage  - commonly used to featured product in any place.
    /****************************************************************************************/
    Route::get('get-new-arrivals'           , 'ProductController@getNewArrivals');





    /****************************************************************************************
    *  Get product listing.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param:
    *          = category     - Which category items should be returned. (category slug)
    *          = limit        - Maximum number of items returned at one time.
    *          = sort         - The algorithm by which the list should be sorted (ASC or DESC)
    *          = filter_price - The price range items should be returned. (100-1000)
    *
    *  Usage  - commonly used in shop page.
    /****************************************************************************************/
    Route::get('/get-shop-catalog'                     , 'ProductController@getProductListing');





    /****************************************************************************************
    *  Get single product data from slug.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = slug      - Which product should be returned. (product slug)
    *  Optional param - NO
    *
    *  Usage  - commonly used in single product page.
    /****************************************************************************************/
    Route::get('/get-product-single'          , 'ProductController@getProduct');





    /****************************************************************************************
    *  Get related product from slug.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = slug      - Which product should be returned. (product slug)
    *  Optional param - NO
    *
    *  Usage  - commonly used in single product page.
    /****************************************************************************************/
    Route::get('/get-product-related-old'       , 'ProductController@getRelatedProductsOld');






    /****************************************************************************************
    *  Get related product from slug.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = slug      - Which product should be returned. (product slug)
    *  Optional param - NO
    *
    *  Usage  - commonly used in single product page.
    /****************************************************************************************/
    Route::get('/get-product-related'       , 'ProductController@getRelatedProducts');





    /****************************************************************************************
    *  Create product review.
    *
    *  Required param:
    *          = review_body  - Review content.
    *          = product      - Which product reviews should be created.
    *          = rating       - Rating count.
    *          = email        - Author email.
    *          = author       - Author name.
    *  Optional param - NO
    *
    *  Usage  - commonly used in create product review.
    /****************************************************************************************/
    Route::post('create-product-reviews'           , 'ProductController@createCustomerReviews');





    /****************************************************************************************
    *  Get product review from product ID
    *
    *  Required param:
    *          = product   - Which product reviews should be returned. (product id)
    *  Optional param - NO
    *
    *  Usage  - commonly used in single product page.
    /****************************************************************************************/
    Route::get('/get-product-review'              , 'ProductController@getProductReview');





    /****************************************************************************************
    *  Get all product.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param - NO
    *          = limit        - Maximum number of items returned at one time.
    *          = order_by     - The algorithm by which the list should be sorted (ASC or DESC and RAND)
    *  Usage  - Get all products in any place.
    /****************************************************************************************/
    Route::get('/get-all-product'             , 'ProductController@getAll');





    /****************************************************************************************
    *  Get all product.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional param - NO
    *          = limit        - Maximum number of items returned at one time.
    *          = order_by     - The algorithm by which the list should be sorted (ASC or DESC and RAND)
    *  Usage  - Get all products in any place.
    /****************************************************************************************/
    Route::get('/load-more-product' , 'ProductController@loadMoreproduct');



    /****************************************************************************************
    *  Get apply coupon code.
    *
    *  Required param:
    *          = coupon_code
    *          = franchise - Which franchise items should be returned. (franchise id)
    *  Optional
    *
    *  Usage  - Get apply coupon code for order.
    /****************************************************************************************/
    Route::get('/apply-coupon-code' , 'ProductController@applyCouponCode');


/*
|--------------------------------------------------------------------------
| Web Route / contactUsController
|--------------------------------------------------------------------------
*/
    Route::post('/contact-us', 'ContactUsController@contactUsSubmit');




/*
|--------------------------------------------------------------------------
| Web Route / SiteMapController
|--------------------------------------------------------------------------
*/
Route::get('/get-site-map-data', 'SiteMapController@getSiteMapData');



/*
|--------------------------------------------------------------------------
| Web Route / ProfileController
|--------------------------------------------------------------------------
*/

    /****************************************************************************************
    *  Get single customer from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer in account.
    /****************************************************************************************/
    Route::get('/get-customer'               , 'ProfileController@getCustomer');





    /****************************************************************************************
    *  Get all customer addresses from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer Recent orders in account.
    /****************************************************************************************/
    Route::get('/get-customer-addresses' , 'ProfileController@getCustomerAddress');





    /****************************************************************************************
    *  Get customer all phone number from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer all phone number in account.
    /****************************************************************************************/
    Route::get('/get-customer-phone-numbers'   , 'ProfileController@getCustomerPhoneNumber');





    /****************************************************************************************
    *  Get recent orders from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer Recent orders in account.
    /****************************************************************************************/
    Route::get('/get-recent-orders'           , 'ProfileController@getRecentOrders');





    /****************************************************************************************
    *  Add new phone number trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = phone_number
    *
    *  Optional param: - NO
    *
    *  Usage  - Add new phone number in account.
    /****************************************************************************************/
    Route::post('/create-customer-phone-number'    , 'ProfileController@createCustomerPhoneNumber');





    /****************************************************************************************
    *  Add new customer address trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = address
    *          = cityId
    *  Optional param: - NO
    *
    *  Usage  - Add new customer address in account.
    /****************************************************************************************/
    Route::post('/create-customer-address'         , 'ProfileController@createCustomerAddress');





    /****************************************************************************************
    *  Remove customer address trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = address
    *
    *  Optional param: - NO
    *
    *  Usage  - Remove address in account.
    /****************************************************************************************/
    Route::post('/remove-address'         , 'ProfileController@removeAddress');





    /****************************************************************************************
    *  Remove customer phone number trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = phone_number
    *
    *  Optional param: - NO
    *
    *  Usage  - Remove customer phone number in account.
    /****************************************************************************************/
    Route::post('/remove-phone-number'           , 'ProfileController@removePhone');





    /****************************************************************************************
    *  Get order details from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = order_id - Which order data should be returned. (order id)
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer orders history in account.
    /****************************************************************************************/
    Route::get('/get-order-details', 'ProfileController@getOrderDetails');





    /****************************************************************************************
    *  Get customer default address from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get customer defaul address in account.
    /****************************************************************************************/
    Route::get('/get-customer-default-address'       , 'ProfileController@getCustomerDefaultAddress');





    /****************************************************************************************
    *  Get customer default phone number from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get customer defaul phone number in account.
    /****************************************************************************************/
    Route::get('/get-customer-default-phone-number'     , 'ProfileController@getCustomerDefaultPhoneNumber');





    /****************************************************************************************
    *  Change default address
    *
    *  Required param:
    *          = address_id - Address ID
    *
    *  Optional param - NO
    *
    *  Usage  - change default address in account.
    /****************************************************************************************/
    Route::post('/change-default-address'        , 'ProfileController@changeDefaultAddress');





    /****************************************************************************************
    *  Change default Phone number
    *
    *  Required param:
    *          = phone_id - Phone number ID
    *
    *  Optional param - NO
    *
    *  Usage  - change default phone number in account.
    /****************************************************************************************/
    Route::post('/change-default-phone-number'    , 'ProfileController@changeDefaultPhoneNumber');





    /****************************************************************************************
    *  Edit customer address trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = address
    *          = city
    *          = address_id
    *  Optional param: - NO
    *
    *  Usage  - Edit customer address in account.
    /****************************************************************************************/
    Route::post('/edit-customer-address'         , 'ProfileController@editCustomerAddress');





    /****************************************************************************************
    *  Edit customer phone number trough  JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param:
    *          = phone_number
    *          = phone_id
    *  Optional param: - NO
    *
    *  Usage  - Edit phone nubers in account.
    /****************************************************************************************/
    Route::post('/edit-customer-phone-number'         , 'ProfileController@editCustomerPhoneNumber');





    /****************************************************************************************
    *  User logout.
    *
    *  Required param - NO
    *
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer logout.
    /****************************************************************************************/
    Route::post('/get-password-reset'     , 'ProfileController@submitForgetPasswordForm');





    /****************************************************************************************
    *  User logout.
    *
    *  Required param - NO
    *
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer logout.
    /****************************************************************************************/
    Route::get('/get-token'     , 'ProfileController@getToken');





    /****************************************************************************************
    *  User logout.
    *
    *  Required param:
    *          = token                 - reset token
    *          = password              - password
    *          = password_confirmation - confirm password
    *
    *  Optional param - NO
    *
    *  Usage  - commonly used for customer logout.
    /****************************************************************************************/
    Route::post('/password-reset'     , 'ProfileController@passwordResetForm');





    /****************************************************************************************
    *  Change account details.
    *
    *  Required param:
    *          = customer_name      - Customer name
    *
    *  Optional param:
    *          = password-required     -
    *          = old_password          - old password
    *          = password              - password.
    *          = password_confirmation - password confirmation.
    *
    *  Usage  - commonly used for customer logout.
    /****************************************************************************************/
    Route::post('/change-account-details'     , 'ProfileController@changeAccountDetails');


/*
|--------------------------------------------------------------------------
| Web Route / CheckoutController
|--------------------------------------------------------------------------
*/

    /****************************************************************************************
    *  Get checkout cities.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *
    *  Optional param - NO
    *
    *  Usage  - commonly used to get checkout cities in any place.
    /****************************************************************************************/
    Route::get('/get-checkout-city'            , 'CheckoutController@getCheckoutCity');





    /****************************************************************************************
    *  Place order.
    *
    *  Required param:
    *          = franchise - Which franchise order should be place. (franchise id)
    *          = name      - Customer name.
    *          = address   - Customer address.
    *          = city      - Customer city.
    *          = phone     - Customer phone
    *          = items     - Order items
    *          = $payment_method - Payment method.
    *
    *  Optional param:
    *          = email          - customer email.
    *          = customer_notes - customer notes .
    *
    *  Usage  - commonly used to place order.
    /****************************************************************************************/
    Route::post('/create-order'           , 'CheckoutController@createOrder');





    /****************************************************************************************
    *  Get order success details.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = search_code - Order search code. (Search code)
    *
    *  Optional param - NO
    *
    *  Usage  - get order success detals in orde success page.
    /****************************************************************************************/
    Route::get('/get-order-success-details', 'CheckoutController@getOrderSuccessDetails');





    /****************************************************************************************
    *  Get order tracking details.
    *
    *  Required param:
    *          = searchcode - Order earch code
    *          = phone      - Phone number. (Search code)
    *
    *  Optional param - NO
    *
    *  Usage  - get order traking detals in orde trackng page.
    /****************************************************************************************/
    Route::get('/get-order-tracking-details'     , 'CheckoutController@getOdertrackingdetails');





/*
|--------------------------------------------------------------------------
| Web Route / CheckoutController
|--------------------------------------------------------------------------
*/





    /****************************************************************************************
    *  Create newsletter.
    *
    *  Required param:
    *          = franchise - Which franchise items should be returned. (franchise id)
    *          = email     - Newsletter email
    *
    *  Optional param - NO
    *
    *  Usage  - create newsletter in page footer.
    /****************************************************************************************/
    Route::post('/create-newsletter'     , 'ActionController@createNewsletter');



    //......................................................................................
    //.PPPPPPPPPPP..........................yHHHH....HHHHH..................................
    //.PPPPPPPPPPPP.........................yHHHH....HHHHH..................................
    //.PPPPPPPPPPPPP........................yHHHH....HHHHH..................................
    //.PPPP...PPPPPP........................yHHHH....HHHHH..................................
    //.PPPP....PPPPP..aaaaaaaa.aayyy...yyyyyyHHHH....HHHHH..eeeeeeee...errrrrrr.eeeeeeee....
    //.PPPP...PPPPPP.Paaaaaaaaaaayyy...yyyy.yHHHH....HHHHH.Heeeeeeeee..errrrrrrreeeeeeeee...
    //.PPPPPPPPPPPPPPPaaa.aaaaa.ayyy..yyyyy.yHHHHHHHHHHHHH.Heeeeeeeee..errrrrrrreeeeeeeee...
    //.PPPPPPPPPPPP....aaaaaaaa.ayyyy.yyyyy.yHHHHHHHHHHHHH.Heee..eeeee.errrr...reee..eeeee..
    //.PPPPPPPPPPP...Paaaaaaaaa..yyyy.yyyy..yHHHHHHHHHHHHHHHeeeeeeeeee.errrr..rreeeeeeeeee..
    //.PPPP.........PPaaaaaaaaa..yyyyyyyyy..yHHHH....HHHHHHHeeeeeeeeee.errr...rreeeeeeeeee..
    //.PPPP.........PPaaa..aaaa..yyyyyyyy...yHHHH....HHHHHHHeee........errr...rreee.........
    //.PPPP.........PPaa..aaaaa...yyyyyyy...yHHHH....HHHHH.Heee..eeeee.errr....reee..eeeee..
    //.PPPP.........PPaaaaaaaaa...yyyyyyy...yHHHH....HHHHH.Heeeeeeeee..errr....reeeeeeeee...
    //.PPPP.........PPaaaaaaaaa...yyyyyy....yHHHH....HHHHH.Heeeeeeeee..errr....reeeeeeeee...
    //.PPPP..........Paaaaaaaaaa...yyyyy....yHHHH....HHHHH..eeeeeeee...errr.....eeeeeeee....
    //.............................yyyyy....................................................
    //..........................ayyyyyy.....................................................
    //..........................ayyyyyy.....................................................
    //..........................ayyyyy......................................................
    //......................................................................................
    //
    //  Required param - No
    //
    //  Optional param - NO
    //
    //  Usage  - change default phone number in account.
    //*************************************************************************************/
    Route::post('/payhere-notify'    , 'CheckoutController@payhereNotify');



    /*
    |--------------------------------------------------------------------------
    | Web Route / Abandoned API
    |--------------------------------------------------------------------------
    */

    // Route::get('/shop/categories'             , 'ProductController@get_categories');

    // Route::get('/categories'                  , 'ProductController@get_popularCategories');

    // Route::get('/mobilelink'                  , 'ProductController@get_mobile_navigation_ink_category');


    /****************************************************************************************
    * Get single address from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer address in account.
    /****************************************************************************************/
    // Route::get('/address'                , 'ProfileController@getAddress');





    /****************************************************************************************
    *  Get order history from JWT key.
    *
    *  Required header:
    *          =  authorization - JWT Token
    *  Required param: - NO
    *
    *  Optional param: - NO
    *
    *  Usage  - Get loged customer orders history in account.
    /****************************************************************************************/
    // Route::get('/get-recent-orders'           , 'ProfileController@getOrderHistory');



    // Route::get('/search-data'                 , 'ProductController@getSearchData');

    // Route::get('/search/suggestions'          , 'ProductController@get_search_Suggestions');


    // Route::post('/getcoupon'    , 'CheckoutController@getCoupon');
});





