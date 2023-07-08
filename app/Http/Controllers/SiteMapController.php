<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Response;
use Carbon\Carbon;

use App\Models\Product;
use App\Models\productImages;
use App\Models\productCategory;

class SiteMapController extends Controller
{
    public function site_map(){

        $product_result = Product::with('Franchise')
                                 ->orWhereHas('Franchise', function($query){
                           $query->where('franchise',config('myapp.franchise'));  
        })->get();

        $site_map_product =array();
               
        foreach ($product_result as $key => $data) {

            $images = productImages::with('images_collection')->where('product',$data->id)->get();
            $site_map_img =array();

            foreach ($images as $key_1 => $img_data) {
                $site_map_img[$key_1]=array(
                    'image'      => $img_data['images_collection']->image_name,
                    'title'      => $data->title,
                );
    
            }

            $site_map_product[$key]=array(
                'loc'       => $data->slug,
                'lastmod'   => Carbon::today()->format('Y-m-d'),
                'changefreq'=> 'daily',
                'priority'  => '0.5',
                'images'  => $site_map_img,
            );

        }




        $category_result = productCategory::all();

        $site_map_category =array();

        foreach ($category_result as $key => $data) {


            $site_map_category[$key]=array(
                'loc'       => $data->slug,
                'lastmod'   => Carbon::today()->format('Y-m-d'),
                'changefreq'=> 'daily',
                'priority'  => '0.9',
            );

        }


        $site_map =[
            'site_map_product'  =>$site_map_product,
            'site_map_category' =>$site_map_category,

        ];

        $contents = View::make('sitemap')->with('data', $site_map);
        $response = Response::make($contents);
        $response->header('Content-Type', 'application/xml');
        return $response;
    }
}
