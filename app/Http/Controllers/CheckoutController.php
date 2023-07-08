<?php

namespace App\Http\Controllers;

use App\Models\checkoutr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

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

class CheckoutController extends Controller
{
    public function get_CheckoutCity()
    {
        $query = DB::table('city')
                    ->Join('franchise_shipping_zone_map', 'city.shipping_zone', '=', 'franchise_shipping_zone_map.shipping_zone')
                    ->where('franchise_shipping_zone_map.franchise', config('myapp.franchise'))
                    ->select('city.id','city.city','franchise_shipping_zone_map.franchise_shipping_cost','franchise_shipping_zone_map.franchise_charge_for_additional_kg',
                             'franchise_shipping_zone_map.service_charge','franchise_shipping_zone_map.charge_for_additional_kg')
                    ->get();         

                    foreach ($query as $key => $city) {                       
            
                        $Cities[$key] = array(
                            'id'=>$key,
                            'cityId'=>$city->id,
                            'text'=> $city->city,  
                            'shipping_cost'=> $city->franchise_shipping_cost,
                            'additional_kg_charge'=> $city->franchise_charge_for_additional_kg
                        );
            
                    }                             

        return response()->json($Cities);
    } 
    
    
    public function place_order(Request $request){
       
       if($request->header('Authorization')){
        $sub =$this->get_authorization($request);
        $payLoad = json_decode(request()->getContent(), true);
        $name          =$payLoad['name'];
        $address       =$payLoad['address'];
        $city          =$payLoad['city'];
        $phone         =$payLoad['phone'];
        $items         =$payLoad['items'];
        $payment_method=$payLoad['payment_method']; 
       }else{
        $name          =$request->name;
        $address       =$request->address;
        $city          =$request->city;
        $phone         =$request->phone;
        $items         =$request->items; 
        $payment_method=$request->payment_method; 
        $sub           = null;
       }
       
        $search_code  = $this->generate_order_search_code();
        $get_total    = $this->get_total($items,$city);
    

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
             'franchise'               => config('myapp.franchise'),
             'order_status'            => 'pending',
             'last_modified'           => date('Y-m-d H:i:s'),
             'customer'                => $sub]
        );


        $merchant_id      = config('myapp.merchant_id');  
        $order_id         = $search_code; 
        $payhere_amount   = sprintf("%0.2f", $get_total['grand_total']);    
        $payhere_currency = config('myapp.payhere_currency');
        $payhere_secret   = config('myapp.payhere_secret');

        $hash = strtoupper (md5 ( $merchant_id . $order_id . $payhere_amount . $payhere_currency . strtoupper(md5($payhere_secret)) ) );


        $response     = array(
            'order_id'     => $search_code,
            'items'        => $get_total['item_description'],
            'amount'       => sprintf('%.02f', round($get_total['grand_total'], 2)),
            'customer_name'=> $name,
            'hash'=> $hash
        );           
        
    $this->insert_order_items($orderId,$items,$city);    
       
    return response()->json(['message' => 'User successfully registered','data' => $response], 201);
    }

    private function insert_order_items($orderId,$items,$city)
    {
        foreach ($items as $key => $item) {

            $product=$this->get_product_query()->where('product.id', $item['product_id'])->first();                        

            
            $variationId =null;


            if(isset($item['variationId'])){

                $franchiseProduct=franchiseProductMap::where('franchise', config('myapp.franchise'))
                                                       ->where('product',$product->id)
                                                       ->where('product_variation',$item['variationId']) 
                                                       ->first();

                $variationId=$item['variationId'];

                $variation = productVariation::find($item['variationId']);

                $amount    = $franchiseProduct->price     * $item['quantity'];
                $commision = $franchiseProduct->commision * $item['quantity'];
                $cost      = $variation->cost             * $item['quantity'];
            }else{

                $franchiseProduct=franchiseProductMap::where('franchise', config('myapp.franchise'))
                                                       ->where('product',$product->id)
                                                       ->first();

                $amount    = $franchiseProduct->price     * $item['quantity'];
                $commision = $franchiseProduct->commision * $item['quantity'];
                $cost      = $product->cost               * $item['quantity'];
                
            }

            

            DB::table('order_items')->insert(
                ['order'             => $orderId,
                 'product'           => $item['product_id'],
                 'product_variation' => $variationId,
                 'quantity'          => $item['quantity'],
                 'amount'            => $amount,
                 'commision'         => $commision,
                 'cost'              => $cost]
            );                      
        }
        return;
    }


    private function generate_order_search_code()
    {
        $query = DB::table('search_code')
                    ->where('search_code.franchise', config('myapp.franchise'))
                    ->select('search_code.order_prefix','search_code.last_order')
                    ->first();

        $search_code = $query->order_prefix."-".($query->last_order+1); 
        
        DB::table('search_code')->increment('search_code.last_order', 1);
        
        return $search_code;
    }


    private function get_total($items,$city)
    {   //define array
        $summary =array('total_amount'=>0,'total_discount'=>0,'total_cost'=>0,'total_weight'=>0,'total_commision'=>0,'franchise_shipping_cost'=>0,'item_description'=>'','service_charge'=>0,'grand_total'=>0);
        //find weight & amount
        foreach ($items as $key => $item) 
        { 
            $product=$this->get_product_query()->where('product.id', $item['product_id'])->first();

          
            if(isset($item['variationId'])){
                $franchiseProduct=franchiseProductMap::where('franchise', config('myapp.franchise'))
                                                      ->where('product',$product->id)
                                                      ->where('product_variation',$item['variationId']) 
                                                      ->first();

                $variation = productVariation::find($item['variationId']);                                      
              
                $summary['total_amount']    += $franchiseProduct->price     * $item['quantity'];
                $summary['total_commision'] += $franchiseProduct->commision * $item['quantity'];
                $summary['total_cost']      += $variation->cost             * $item['quantity'];
                $summary['item_description'].= $product->short_title."(".$variation->title.")-".$item['quantity']."| ";
            }else{
                $franchiseProduct=franchiseProductMap::where('franchise', config('myapp.franchise'))
                                                     ->where('product',$product->id)
                                                     ->first();

                $summary['total_amount']    += $franchiseProduct->price     * $item['quantity'];
                $summary['total_commision'] += $franchiseProduct->commision * $item['quantity'];
                $summary['total_cost']      += $product->cost               * $item['quantity'];
                $summary['item_description'].= $product->short_title."-".$item['quantity']."| ";
            }

            $summary['total_weight']   += $product->weight    * $item['quantity'];

            $coupon = $this->getCouponAmount($item['product_id'],$item['coupon']);

            $summary['total_discount'] += $coupon;
            
        }
        // find shipping cost
        $shipping_cost = DB::table  ('city')
                            ->join  ('franchise_shipping_zone_map', 'franchise_shipping_zone_map.shipping_zone', '=', 'city.shipping_zone')
                            ->where ('city.id', $city)
                            ->where ('franchise_shipping_zone_map.franchise',config('myapp.franchise'))
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


    public function payhereNotify(Request $request){

        $merchant_id          = $_POST['merchant_id'];
        $order_id             = $_POST['order_id'];
        $payhere_amount       = $_POST['payhere_amount'];
        $payhere_currency   = $_POST['payhere_currency'];
        $status_code         = $_POST['status_code'];
        $md5sig                = $_POST['md5sig'];
        $merchant_secret = config('myapp.payhere_secret'); // Replace with your Merchant Secret (Can be found on your PayHere account's Settings page)
        $local_md5sig = strtoupper (md5 ( $merchant_id . $order_id . $payhere_amount . $payhere_currency . $status_code . strtoupper(md5($merchant_secret)) ) );
        if ($status_code == 2 ){
            DB::table('order')
                   ->where('search_code', $order_id)
                   ->update(['order_status' => 'confirm', 'payment_type'=>'AP']);
        }
               
    }

    public function ordersuccessdetails($orderId){
        // $query = DB::table('order')
        //             ->where('order.franchise', config('myapp.franchise'))
        //             ->where('order.search_code', $orderId)
        //             ->select('order.id','order.created_date','order.order_status','order.franchise_shipping_cost','order.search_code','order.address','order.phone_one', 'order.resipient','order.grand_total' , 'order.payment_type' ,'order.item_description' )
        //             ->first();                

        $query = Order::where('franchise', config('myapp.franchise'))   
                      ->where('order.search_code', $orderId)
                      ->first();

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

            $product_images = $this->get_product_image_by_id($item->id);

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

         $Order =array(
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
        return response()->json($Order);
    
    }

    public function ordertrackingdetails($searchcode,$phone){

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

                $product_images = $this->get_product_image_by_id($item->id);

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
            return response()->json($Order);
        }else{
            return null;
        }
    }

    private function getImage($id){

        $image = Images:: find($id);   
        
        return $image;
    }

    private function get_product_image_by_id($id)
    {
        $product_images = DB::table('product_images')
                        ->join('image', 'image.id', '=', 'product_images.image')
                        ->select(DB::raw('CONCAT("'.config('myapp.base_url').'", image.image_name) AS image_name'))
                        ->where('product', $id)->limit(1)
                        ->pluck('image.image_name');
       
        return ($product_images);
    }   
    

    public function get_address(Request $request){

        $sub =$this->get_authorization($request);

        $query = DB::table('customer')
                    ->leftJoin('customer_address', 'customer_address.customer', '=', 'customer.id')
                    ->leftJoin('customer_phone_number', 'customer_phone_number.customer', '=', 'customer.id')
                    ->leftJoin('city', 'city.id', '=', 'customer_address.city')
                    ->where('customer.id', $sub)
                    ->where('customer_address.is_default',1)
                    ->select('customer.customer_name','customer.email', 'customer_phone_number.phone_number','customer_address.address' ,'city.id','city.city','city.zip_code')
                    ->first();
        if($query){
            $Address = array(
                'default'  => true,
                'firstName'=> $query->customer_name,
                'lastName' => $query->customer_name,
                'email'    => $query->email,
                'phone'    => $query->phone_number,
                'country'  => "Sri Lanka",
                'city'     => $query->city,
                'cityId'   => $query->id,
                'postcode' => $query->zip_code,
                'address'  => $query->address,
            );
            
            return response()->json($Address);
        }else{
            return null;
        }            


    } 

    public function get_PageAddresses(Request $request)
    {

        $sub =$this->get_authorization($request);

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

    public function get_checkoutphonenumbers(Request $request){

        $sub =$this->get_authorization($request);

        $query = DB::table('customer_phone_number')
                    ->where('customer_phone_number.customer', $sub)
                    ->select('customer_phone_number.id','customer_phone_number.phone_number','customer_phone_number.is_default' )
                    ->orderBy('customer_phone_number.is_default', 'desc')
                    ->get();  
                    $Phone=array();
                    foreach ($query as $key => $phoneNumber) {
                        
                        $default=($phoneNumber->is_default==1) ? true : false;
            
                        $Phone[$key] = array(
                            'id'       => $phoneNumber->id,
                            'default'  => $default,
                            'phone'     => $phoneNumber->phone_number,
                        );
            
                    }                    

        return response()->json($Phone);          
    }

    public function get_checkoutphonenumber(Request $request){

        $sub =$this->get_authorization($request);

        $query = DB::table('customer_phone_number')
                    ->where('customer_phone_number.customer', $sub)
                    ->where('customer_phone_number.is_default', 1)
                    ->select('customer_phone_number.id','customer_phone_number.phone_number','customer_phone_number.is_default' )
                    ->first();  
        if($query){
            $Phone= array(
                'id'       => $query->id,
                'default'  => true,
                'phone'     => $query->phone_number,
            );  
            return response()->json($Phone);    
        }else{
            return null;
        }
      
    }   

  public function submitChangeDefaultAddress(Request $request){
            $addressId = $request->id;

            $get_customer = DB::table('customer_address')
                        ->where('customer_address.id',$addressId)
                        ->select('customer_address.customer')
                        ->first(); 

            $set_0 = DB::table('customer_address')
                        ->where('customer_address.customer',$get_customer->customer)
                        ->update(['is_default' => 0]);

            $update_is_default = DB::table('customer_address')
                        ->where('customer_address.id',$addressId)
                        ->update(['is_default' => 1]);   

    return response()->json(['message' => 'User successfully registered'], 201);                                         
                        
    // return response()->json('doen');

  }

  public function submitChangeDefaultPhoneNumber(Request $request){
    $phoneNumberId = $request->id;

    $get_customer = DB::table('customer_phone_number')
                ->where('customer_phone_number.id',$phoneNumberId)
                ->select('customer_phone_number.customer')
                ->first(); 

    $set_0 = DB::table('customer_phone_number')
                ->where('customer_phone_number.customer',$get_customer->customer)
                ->update(['is_default' => 0]);

    $update_is_default = DB::table('customer_phone_number')
                ->where('customer_phone_number.id',$phoneNumberId)
                ->update(['is_default' => 1]);   

  return response()->json(['message' => 'User successfully registered'], 201);    

}                                     
                
    public function addnewaddress(Request $request){
        
        $sub =$this->get_authorization($request);

        $payLoad = json_decode(request()->getContent(), true);

        $query = customerAddress::where('customer',$sub)->first();
        
        if($query){
         
            $newAddress = customerAddress::create(
                ['customer'=> $sub,
                'address' => $payLoad['address'],
                'city'    => $payLoad['cityId']]
            ); 

       }else{

            $newAddress = customerAddress::create(
            ['customer'  => $sub,
                'address'   => $payLoad['address'],
                'city'      => $payLoad['cityId'],
                'is_default'=> 1]
            );    
       } 

        $customer = Customer::find($sub);
        $city     = City::find($newAddress->city);
        $default  = ($newAddress->is_default) ? 1 : 0;
        
        
        $address= array( 
            'id'       => $newAddress->id,
            'default'  => $default,
            'firstName'=> $customer->customer_name,
            'lastName' => $customer->customer_name,
            'email'    => $customer->email,
            'phone'    => '071',
            'country'  => 'Sri Lanka',
            'city'     => $city->city,
            'cityId'   => $city->id,
            'postcode' => $city->zip_code,
            'address'  => $newAddress->address,
        );  

        return response()->json(['message' => 'Address successfully created','address' => $address], 201);   
    }

    public function addnewphone(Request $request){
        
        $sub =$this->get_authorization($request);

        $payLoad = json_decode(request()->getContent(), true);

        $query = customerPhoneNumber::where('customer',$sub)
                                    ->first();

        if($query){   
            $newPhone = customerPhoneNumber::create(
                ['customer'     => $sub,
                 'phone_number' => $payLoad['phone_number']]);

        }else{
            $newPhone = customerPhoneNumber::create(
                ['customer'     => $sub,
                 'phone_number' => $payLoad['phone_number'],
                 'is_default'   => 1]);
       
        }  
        
        $default  = ($newPhone->is_default) ? 1 : 0;

        $phone = array(
            'id'      => $newPhone->id,
            'default' => $default,
            'phone'   => $newPhone->phone_number
        );
        
        return response()->json(['message' => 'Phone number successfully created','phone' => $phone], 201);
        
    }
    
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

    
    private function get_product_query()
    {
        $query = DB::table('product')
                            ->join  ('franchise_product_map', 'franchise_product_map.product', '=', 'product.id')
                            ->where('franchise_product_map.franchise', config('myapp.franchise'))
                            ->select('product.id','product.title','product.short_title', 'product.slug', 'product.brand'
                                    ,'product.sku','franchise_product_map.price','product.stock','product.cost'
                                    ,'product.short_description','product.long_description'
                                    ,'product.sinhala_long_description','product.old_price','franchise_product_map.price','franchise_product_map.commision','product.weight');
        
         return $query;                           
    } 

    
    private function get_authorization($request)
    {
        $token = $request->header('authorization');
        $payload = JWTAuth::setToken($token)->getPayload();
        $sub =$payload->get('sub');
        
        return $sub;
    }  
    
    
}
