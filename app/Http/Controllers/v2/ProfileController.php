<?php

namespace App\Http\Controllers\v2;
use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Carbon\Carbon;
use Validator;


use App\Models\Profile;
use App\Models\Order;
use App\Models\orderItems;
use App\Models\Customer;
use App\Models\auruduCompetition;
use App\Models\customerPhoneNumber;
use App\Models\customerAddress;
use App\Models\City;
use App\Models\PasswordReset;

class ProfileController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Public function / get customer
    |--------------------------------------------------------------------------
    */  
    public function getCustomer(Request $request){
        try {
            $sub =$this->getAuthorization($request);

            $query = Customer::where('id', $sub['sub'])
                             ->where('franchise', $sub['franchise'])
                             ->select('customer.customer_name','customer.email','provider','provider_pic')                          
                             ->first();

            if($query){
                return response(['success' => true,'data'=> $query,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }
          
          } catch (\Exception $e) { 
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500); 
          }
    }










    /*
    |--------------------------------------------------------------------------
    | Public function / get Customer address
    |--------------------------------------------------------------------------
    */ 
    public function getCustomerAddress(Request $request)
    {

        try {
            $sub =$this->getAuthorization($request);

            $query = DB::table('customer')
                        ->Join('customer_address', 'customer_address.customer', '=', 'customer.id')
                        ->leftJoin('city', 'city.id', '=', 'customer_address.city')
                        ->where('customer.id', $sub['sub'])
                        ->select('customer_address.id','customer.customer_name','customer.email','customer_address.address' ,
                                 'city.city','city.zip_code','customer_address.is_default', 'city.id as city_id' )
                        ->orderBy('customer_address.is_default', 'desc')
                        ->get();
    
            $Address = array();

            foreach ($query as $key => $address) {
                
                $default=($address->is_default==1) ? true : false;
    
                $Address[$key] = array(
                    'address_id'=> $address->id,
                    'default'  => $default,
                    'firstName'=> $address->customer_name,
                    'lastName' => $address->customer_name,
                    'email'    => $address->email,
                    'country'  => "Sri Lanka",
                    'city'     => $address->city,
                    'city_id'  => $address->city_id,
                    'postcode' => $address->zip_code,
                    'address'  => $address->address,
                );
    
            }

            if($query){
                return response(['success' => true,'data'=> $Address,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }  

        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / get Customer Phone Number 
    |--------------------------------------------------------------------------
    */ 
    public function getCustomerPhoneNumber(Request $request){

        try {
            $sub =$this->getAuthorization($request);

            $query = DB::table('customer_phone_number')
                        ->where('customer_phone_number.customer', $sub['sub'])
                        ->select('customer_phone_number.id','customer_phone_number.phone_number','customer_phone_number.is_default' )
                        ->orderBy('customer_phone_number.is_default', 'desc')
                        ->get(); 

            $phone=array();
            foreach ($query as $key => $phoneNumber) {
                
                $default=($phoneNumber->is_default==1) ? true : false;
    
                $phone[$key] = array(
                    'phone_id' => $phoneNumber->id,
                    'default'  => $default,
                    'phone'    => $phoneNumber->phone_number,
                );
    
            }   
            
            if($query){
                return response(['success' => true,'data'=> $phone,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }  
                    
        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }       
    }










    /*
    |--------------------------------------------------------------------------
    | Public function / get recent orders
    |--------------------------------------------------------------------------
    */ 
    public function getRecentOrders(Request $request)
    {
        try {
            $sub =$this->getAuthorization($request);

            $query = Order::where('franchise', $sub['franchise'])
                          ->where('customer', $sub['sub'])
                          ->select('order.search_code','order.created_date', 'order.order_status','order.grand_total')
                          ->limit(3)
                          ->get();
    
            $Order =array();

            foreach ($query as $key => $order) {                       
    
                $Order[$key] = array(
                    'id'      => $order->search_code,
                    'date'    => $order->created_date,
                    'status'  => $order->order_status,
                    'total'   => $order->grand_total,
                    'quantity'=> 5, 
                );
    
            }   

            if($query){
                return response(['success' => true,'data'=> $Order,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }
          
          } catch (\Exception $e) { 
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
          }
    }
    









    /*
    |--------------------------------------------------------------------------
    | Public function / Create Customer Phone Number 
    |--------------------------------------------------------------------------
    */ 
    public function createCustomerPhoneNumber(Request $request){
        
        try {
        
            $sub = $this->getAuthorization($request);

            $validation_array = [
                'phone_number'           => 'required|numeric',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }

            DB::beginTransaction();



            $query = customerPhoneNumber::Join('customer', 'customer_phone_number.customer', '=', 'customer.id')
                                          ->where('customer.franchise',$sub['franchise']);


            if($query->where('customer_phone_number.phone_number',$request->phone_number)->first()){
                return response(['success' => false,'data'=> null,'message' => 'Phone number already taken.'], 200);  
            }
                                         
       
            if($query->where('customer.id',$sub['sub'])->first()){   
                $newPhone = customerPhoneNumber::create(
                    ['customer'     => $sub['sub'],
                     'phone_number' => $request->phone_number]);

            }else{
                $newPhone = customerPhoneNumber::create(
                    ['customer'     => $sub['sub'],
                     'phone_number' => $request->phone_number,
                     'is_default'   => 1]);      
            }  
            
            $default  = ($newPhone->is_default) ? 1 : 0;

            $phone = array(
                'id'      => $newPhone->id,
                'default' => $default,
                'phone'   => $newPhone->phone_number
            );   

            DB::commit();

            if($newPhone){
                return response()->json(['success' => true,'data'=> $phone,'message' => 'Everything is good!. Phone number successfully created'], 201);  
            }
                    
        } catch (\Exception $e) {
            DB::rollback();
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);  
        }
       
    }










    /*
    |--------------------------------------------------------------------------
    | Public function / Create Customer Address 
    |--------------------------------------------------------------------------
    */ 
    public function createCustomerAddress(Request $request){

        try {
  
            $sub =$this->getAuthorization($request);

            $validation_array = [
                'address'          => 'required',
                'cityId'           => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }
    
            $query = customerAddress::where('customer',$sub['sub'])->first();
            
            if($query){
             
                $newAddress = customerAddress::create(
                    ['customer'=> $sub['sub'],
                    'address'  => $request->address,
                    'city'     => $request->cityId]
                ); 
    
           }else{
    
                $newAddress = customerAddress::create(
                ['customer'  => $sub['sub'],
                    'address'   =>  $request->address,
                    'city'      => $request->cityId,
                    'is_default'=> 1]
                );    
           } 
    
            $customer = Customer::find($sub['sub']);
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

            DB::commit();

            if($newAddress){
                return response(['success' => true,'data'=> $address,'message' => 'Everything is good!. Address successfully created'], 201);  
            }
                    
        } catch (\Exception $e) {
            DB::rollback();
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }      
          
    }


    







    /*
    |--------------------------------------------------------------------------
    | Private function / remove Address
    |--------------------------------------------------------------------------
    */ 
    public function removeAddress(Request $request){

        try {

            $sub =$this->getAuthorization($request);

            if(!$request->has('address')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. Address is required.",], 500);   
            }

            $address = $request ->address; 
    
            $query = DB::table('customer_address')
                        ->where('customer_address.customer', $sub['sub'])
                        ->where('customer_address.address',$address)
                        ->delete();

            if($query){
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Address was successfully deleted."], 200);
            }  

        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
     
    }









    /*
    |--------------------------------------------------------------------------
    | Private function / remove Phone Number
    |--------------------------------------------------------------------------
    */   
    public function removePhone(Request $request){

        try {

            $sub =$this->getAuthorization($request);

            if(!$request->has('phone_number')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. Phone number is required.",], 500);   
            }

            $phone = $request ->phone_number; 
    
            $query = DB::table('customer_phone_number')
                        ->where('customer_phone_number.customer', $sub['sub'])
                        ->where('customer_phone_number.phone_number', $phone)
                        ->delete();

            if($query){
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Phone number was successfully deleted."], 200);
            }  

        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }










    /*
    |--------------------------------------------------------------------------
    | Public function / get orders details
    |--------------------------------------------------------------------------
    */ 
    public function getOrderDetails(Request $request)
    {
        try {
     
            $sub =$this->getAuthorization($request);

            if(!$request->has('order_id')){
                return response(['success' => false,'data'=> null,'message' => "Opps!. Order ID is required.",], 500);   
            }

            $order_id = $request ->order_id; 

            $query =Order::where('order.franchise',$sub['franchise']) 
                        ->where('order.customer', $sub['sub'])
                        ->where('order.search_code', $order_id)
                        ->first();
 

            $order_items= orderItems::with('Product')->where('order',$query->id)->get();                        


            $options= array(
                'label'=> "label",
                'value'=> "value"
            );

            $sub_total=0;
            $items =array();
            foreach ($order_items as $key => $order) {
                $total =$order->Product->price * $order->quantity;
                $sub_total+=$total;
                $items[$key]=array(
                    'id'=> $order->id,
                    'slug'=> $order->Product->slug,
                    'name'=> $order->Product->title,
                    'image'=> "http://webstoresl.s3.ap-southeast-1.amazonaws.com/webstore/product-images/no-product-image.png",
                    'options'=>$options,
                    'price'=> $order->Product->price,
                    'quantity'=>$order->quantity,
                    'total'=> $total,
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

            $Order =array(
                'id'             => $query->search_code,
                'date'           => date('Y-m-d', strtotime($query->created_date)),
                'status'         => $query->order_status,
                'items'          => $items,
                'additionalLines'=> array($additionalLines),
                'quantity'       => 10,
                'subtotal'       => $sub_total,
                'total'          => $query->grand_total,
                'paymentMethod'  => $query->payment_type,
                'shippingAddress'=> $Address,
                'billingAddress' => $Address,
            );

            if($query){
                return response(['success' => true,'data'=> $Order,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }  

        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }










    /*
    |--------------------------------------------------------------------------
    | Public function / get Customer default address V2
    |--------------------------------------------------------------------------
    */ 
    public function getCustomerDefaultAddress(Request $request){

        try {

            $sub =$this->getAuthorization($request);

            $query = DB::table('customer')
                        ->leftJoin('customer_address', 'customer_address.customer', '=', 'customer.id')
                        ->leftJoin('customer_phone_number', 'customer_phone_number.customer', '=', 'customer.id')
                        ->leftJoin('city', 'city.id', '=', 'customer_address.city')
                        ->where('customer.id', $sub['sub'])
                        ->where('customer_address.is_default',1)
                        ->select('customer.customer_name','customer.email', 'customer_phone_number.phone_number','customer_address.address', 'customer_address.id as addressId','city.id','city.city','city.zip_code')
                        ->first();

            if(empty($query)){
                return response(['success' => true,'data'=>  null ,'message' => 'Data not available'], 200);  
            }

            $Address = array(
                'default'   => true,
                'firstName' => $query->customer_name,
                'lastName'  => $query->customer_name,
                'email'     => $query->email,
                'phone'     => $query->phone_number,
                'country'   => "Sri Lanka",
                'city'      => $query->city,
                'city_id'   => $query->id,
                'address_id'=> $query->addressId,
                'postcode'  => $query->zip_code,
                'address'   => $query->address,
            );

            if($query){
                return response(['success' => true,'data'=> $Address,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }  
                    
        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500); 
        }          

    } 




    /*
    |--------------------------------------------------------------------------
    | Public function / get Customer Default Phone Number V2
    |--------------------------------------------------------------------------
    */ 
    public function getCustomerDefaultPhoneNumber(Request $request){

        try {

            $sub =$this->getAuthorization($request);

            $query = DB::table('customer_phone_number')
                        ->where('customer_phone_number.customer', $sub['sub'])
                        ->where('customer_phone_number.is_default', 1)
                        ->select('customer_phone_number.id','customer_phone_number.phone_number','customer_phone_number.is_default' )
                        ->first();  
            if(empty($query)){
                return response(['success' => true,'data'=>  null ,'message' => 'Data not available'], 200);  
            }

            $phone= array(
                'phone_id' => $query->id,
                'default'  => true,
                'phone'    => $query->phone_number,
            );  

            if($query){
                return response(['success' => true,'data'=> $phone,'message' => "Everything is good!. Data found successfully"], 200);
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }  
                    
        } catch (\Exception $e) {
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);  
        }
  
    } 










    /*
    |--------------------------------------------------------------------------
    | Public function / Change Default Address
    |--------------------------------------------------------------------------
    */ 
    public function changeDefaultAddress(Request $request){

        try {
                if(!$request->has('address_id')){
                    return response(['success' => false,'data'=> null,'message' => "Opps!. Address id is required.",], 500);   
                }
    
                $id  = $request ->address_id; 
    
                DB::beginTransaction();
                $customer_address = customerAddress::find($id);
    
                $set_0 = customerAddress::where('customer_address.customer',$customer_address->customer)
                                        ->update(['is_default' => 0]);
    
                $update_is_default = customerAddress::where('customer_address.id',$id)
                                                     ->update(['is_default' => 1]);   
    
                DB::commit();

                $Address = customerAddress::find($id);
    
                if($update_is_default){
                    return response(['success' => true,'data'=> $Address,'message' => "Everything is good!. Address priority is changed"], 200);
                }  
       
            } catch (\Exception $e) {
                DB::rollback();
                return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
            }
                                   
    }
    









    /*
    |--------------------------------------------------------------------------
    | Public function / Change Default Phone Number
    |--------------------------------------------------------------------------
    */ 
    public function changeDefaultPhoneNumber(Request $request){
        try {
    
                if(!$request->has('phone_id')){
                    return response(['success' => false,'data'=> null,'message' => "Opps!. Phone Number ID is required.",], 500);   
                }
                
                $phoneNumberId = $request->phone_id;
    
    
                DB::beginTransaction();
                $get_customer = customerPhoneNumber::find($phoneNumberId);
    
                $set_0 = customerPhoneNumber::where('customer_phone_number.customer',$get_customer->customer)
                                            ->update(['is_default' => 0]);
            
                $update_is_default = customerPhoneNumber::where('customer_phone_number.id',$phoneNumberId)
                                                       ->update(['is_default' => 1]);   
    
                DB::commit();
            
    
                if($update_is_default){
                    return response(['success' => true,'data'=> null,'message' => "Everything is good!. Phone number priority is chenged"], 200);
                }  
       
            } catch (\Exception $e) {
                DB::rollback();
                return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
            }  
    
    }   







    
    /*
    |--------------------------------------------------------------------------
    | Public function / get address
    |--------------------------------------------------------------------------
    */  
    // public function getAddress(Request $request){

    //     try {
    //         $sub =$this->getAuthorization($request);

    //         $query = Customer::where('customer.id', $sub['sub'])
    //                         ->leftjoin('customer_address', 'customer_address.customer', '=', 'customer.id')
    //                         ->leftjoin('customer_phone_number', 'customer_phone_number.customer', '=', 'customer.id')
    //                         ->leftjoin('city', 'city.id', '=', 'customer_address.city')
    //                         ->select('customer.customer_name','customer.email', 'customer_phone_number.phone_number','customer_address.address' ,'city.city','city.zip_code')
    //                         ->first();

    //         if($query){
    //             $Address = array(
    //                 'default'  => true,
    //                 'firstName'=> $query->customer_name,
    //                 'lastName' => $query->customer_name,
    //                 'email'    => $query->email,
    //                 'phone'    => $query->phone_number,
    //                 'country'  => "Sri Lanka",
    //                 'city'     => $query->city,
    //                 'postcode' => $query->zip_code,
    //                 'address'  => $query->address,
    //             );

    //         }

    //         if($query){
    //             return response(['success' => true,'data'=> $Address,'message' => "Everything is good!. Data found successfully"], 200);
    //         }else{
    //             return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
    //         }

    //     } catch (\Exception $e) { 
    //         return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
    //     }
        
    // } 
    
    
    

    /*
    |--------------------------------------------------------------------------
    | Public function / get orders History
    |--------------------------------------------------------------------------
    */ 
    // public function getOrderHistory(Request $request)
    // {
    //     try {
        
    //         $sub =$this->getAuthorization($request);

    //         $query = DB::table('order')
    //                     ->where('order.franchise', config('myapp.franchise'))
    //                     ->where('order.customer', $sub)
    //                     ->select('order.search_code','order.created_date', 'order.order_status','order.grand_total');
                        
    //         $order_list = $query->paginate(5);  
    
    //         $Order =array();

    //         foreach ($order_list as $key => $order) {                       
    
    //             $Order[$key] = array(
    //                 'id'=> $order->search_code,
    //                 'date'=> $order->created_date,
    //                 'status'=> $order->order_status,
    //                 'total'=> $order->grand_total,
    //                 'quantity'=> 5, 
    //             );
    
    //         }                   

    //         if($query){
    //             return response(['success' => true,'data'=> $Order,'message' => "Everything is good!. Data found successfully"], 200);
    //         }else{
    //             return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
    //         }  

    //     } catch (\Exception $e) {
    //         return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
    //     }

    // }


    /*
    |--------------------------------------------------------------------------
    | Public function / edit Customer Address
    |--------------------------------------------------------------------------
    */  
    public function editCustomerAddress(Request $request){

        try {
  
            $sub =$this->getAuthorization($request);

            $validation_array = [
                'address'          => 'required',
                'city'           => 'required',
                'address_id'       => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }

            DB::beginTransaction();
    
            $query = customerAddress::find($request->address_id);
            
            if($query){
             
                $query->address = $request->address;
                $query->city  = $request->city;
                $query->save();

                DB::commit();

                return response(['success' => true,'data'=> $query,'message' => 'Everything is good!. Address successfully updated'], 201);  
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
    | Public function / edit Customer Address
    |--------------------------------------------------------------------------
    */  
    public function editCustomerPhoneNumber(Request $request){

        try {
  
            $sub =$this->getAuthorization($request);

            $validation_array = [
                'phone_number'         => 'required|numeric',
                'phone_id'             => 'required',
            ];
    
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }

            DB::beginTransaction();
    
            $query = customerPhoneNumber::find($request->phone_id);
            
            if($query){
             
                $query->phone_number = $request->phone_number;
                $query->save();

                DB::commit();

                return response(['success' => true,'data'=> $query,'message' => 'Everything is good!. Phone Number successfully updated'], 201);  
            }else{
                return response(['success' => true,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }

                    
        } catch (\Exception $e) {
            DB::rollback();
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }      
          
    }


    public function submitForgetPasswordForm(Request $request){

        try {

            $validation_array = [
                'email'         => 'required|string|email|max:100',
                'franchise'     => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
            }

            $customer = Customer::where('franchise', $request->franchise)->where('email'    , $request->email)->first();

            if(!$customer){
                return response(['success' => true,'data'=> null,'message' => "Opps!. Your email not found.",], 200);   
            }                   


            $concatenate = Str::random(64).'?'.$request->franchise.'?'.$request->email.'?'. Carbon::now()->addHour(1)->format('Y-m-d H:i:s');       
            $token = Crypt::encryptString($concatenate);

            PasswordReset::where('franchise',  $request->franchise)->where('email'  , $request->email)->delete();

            $query =  PasswordReset::create([
                'email'      => $request->email, 
                'token'      => $token, 
                'franchise'  => $request->franchise, 
            ]);
    

            if($query){
                return response(['success' => true,'data'=> $query,'message' => "Everything is good!. The password reset token has been sent to your email."], 200);
            }

          
        } catch (\Exception $e) {     
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }
    
    
    public function getToken(Request $request){

        try {

            $validation_array = [
                'token'         => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
            }
            
            $decrypted = Crypt::decryptString($request->token);
            $explode   = explode("?",$decrypted);
    

            if(Carbon::now()->addHour(0)->format('Y-m-d H:i:s') < $explode[3] ==false){
                PasswordReset::where('franchise', $explode[1])->where('email'  , $explode[2])->delete();
                return response(['success' => false,'data'=> null,'message' => "Everything is good!. Password reset link is expired"], 200);
            }
      

            $query  = PasswordReset::where('franchise', $explode[1])
                                     ->where('email'  , $explode[2])
                                     ->where('token'  , $request->token)
                                     ->first();
                                     
                               

            if($query){
                return response(['success' => true,'data'=> $query->token,'message' => "Everything is good!. Email found successfully"], 200);  
            }else{
                return response(['success' => false,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }                   

          
        } catch (\Exception $e) {     
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }


    public function passwordResetForm(Request $request){

        try {

            $validation_array = [
                'token'         => 'required',
                'password'      => 'required|string|confirmed|min:4',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
            }
            
            $decrypted = Crypt::decryptString($request->token);
            $explode   = explode("?",$decrypted);
            
      

            $query  = PasswordReset::where('franchise', $explode[1])->where('email'  , $explode[2])->where('token'  , $request->token);
            $passwordreset  = $query->first();  

            if($passwordreset){

                $customer  = Customer::where('franchise', $explode[1])->where('email'  , $explode[2])->update(['password' =>  bcrypt($request->password)]);
                $query->delete(); 
                if($customer){
                    return response(['success' => true,'data'=> null,'message' => "Everything is good!. Password reset successfully."], 200); 
                }
 
                 
            }else{
                return response(['success' => false,'data'=> null,'message' => "Everything is good!. Data not found"], 200);
            }                   

          
        } catch (\Exception $e) {     
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }



    public function changeAccountDetails(Request $request){

        try {

            $sub =$this->getAuthorization($request);


            $validation_array = [
                'customer_name'         => 'required',
            ];

            if($request->has('password-required')){
                $validation_array['old_password']           = 'required';
                $validation_array['password']               = 'required|confirmed';
                $validation_array['password_confirmation']  = 'required';
            }  
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
            } 
            
            $query    = Customer::where('id', $sub['sub']);
            $customer = $query->first();

            if($request->has('password-required')){
                if(Hash::check($validator->valid()['old_password'],$customer->password)){
                     $query->update(['customer_name' => $validator->valid()['customer_name'], 'password'=>bcrypt($validator->valid()['password'])]);
                }else{
                     return response(['success' => false,'data'=> null,'message' => "Everything is good!. Old Password wrong"], 200);
                }      
            }else{
                $query->update(['customer_name' => $validator->valid()['customer_name']]);
            }



            if($query){
                return response(['success' => true,'data'=> $query,'message' => "Everything is good!. Account details updated successfully"], 200);
            }

          
        } catch (\Exception $e) {     
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }





    /*
    |--------------------------------------------------------------------------
    | Private function / get Authorization V2
    |--------------------------------------------------------------------------
    */   
    private function getAuthorization($request)
    {
        $token   = $request->header('authorization');
        $payload = JWTAuth::setToken($token)->getPayload();
        $sub     = $payload->get();
        
        return $sub;
    }
}
