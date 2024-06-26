<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Listing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ListingGallery;
use App\Models\Deals;
use App\Models\Offers;
use App\Models\User;
use App\Models\Invoices;
use App\Notifications\ListingCreated;
use App\Notifications\ListingNotification;
use App\Notifications\OfferNotification;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;
use Throwable;

class ListingController extends Controller
{
    public function index(){
        return view('frontend.pages.listings.all');
    }
    public function get_listing(){
        $user_id = Auth::id();
        $listing = Listing::where([
            'posted_by' => $user_id,
            'status' => 'posted'
        ])->orderBy('id','DESC')->get();
        
        return response()->json([
            'listings'=>$listing
        ]);
    }
    public function single_list($slug){
        $user = Auth::user();
        $listing = Listing::where('slug',$slug)->first();
        if($listing==null){
            abort(404);
        }
        if($listing->posted_by!==$user->id){
           // abort(403);
        }
        $listing_gallery = ListingGallery::where('listing_id',$listing->id)->get();
        // $deal = Deals :: where('listing_id',$listing->id)->first();
        // $invoice =null;
        // if($deal!==null){
        //     $user_id = Auth::id();
        //     $invoice = Invoices::where('deal_id',$deal->id)->where('user_id',$user_id)->first();
        // }
        
        // $offers = null;
        // if($deal==null){
        //     $offers = Offers::where('listing_id',$listing->id)->where('offer_status','!=','declined')->get();
        // }else{
        //     $offers = [];
        //     $offers[] = Offers::where('id',$deal->offer_id)->first();
        // }
        return view('frontend.pages.listings.single',[
            'listing' => $listing,
            'listing_gallery' => $listing_gallery
        ]);
    }
    public function edit($id){
        $user_id = Auth::id();
        $listing = Listing::findOrFail($id);
        
        $listing_gallery = ListingGallery::where('listing_id',$listing->id)->get();

        return view('frontend.pages.listings.edit',[
            'listing' => $listing,
            'listing_gallery' => $listing_gallery
        ]);
    }
    public function update(Request $request){
        $request->validate([
            'listing_id' => 'required',
            'product_title' => 'required|max:100',
            'product_description' => 'required|max:500',
            'product_img' => 'image|mimes:jpeg,png,jpg|max:15360',
            'authenticity' => 'required'
        ]);
        $id = $request->listing_id;
        $listing = Listing::findOrFail($id);
        if(isset($request->product_img)){
            
            $imageName = time().'.'.$request->product_img->extension();
            
            
            $request->product_img->move(public_path('images/products'), $imageName);
            @unlink($listing->product_img);
            $listing->product_img = "images/products/".$imageName;
            
            try{
                app(Spatie\ImageOptimizer\OptimizerChain::class)->optimize(public_path('images/products/'.$imageName));
            }
            catch(Throwable $e){
    
            }
        }
        $listing->product_title = $request->input('product_title');
        $listing->product_description = $request->input('product_description');
	      $listing->category = $request->input('category');
	      $listing->price = $request->input('price');
        $listing->authentic = $request->authenticity;
        $listing->update();
        $this->upload_gallery_images($request,$listing->id);
        $this->delete_gallery_images($request);
        return back()->with('success','Product details updated!');
    }
    public function newView(){
        return view('frontend.pages.listings.post');
    }
    public function newPost(Request $request){
        $request->validate([
            'product_title' => 'required|max:100',
            'product_description' => 'required|max:500',
            'product_img' => 'required|image|mimes:jpeg,png,jpg|max:15360',
            'authenticity' => 'required'
        ]);

        $imageName = uniqid().'.'.$request->product_img->extension();

        $img = Image::make($request->product_img->path());
        $img->resize(600, 600, function ($constraint) {
            $constraint->aspectRatio();
        })->save(storage_path('app/public/products_featured').'/'.$imageName);

        $posted_by = Auth::id();
        $slug = Str::slug($request->product_title, '-');
        $slug = $slug.'-'.substr(md5(mt_rand()),0, 4);
        $count = 1;
        while(1){
            $slug_duplicated = Listing::where('slug',$slug)->get();
            if($slug_duplicated==null or count($slug_duplicated)==0){
                break;
            }
            $slug.=$count++;
        }
        $authentic = $request->authenticity;
        $listing = Listing::create([
            'product_title' => $request->product_title,
            'product_description' => $request->product_description,
            'product_img' => $imageName,
            'posted_by' => $posted_by,
            'slug' => $slug,
            'authentic' => $authentic,
	          'category' => $request->category,
	          'price' => $request->price
        ]);
        $this->upload_gallery_images($request,$listing->id);
        
        $user = Auth::user();
        try {
            $user->notify(new ListingCreated($listing->product_img));
        }catch (Throwable $e) {
            report($e);
        }
        return redirect('exchange/'.$listing->slug)
            ->with('success','You have successfully created a listing!');
    }
    private function upload_gallery_images(Request $request,$listing_id=null){
        if($request->has('image') && $listing_id!==null){
            $images = $request->image;
            foreach($images as $image){
                try {
                    @list($type, $file_data) = explode(';', $image);
                    @list(, $file_data) = explode(',', $file_data);

                    $imageName = uniqid() . '.png';
                    $img = Image::make($file_data);
                    $img->resize(600, 600, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save(storage_path('app/public/products') . '/' . $imageName);

                    ListingGallery::create([
                        'listing_id' => $listing_id,
                        'image' => $imageName
                    ]);
                } catch (NotReadableException $e) {
                    report($e);
                }
            }
        }
    }
    private function delete_gallery_images(Request $request,$listing_id=null){
        if($request->has('delete')){
            $images = $request->delete;
            foreach($images as $image){
                $listing_image = ListingGallery::find($image);
                Storage::disk('public')->delete('products/'.$listing_image->image);
                $listing_image->delete();
            }
        }
    }
    public function cancel(Request $request){
        $request->validate([
            'listing' => 'required'
        ]);
        $listing = Listing:: findOrFail($request->listing);
        $listing->status = 'cancelled';
        $listing->save();
        
        $this->removeOffers($listing->id, $listing->product_img);
        $data = [
            'type' => 'cancelled',
            'message' => 'Your listing has been cancelled.',
            'url' => url('/')."/listings/add-listing",
            'url_text' => "Create another listing",
            'image_url' => $listing->product_img
        ];
        
        $user = Auth::user();
        try {
            $user->notify(new ListingNotification($data));
        }catch (Throwable $e) {
            report($e);
        }

        return back()
               ->with('success','Listing cancelled successfully!');
    }
    public function cancelbyAdmin(Request $request){
        $request->validate([
            'listing' => 'required'
        ]);
        $listing = Listing:: findOrFail($request->listing);
        $listing->status = 'cancelled';
        $listing->save();

        $this->removeOffers($listing->id, $listing->product_img);

        $data = [
            'type' => 'cancelled',
            'message' => 'Your listing has been cancelled by the admin!',
            'url' => url('/')."/listings/add-listing",
            'url_text' => "Create another listing",
            'image_url' => $listing->product_img
        ];
        
        $user = User::find($listing->posted_by);
        try {
            $user->notify(new ListingNotification($data));
        }catch (Throwable $e) {
            report($e);
        }

        return back()
               ->with('success','Listing cancelled successfully!');
    }

    /**
     * Remove offers when the listing is deleted.
     */
    private function removeOffers($listing_id, $product_img){
        
        $offers = Offers::where('listing_id', $listing_id)->get();

        $user = Auth::user();
        $i=0;
        while($i<count($offers)){
            $offers[$i]->offer_status="cancelled";
            $offers[$i]->save();        
            $user_noti = User::findOrFail($offers[$i]->posted_by);
            $data = [
                'type'=> 'declined',
                'message' => $user->username.' has declined your offer!',
                'image_url' => $product_img
            ];
            try {
                $user_noti->notify(new OfferNotification($data));
            }catch (Throwable $e) {
                report($e);
            }
            $i++;
        }
   }
}
