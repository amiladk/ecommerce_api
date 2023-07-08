<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\Order;
use App\Models\orderItems;
use App\Models\Customer;
use App\Models\auruduCompetition;

class ProfileController extends Controller
{
    public function get_customer(Request $request){

        $sub =$this->get_authorization($request);

        $query = DB::table('customer')
                    ->where('customer.id', $sub)
                    ->select('customer.customer_name','customer.email','provider','provider_pic')                          
                    ->first();
        
        return response()->json($query);
    }
    
    





    public function get_address(Request $request){

        $sub =$this->get_authorization($request);

        $query = DB::table('customer')
                    ->leftjoin('customer_address', 'customer_address.customer', '=', 'customer.id')
                    ->leftjoin('customer_phone_number', 'customer_phone_number.customer', '=', 'customer.id')
                    ->leftjoin('city', 'city.id', '=', 'customer_address.city')
                    ->where('customer.id', $sub)
                    ->select('customer.customer_name','customer.email', 'customer_phone_number.phone_number','customer_address.address' ,'city.city','city.zip_code')
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
                'postcode' => $query->zip_code,
                'address'  => $query->address,
            );

            return response()->json($Address);
        }

        return response()->json(array());
        
        
    } 
    
    
    public function get_RecentOrders(Request $request)
    {
        $sub =$this->get_authorization($request);

        $query = DB::table('order')
                    ->where('order.franchise', config('myapp.franchise'))
                    ->where('order.customer', $sub)
                    ->select('order.search_code','order.created_date', 'order.order_status','order.grand_total')
                    ->limit(3)
                    ->get();

        $Order =array();
                    foreach ($query as $key => $order) {                       
            
                        $Order[$key] = array(
                            'id'=> $order->search_code,
                            'date'=> $order->created_date,
                            'status'=> $order->order_status,
                            'total'=> $order->grand_total,
                            'quantity'=> 5, 
                        );
            
                    }                   

             return response()->json($Order);
    }

    public function get_OrderHistory(Request $request)
    {
        $sub =$this->get_authorization($request);

        $query = DB::table('order')
                    ->where('order.franchise', config('myapp.franchise'))
                    ->where('order.customer', $sub)
                    ->select('order.search_code','order.created_date', 'order.order_status','order.grand_total');
                    
        $order_list = $query->paginate(5);  

        $Order =array();
                    foreach ($order_list as $key => $order) {                       
            
                        $Order[$key] = array(
                            'id'=> $order->search_code,
                            'date'=> $order->created_date,
                            'status'=> $order->order_status,
                            'total'=> $order->grand_total,
                            'quantity'=> 5, 
                        );
            
                    }                   

             return response()->json($Order);
    }

    public function get_OrderDetails($order_id,Request $request)
    {
        $sub =$this->get_authorization($request);

        $query =Order::where('order.franchise', config('myapp.franchise')) 
                       ->where('order.customer', $sub)
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
        return response()->json($Order);
    }


    public function get_PageAddresses(Request $request)
    {

        $sub =$this->get_authorization($request);

        $query = DB::table('customer')
                    ->leftJoin('customer_address', 'customer_address.customer', '=', 'customer.id')
                    ->leftJoin('customer_phone_number', 'customer_phone_number.customer', '=', 'customer.id')
                    ->leftJoin('city', 'city.id', '=', 'customer_address.city')
                    ->where('customer.id', $sub)
                    ->select('customer_address.id','customer.customer_name','customer.email', 'customer_phone_number.phone_number','customer_address.address' ,
                             'city.city','city.zip_code','customer_address.is_default' )
                    ->get();

                    foreach ($query as $key => $address) {
                        
                        $default=($address->is_default==1) ? true : false;
            
                        $Address[$key] = array(
                            'id'       => $address->id,
                            'default'  => $default,
                            'firstName'=> $address->customer_name,
                            'lastName' => $address->customer_name,
                            'email'    => $address->email,
                            'phone'    => $address->phone_number,
                            'country'  => "Sri Lanka",
                            'city'     => $address->city,
                            'postcode' => $address->zip_code,
                            'address'  => $address->address,
                        );
            
                    }

        return response()->json($Address);
    }

public function removeAddress(Request $request){

    $sub =$this->get_authorization($request);
    $payLoad = json_decode(request()->getContent(), true);

    $query = DB::table('customer_address')
                ->where('customer_address.id', $payLoad)
                ->delete();

    return response()->json(['message' => 'Address deleted'], 201); 

}
public function removePhone(Request $request){

    $sub =$this->get_authorization($request);
    $payLoad = json_decode(request()->getContent(), true);

    $query = DB::table('customer_phone_number')
                ->where('customer_phone_number.id', $payLoad)
                ->delete();

    return response()->json(['message' => 'Phone deleted'], 201); 

}

    public function male_data(Request $request){

        if($request->limit=1){$gender='male';}else{$gender='female';};
        
        $data = DB::table('aurudu_competition')
                        ->where('aurudu_competition.gender', $gender)
                        ->select('aurudu_competition.id','aurudu_competition.name','aurudu_competition.votes','aurudu_competition.membership_no','aurudu_competition.image_link')                        
                        ->get();
        if($data){
            $product_array =array();               

            foreach($data as $key=> $item){

            $product_array[$key] = array(
                'id'              => $item->id,
                'slug'            => '',
                'name'            => $item->name,
                'sku'             => '',
                'price'           => $item->votes,
                'compareAtPrice'  => $item->membership_no,
                'images'          => array($item->image_link),
                'badges'          => array(),
                'rating'          => 0,
                'reviews'         => 0,
                'availability'    => '',
                'brand'           => null,
                'categories'      => array(),
                'attributes'      => array(),
                'customFields'    => '',
                'meta_title'      => '',
                'meta_description'=> ''
            );
            }
            return $product_array;
        }
        return false;
    }

    public function vote_me(Request $request){ 
        $user      = $this->get_authorization($request);
        $voting_id = json_decode(request()->getContent(), true);

        $user_data = Customer::find($user);

        if($user_data->loyalty_points <4){

            auruduCompetition::find($voting_id)->increment('votes');
            Customer::find($user)->increment('loyalty_points');

            $votes = auruduCompetition::find($voting_id);

            return response()->json(['message' => true,'votes' => $votes->votes], 201);
    

        }else{
            return response()->json(['message' => false], 201);
        }

        return false;
    }

    
    private function get_authorization($request)
    {
        $token = $request->header('authorization');
        $payload = JWTAuth::setToken($token)->getPayload();
        $sub =$payload->get('sub');
        
        return $sub;
    }
}
