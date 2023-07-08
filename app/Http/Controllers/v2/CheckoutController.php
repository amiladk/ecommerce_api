<?php

namespace App\Http\Controllers\v2;
use App\Http\Controllers\Controller;

use App\Models\checkoutr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Validator;


use App\Models\customerAddress;
use App\Models\productVariation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\City;
use App\Models\customerPhoneNumber;
use App\Models\franchiseProductMap;
use App\Models\Discount;
use App\Models\discountProductMap;
use App\Models\Images;
use App\Models\searchCode;
use App\Models\WebsiteSettingsMeta;
use App\Models\WebsiteSettings;

class CheckoutController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Public function / Get checkout cities
    |--------------------------------------------------------------------------
    */
    public function getCheckoutCity(Request $request)
    {
        try {

                if(!$request->has('franchise')){
                    return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
                }

                $franchise = $request ->franchise;

                $query = DB::table('city')
                            ->Join('franchise_shipping_zone_map', 'city.shipping_zone', '=', 'franchise_shipping_zone_map.shipping_zone')
                            ->where('franchise_shipping_zone_map.franchise', $franchise)
                            ->select('city.id','city.city','franchise_shipping_zone_map.franchise_shipping_cost','franchise_shipping_zone_map.franchise_charge_for_additional_kg',
                                    'franchise_shipping_zone_map.service_charge','franchise_shipping_zone_map.charge_for_additional_kg')
                            ->get();

                if(empty($query)){
                    return response(['success' => true,'data'=>  null ,'message' => 'Data not available'], 200);
                }

                foreach ($query as $key => $city) {

                    $cities[$key] = array(
                        'id'=>$key,
                        'cityId'=>$city->id,
                        'text'=> $city->city,
                        'shipping_cost'=> $city->franchise_shipping_cost,
                        'additional_kg_charge'=> $city->franchise_charge_for_additional_kg
                    );

                }


                if($query){
                    return response(['success' => true,'data'=> $cities,'message' => "Everything is good!. Data found successfully"], 200);
                }else{
                    return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
                }

            } catch (\Exception $e) {
                return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
            }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / Create order
    |--------------------------------------------------------------------------
    */
    public function createOrder(Request $request){

        $validation_array = [
            "name"            => 'required',
            "address"         => 'required',
            "city"            => 'required',
            "items"           => 'required',
            "payment_method"  => 'required',
            "franchise"       => 'required',
            "email"           => 'nullable|email',
            "customer_notes"  => 'nullable',
            "coupon_code"     => 'nullable',
        ];

        $validator = Validator::make($request->all(), $validation_array);

        if($validator->fails()){
            return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);
        }


        $name          =$request->name;
        $address       =$request->address;
        $city          =$request->city;
        $phone         =$request->phone;
        $items         =$request->items;
        $payment_method=$request->payment_method;
        $franchise     =$request->franchise;
        $email         =$request->email;
        $customer_notes=$request->customer_notes;
        $coupon_code   =$request->coupon_code;

        //return response(['success' => false,'data'=> $coupon_code], 200);

        // $sub           =($request->header('Authorization')) ? $sub = $this->getAuthorization($request) : null;

        if($request->header('Authorization')){
            $sub =$this->getAuthorization($request)['sub'];
        }else{
            $sub =null;
        }

        $discount_products = null;
        $discount = Discount::with('getDiscountProductMap')->where('valid_until','>=',date("Y-m-d"))->where('discount_code',$coupon_code)->first();
        if($discount && count($discount->getDiscountProductMap) > 0){
            $discount_products = $discount->getDiscountProductMap;
        }

        try {

            DB::beginTransaction();


            $search_code  = $this->generateOrderSearchCode($franchise);
            $get_total    = $this->getTotal($items,$city,$franchise,$discount_products);

            $orderId=DB::table('order')->insertGetId(
                ['search_code'             => $search_code,
                    'franchise_shipping_cost' => $get_total['franchise_shipping_cost'],
                    'total_amount'            => $get_total['total_amount'],
                    'total_discount'          => $get_total['total_discount'],
                    'grand_total'             => $get_total['grand_total'],
                    'total_commision'         => $get_total['total_commision'],
                    'total_cost'              => $get_total['total_cost'],
                    'service_charge'          => $get_total['service_charge'],
                    'item_description'        => $get_total['item_description'],
                    'address'                 => $address,
                    'resipient'               => $name,
                    'phone_one'               => $phone,
                    'total_weight'            => $get_total['total_weight'],
                    'payment_type'            => $payment_method,
                    'city'                    => $city,
                    'franchise'               => $franchise,
                    'order_status'            => 'pending',
                    'last_modified'           => date('Y-m-d H:i:s'),
                    'customer'                => $sub,
                    'email'                   => $email,
                    'customer_notes'          => $customer_notes]
            );


            if($payment_method == 'PP'){
                $merchant_id      = $this->getSettings($franchise,'merchant_id');
                $order_id         = $search_code;
                $payhere_amount   = sprintf("%0.2f", $get_total['grand_total'],2);
                $payhere_currency = config('myapp.payhere_currency');
                $payhere_secret   = $this->getSettings($franchise,'payhere_secret') ;

                $hash = strtoupper (md5 ( $merchant_id . $order_id . $payhere_amount . $payhere_currency . strtoupper(md5($payhere_secret)) ) );
            }else{
                $hash = null;
            }


            $response     = array(
                'order_id'     => $search_code,
                'items'        => $get_total['item_description'],
                'amount'       => $get_total['grand_total'],
                'customer_name'=> $name,
                'hash'         => $hash
            );

            $this->insertOrdertems($orderId,$items,$city,$franchise,$discount_products);

            DB::commit();

            if($orderId){
                return response()->json(['success' => true,'data'=> $response,'message' => 'Everything good! Order placed successfully!'], 201);
            }

        } catch (\Throwable $e) {
            DB::rollback();
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }





















    /*
    |--------------------------------------------------------------------------
    | Public function / Order Success details
    |--------------------------------------------------------------------------
    */
    public function getOrderSuccessDetails(Request $request){

        try {

            if(!$request->has('franchise')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. franchise is required.",], 500);
            }

            if(!$request->has('search_code')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. slug is required.",], 500);
            }

            $franchise   = $request ->franchise;
            $search_code = $request ->search_code;

            $query = Order::where('franchise', $franchise)
                      ->where('order.search_code', $search_code)
                      ->first();

            if(empty($query)){
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }


            $order_items = DB::table('order_items')
                            ->Join('product', 'product.id', '=', 'order_items.product')
                            ->where('order_items.order', $query->id)
                            ->select('product.id','product.slug','product.title','product.price', 'order_items.quantity','order_items.amount', 'order_items.product_variation')
                            ->get();

            $options= array(
                'label'=> "label",
                'value'=> "value"
            );

            $sub_total=0;
            $items =array();

            foreach ($order_items as $key => $item) {

                $sub_total+=$item->amount;

                $product_images = $this->getProductImageById($item->id);

                if($item->product_variation){
                    $variation = productVariation::find($item->product_variation);
                    $name      = $item->title.' ('.$variation->title.')';
                }else{
                    $name      = $item->title;
                }


                $items[$key]=array(
                    'id'      => $item->id,
                    'slug'    => $item->slug,
                    'name'    => $name,
                    'image'   => $product_images,
                    'options' => $options,
                    'price'   => $item->amount,
                    'quantity'=> $item->quantity,
                    'total'   => $item->amount,
                );
            }

            $additionalLines=array(
                'label'=> "Shipping",
                'total'=> $query->franchise_shipping_cost,
            );


            $Address = array(
                'default'  => true,
                'firstName'=> $query->resipient,
                'lastName' => $query->resipient,
                'email'    => "nadeera@gmail.com",
                'phone'    => $query->phone_one,
                'country'  => "Sri Lanka",
                'city'     => "Colombo",
                'postcode' => "10300",
                'address'  => $query->address,
            );

            $merchant_id      = config('myapp.merchant_id');
            $order_id         = $query->search_code;
            $payhere_amount   = sprintf("%0.2f", $query->grand_total);
            $payhere_currency = config('myapp.payhere_currency');
            $payhere_secret   = config('myapp.payhere_secret');

            $hash = strtoupper (md5 ( $merchant_id . $order_id . $payhere_amount . $payhere_currency . strtoupper(md5($payhere_secret)) ) );

            $order =array(
                'id'               => $query->search_code,
                'date'             => date('Y-m-d', strtotime($query->created_date)),
                'status'           => $query->order_status,
                'items'            => $items,
                'additionalLines'  => array($additionalLines),
                'quantity'         => 10,
                'subtotal'         => $sub_total,
                'total'            => $query->grand_total,
                'coupon'           => $query->total_discount,
                'paymentMethod'    => $query->payment_type,
                'shippingAddress'  => $Address,
                'billingAddress'   => $Address,
                'item_description' => $query->item_description,
                'hash'             => $hash,
            );

            if($query){
                return response(['success' => true,'data'=> $order,'message' => "Everything is good!. Data found successfully"], 200);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / Get order trackng details
    |--------------------------------------------------------------------------
    */
    public function getOdertrackingdetails(Request $request){

        try {
                if(!$request->has('searchcode')){
                    return response(['success' => false,'data'=> null,'message' => "Opps!. Searchcode is required.",], 500);
                }

                if(!$request->has('phone')){
                    return response(['success' => false,'data'=> null,'message' => "Opps!. Phone is required.",], 500);
                }

                $searchcode = $request ->searchcode;
                $phone      = $request ->phone;


                $query = Order::with('courierdata','franchisedata','citydata')->where('order.search_code', $searchcode)
                                ->where('order.order_status', 'in_courier')
                                ->where(function ($query) use ($phone) {
                                    $query->where  ('phone_one', '=', $phone)
                                        ->orWhere('phone_two', '=', $phone);
                                })
                                ->first();

                if($query){

                    $order_items = DB::table('order_items')
                                    ->Join('product', 'product.id', '=', 'order_items.product')
                                    ->where('order_items.order', $query->id)
                                    ->select('product.id','product.slug','product.title','product.price',
                                            'order_items.quantity','order_items.amount', 'order_items.product_variation')
                                    ->get();



                    $courier_logo =  $this->getImage($query->courierdata['logo']);
                    $seller_logo  =  $this->getImage($query->franchisedata['logo']);

                    $options= array(
                        'label'=> "label",
                        'value'=> "value"
                    );

                    $sub_total=0;
                    $items =array();
                    foreach ($order_items as $key => $item) {

                        $sub_total+=$item->amount;

                        $product_images = $this->getProductImageById($item->id);

                        if($item->product_variation){
                            $variation = productVariation::find($item->product_variation);
                            $name      = $item->title.' ('.$variation->title.')';
                        }else{
                            $name      = $item->title;
                        }


                        $items[$key]=array(
                            'id'      => $item->id,
                            'slug'    => $item->slug,
                            'name'    => $name,
                            'image'   => $product_images,
                            'options' => $options,
                            'price'   => $item->amount,
                            'quantity'=> $item->quantity,
                            'total'   => $item->amount,
                        );
                    }
                    $additionalLines=array(
                        'label'=> "Shipping",
                        'total'=> $query->franchise_shipping_cost,
                    );

                    $Address = array(
                        'default'  => true,
                        'firstName'=> $query->resipient,
                        'lastName' => $query->resipient,
                        'email'    => "nadeera@gmail.com",
                        'phone'    => $query->phone_one,
                        'country'  => "Sri Lanka",
                        'city'     => $query->citydata['city'],
                        'postcode' => "10300",
                        'address'  => $query->address,
                    );


                    $courier =array(
                        'logo'        => config('myapp.base_url').$courier_logo->image_name,
                        'name'        => $query->courierdata['name'],
                        'trackingCode'=> $query->courier_reference,
                        'phone'       => $query->courierdata['phone_one'],

                    );

                    $image = base64_encode(file_get_contents(config('myapp.base_url').$seller_logo->image_name));

                    $seller =array(
                        'logo'        => config('myapp.base_url').$seller_logo->image_name,
                        'logo_encode' => $image,
                        'name'        => $query->franchisedata['name'],
                        'phone'       => $query->franchisedata['phone_one'],
                    );

                    $Order =array(
                        'id'               => $query->search_code,
                        'date'             => date('Y-m-d', strtotime($query->created_date)),
                        'dispatched_date'  => date('M d, Y', strtotime($query->dispatched_date)),
                        'courier_reference'=> $query->courier_reference,
                        'status'           => $query->order_status,
                        'items'            => $items,
                        'additionalLines'  => array($additionalLines),
                        'quantity'         => 10,
                        'subtotal'         => $sub_total,
                        'total'            => $query->grand_total,
                        'coupon'           => $query->total_discount,
                        'paymentMethod'    => $query->payment_type,
                        'shippingAddress'  => $Address,
                        'billingAddress'   => $Address,
                        'item_description' => $query->item_description,
                        'hash'             => null,
                        'courier'          => $courier,
                        'seller'           => $seller,
                    );
                    return response(['success' => true,'data'=> $Order,'message' => "Everything is good!. Data found successfully"], 200);
                }else{
                    return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
                }

            } catch (\Exception $e) {
                DB::rollback();
                return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
            }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / payhereNotify
    |--------------------------------------------------------------------------
    */
    public function payhereNotify(Request $request){

        $order     =  Order::where('search_code', $_POST['order_id']);
        $orderData =  $order->first();

        $merchant_id          = $_POST['merchant_id'];
        $order_id             = $_POST['order_id'];
        $payhere_amount       = $_POST['payhere_amount']; //order table eke grand_total eka ganna
        $payhere_currency     = $_POST['payhere_currency'];
        $status_code          = $_POST['status_code'];
        $md5sig               = $_POST['md5sig'];

        $merchant_secret      = $this->getSettings($orderData->franchise,'payhere_secret') ; // Replace with your Merchant Secret (Can be found on your PayHere account's Settings page)
        $local_md5sig         = strtoupper (md5 ( $merchant_id . $order_id . $payhere_amount . $payhere_currency . $status_code . strtoupper(md5($merchant_secret)) ) );

        if (($local_md5sig === $md5sig) AND ($status_code == 2)){
                   $order->update(['order_status' => 'confirm', 'payment_type'=>'AP']);
        }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / getCoupon
    |--------------------------------------------------------------------------
    */
    public function getCoupon(Request $request){

        $payLoad = json_decode(request()->getContent(), true);

        $today  = Carbon::now()->toDateString();
        $coupon = $payLoad['coupon'];
        $items  = $payLoad['items'];

        $data =Discount::with('Product')->where('discount_code',$coupon)->first();

        if($data){

            if($data->valid_until >= $today){

                if($data->max_count > $data->current_count){

                    foreach ($items as $key => $val) {
                        if($val['product_id'] == $data->product['product_id']){
                            $response =  array(
                                'amount'      => $data->amount,
                                'product'     => $data->product->product_id,
                                'coupon_code' => $data->discount_code,
                            );
                            return response()->json(['message' => 'exist','type' => 'success','data' => $response], 201);
                        }
                    }
                    return response()->json(['message' => 'Oops!. Coupon is only applied for selected products.','type' => 'error'], 201);

                }else{
                    return response()->json(['message' => 'Oops!. Coupon limit exceeded.','type' => 'error'], 201);
                }
            }else{
                return response()->json(['message' => 'Oops!. Coupon expired.','type' => 'error'], 201);
            }
        }else{
            return response()->json(['message' => 'Oops!. Invalid coupon.','type' => 'error'], 201);
        }

        return;
    }









    public function get_PageAddresses(Request $request)
    {

        $sub =$this->getAuthorization($request);

        $query = DB::table('customer_address')
                    ->leftJoin('city', 'city.id', '=', 'customer_address.city')
                    ->where('customer_address.customer', $sub)
                    ->select('customer_address.id','customer_address.address' ,
                             'city.city','city.zip_code','customer_address.is_default' )
                    ->orderBy('customer_address.is_default', 'desc')
                    ->get();
    if($query){
        $Address=array();
        foreach ($query as $key => $address) {

            $default=($address->is_default==1) ? true : false;

            $Address[$key] = array(
                'id' => $address->id,
                'default'  => $default,
                'country'  => "Sri Lanka",
                'city'     => $address->city,
                'postcode' => $address->zip_code,
                'address'  => $address->address,
            );

        }
        return response()->json($Address);
    } else{
        return null;
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
    | Prvate function / Insert order items
    |--------------------------------------------------------------------------
    */
    private function insertOrdertems($orderId,$items,$city,$franchise,$discount_products)
    {
        foreach ($items as $key => $item) {

            $product=$this->getProductQuery($franchise)->where('product.id', $item['product_id'])->first();


            $variationId =null;


            if(isset($item['variationId'])){

                $franchiseProduct=franchiseProductMap::where('franchise', $franchise)
                                                       ->where('product',$product->id)
                                                       ->where('product_variation',$item['variationId'])
                                                       ->first();

                $variationId=$item['variationId'];

                $variation = productVariation::find($item['variationId']);

                $amount    = $franchiseProduct->price     * $item['quantity'];
                $commision = $franchiseProduct->commision * $item['quantity'];
                $cost      = $variation->cost             * $item['quantity'];
            }else{

                $franchiseProduct=franchiseProductMap::where('franchise', $franchise)
                                                       ->where('product',$product->id)
                                                       ->first();

                $amount    = $franchiseProduct->price     * $item['quantity'];
                $commision = $franchiseProduct->commision * $item['quantity'];
                $cost      = $product->cost               * $item['quantity'];

            }

            $discount=null;
            if($discount_products != null){
                foreach($discount_products as $data){
                    if($data->product_id == $item['product_id']){
                        $discount = $data->value;
                    }
                }
            }

            DB::table('order_items')->insert(
                ['order'             => $orderId,
                 'product'           => $item['product_id'],
                 'product_variation' => $variationId,
                 'quantity'          => $item['quantity'],
                 'amount'            => $amount,
                 'commision'         => $commision,
                 'cost'              => $cost,
                 'discount'          => $discount]
            );
        }
        return;
    }









    /*
    |--------------------------------------------------------------------------
    | Prvate function / Generate order search code
    |--------------------------------------------------------------------------
    */
    private function generateOrderSearchCode($franchise)
    {
        $query = searchCode::where('search_code.franchise', $franchise)
                    ->select('search_code.order_prefix','search_code.last_order')
                    ->first();

        $search_code = $query->order_prefix."-".($query->last_order+1);

        searchCode::increment('search_code.last_order', 1);

        return $search_code;
    }









    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get total
    |--------------------------------------------------------------------------
    */
    private function getTotal($items,$city,$franchise,$discount_products)
    {   //define array
        $summary =array('total_amount'=>0,'total_discount'=>0,'total_cost'=>0,'total_weight'=>0,'total_commision'=>0,'franchise_shipping_cost'=>0,'item_description'=>'','service_charge'=>0,'grand_total'=>0);
        //find weight & amount
        foreach ($items as $key => $item)
        {
            $product=$this->getProductQuery($franchise)->where('product.id', $item['product_id'])->first();


            if(isset($item['variationId'])){
                $franchiseProduct=franchiseProductMap::where('franchise', $franchise)
                                                      ->where('product',$product->id)
                                                      ->where('product_variation',$item['variationId'])
                                                      ->first();

                $variation = productVariation::find($item['variationId']);

                $summary['total_amount']    += $franchiseProduct->price     * $item['quantity'];
                $summary['total_commision'] += $franchiseProduct->commision * $item['quantity'];
                $summary['total_cost']      += $variation->cost             * $item['quantity'];
                $summary['item_description'].= $product->short_title."(".$variation->title.")-".$item['quantity']."| ";
            }else{
                $franchiseProduct=franchiseProductMap::where('franchise', $franchise)
                                                     ->where('product',$product->id)
                                                     ->first();

                $summary['total_amount']    += $franchiseProduct->price     * $item['quantity'];
                $summary['total_commision'] += $franchiseProduct->commision * $item['quantity'];
                $summary['total_cost']      += $product->cost               * $item['quantity'];
                $summary['item_description'].= $product->short_title."-".$item['quantity']."| ";
            }

            $summary['total_weight']   += $product->weight    * $item['quantity'];

            $coupon = $this->getCouponAmount($item['product_id'],$item['coupon']);

            //$summary['total_discount'] += $coupon;

            if($discount_products != null){
                foreach($discount_products as $data){
                    if($data->product_id == $item['product_id']){
                        $summary['total_discount'] += $data->value;
                    }
                }
            }
        }
        // find shipping cost
        $shipping_cost = DB::table  ('city')
                            ->join  ('franchise_shipping_zone_map', 'franchise_shipping_zone_map.shipping_zone', '=', 'city.shipping_zone')
                            ->where ('city.id', $city)
                            ->where ('franchise_shipping_zone_map.franchise',$franchise)
                            ->select('franchise_shipping_zone_map.franchise_shipping_cost','franchise_shipping_zone_map.franchise_charge_for_additional_kg',
                                     'franchise_shipping_zone_map.service_charge','franchise_shipping_zone_map.charge_for_additional_kg')
                            ->first ();

		$kg = ceil($summary['total_weight']/ 1000);
		if ($kg <= 1) {
            $summary['franchise_shipping_cost']= $shipping_cost->franchise_shipping_cost;
            $summary['service_charge']= $shipping_cost->service_charge;
		}else{
			$summary['franchise_shipping_cost']= $shipping_cost->franchise_shipping_cost + ($shipping_cost->franchise_charge_for_additional_kg * ($kg-1)); //basic charge + additional fee
            $summary['service_charge']= $shipping_cost->franchise_shipping_cost + ($shipping_cost->charge_for_additional_kg * ($kg-1));
        }
        //item description
        $summary['item_description'] = rtrim($summary['item_description'],"| ");

        //find grand total
        $summary['grand_total'] =$summary['franchise_shipping_cost'] + $summary['total_amount'] - $summary['total_discount'];

        return $summary;
    }










    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get coupon amount
    |--------------------------------------------------------------------------
    */
    private function getCouponAmount($product,$coupon){

        $today  = Carbon::now()->toDateString();

        $data =Discount::with('Product')->where('discount_code',$coupon)->first();

        if($data){

            if($data->valid_until >= $today){
                if($data->max_count > $data->current_count){

                    if($data->product['product_id']==$product){
                        Discount::find($data->id)->increment('current_count');
                        return $data->amount;
                    }
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }else{
            return 0;
        }
        return 0;
    }










    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get Image
    |--------------------------------------------------------------------------
    */
    private function getImage($id){

        $image = Images:: find($id);

        return $image;
    }










    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get product image by id
    |--------------------------------------------------------------------------
    */
    private function getProductImageById($id)
    {
        $product_images = DB::table('product_images')
                        ->join('image', 'image.id', '=', 'product_images.image')
                        ->select(DB::raw('CONCAT("'.config('myapp.base_url').'", image.image_name) AS image_name'))
                        ->where('product', $id)->limit(1)
                        ->pluck('image.image_name');

        return ($product_images);
    }










    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get product query
    |--------------------------------------------------------------------------
    */
    private function getProductQuery($franchise)
    {
        $query = DB::table('product')
                            ->join  ('franchise_product_map', 'franchise_product_map.product', '=', 'product.id')
                            ->where('franchise_product_map.franchise', $franchise)
                            ->select('product.id','product.title','product.short_title', 'product.slug', 'product.brand'
                                    ,'product.sku','franchise_product_map.price','product.stock','product.cost'
                                    ,'product.short_description','product.long_description'
                                    ,'product.sinhala_long_description','product.old_price','franchise_product_map.price','franchise_product_map.commision','product.weight');

         return $query;
    }










    /*
    |--------------------------------------------------------------------------
    | Prvate function / Get Authorization
    |--------------------------------------------------------------------------
    */
    private function getAuthorization($request)
    {
        $token   = $request->header('authorization');
        $payload = JWTAuth::setToken($token)->getPayload();
        $sub     = $payload->get();

        return $sub;
    }



    private function getSettings($franchise,$field){

        $settings = WebsiteSettings::whereHas('meta',function ($query) use ($field) {
            $query->where('field', $field);
        })->where('franchise', $franchise)->first();

        return ($settings) ? $settings->value : null;
    }
}
