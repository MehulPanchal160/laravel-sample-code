<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;
use App\Banner;
use File;
use Auth;
use User;

class BannerController extends AdminController {

    function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Get banners
     * @return Object
     */
    public function index() {

        $search = Input::get('search', '');
        $status = Input::get('status', '');
        $category = Input::get('category', '');
        $sort = Input::get('sort', 'id');
        $order = Input::get('order', 'desc');
        $page = Input::get('page', 0);
        $limit = Input::get('limit', 10);

        \DB::enableQueryLog();

        $bannerObj = Banner::select("id", "name", "alias", "image", "status")->where('status', '!=', 2);

        if ($search != '') {
            $bannerObj->Where(function ($query) use ($search) {
                $query->Where('name', 'like', "%$search%");
            });
        }
        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $bannerObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }
        //Where conditions for search by category
        if ((int) $category > 0) {
            $bannerObj->Where(function ($query) use ($category) {
                $query->Where('banner_category_id', '=', "$category");
            });
        }

        $bannerObj->orderBy($sort, $order);

        $allBannerData = $bannerObj->paginate($limit);
        $totalBannerCount = $allBannerData->total();

        if ($totalBannerCount > 0) {
            $this->apiData->bannerlist = array();
            foreach ($allBannerData as $eachBanner) {
                $bannerData = array();
                $bannerData["id"] = $eachBanner->id;
                $bannerData["name"] = $eachBanner->name;
                $bannerData["alias"] = $eachBanner->alias;
                $bannerData["image"] = $eachBanner->image;
                $bannerData["image_url"] = $this->getImageUrl($eachBanner->image, 'banners');
                $bannerData["status"] = $eachBanner->status;
                $this->apiData->bannerlist[] = $bannerData;
            }
            $this->apiMessage = "";
            $this->apiData->count = $totalBannerCount;
            $this->apiData->currentPage = $allBannerData->currentPage();
            $this->apiData->lastPage = $allBannerData->lastPage();
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'No records found.';
        }
        return $this->response();
    }

    /**
     * Delete banner
     * @param int $id
     * @return object
     */
    public function delete($id) {
        $bannerObj = Banner::find($id);
        $bannerObj->status = 2;
        if ($bannerObj->save()) {
            $this->apiMessage = "Banner deleted successfully.";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'Banner not deleted successfully. Please try again later.';
        }
        return $this->response();
    }

    /**
     * Save banner data
     * @param Request $request
     * @return object
     */
    public function add(Request $request) {

        $user = $this->getUser();
        $rules = array(
            'banner_category_id' => 'required',
            'name' => 'required',
            'image' => 'bail|image',
            'status' => 'required'
        );
        $messages = [
            'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.'
        ];

        $validator = Validator::make(Input::all(), $rules, $messages);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $bannerObj = new Banner();
            // Upload image
            $image_name = '';
            if ($request->file('image') != null) {
                $image_name = $this->uploadImage($request, 'banners');
            }
            $banner = Banner::create([
                        'banner_category_id' => $request->banner_category_id,
                        'name' => $request->name,
                        'alias' => $request->alias,
                        'image' => $image_name,
                        'status' => $request->status,
                        'description' => $request->description,
                        'meta_title' => $request->meta_title,
                        'meta_description' => $request->meta_description,
            ]);
            if ($banner) {
                $this->apiData = $banner->id;
                $this->apiCode = 200;
                $this->apiStatus = true;
                $this->apiMessage = "Banner added successfully.";
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Banner not added successfully. Please try again later.';
            }
        }
        return $this->response();
    }

    /**
     * Update banner data
     * @param Request $request
     * @return object
     */
    public function edit(Request $request, $id) {
        $user = $this->getUser();
        $rules = array(
            'banner_category_id' => 'required',
            'name' => 'required',
            'status' => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $bannerObj = Banner::find($id);
            if ($bannerObj) {
                $image_name = $bannerObj->image;
                if ($request->file('image') != null) {
                    $rules = array(
                        'image' => 'bail|image',
                    );
                    $messages = [
                        'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.'
                    ];

                    $validator = Validator::make(Input::all(), $rules, $messages);
                    if ($validator->fails()) {
                        $this->apiCode = 500;
                        $this->apiStatus = false;
                        $this->apiMessage = $validator->errors()->all();
                        return $this->response();
                    } else {
                        $image_name = $this->uploadImage($request, 'banners');
                        // Delete existing image
                        $this->deleteImage($bannerObj->image, 'banners');
                    }
                }
                $bannerObj->name = $request->name;
                $bannerObj->image = $image_name;
                $bannerObj->description = $request->description;
                $bannerObj->status = $request->status;
                $bannerObj->banner_category_id = $request->banner_category_id;
                $bannerObj->alias = $request->alias;
                $bannerObj->meta_title = $request->meta_title;
                $bannerObj->meta_description = $request->meta_description;
                if ($bannerObj->save()) {
                    $this->apiMessage = "Banner updated successfully.";
                    $this->apiCode = 200;
                    $this->apiStatus = true;
                } else {
                    $this->apiCode = 500;
                    $this->apiStatus = false;
                    $this->apiMessage = 'Banner not updated successfully. Please try again later.';
                }
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Banner not updated successfully. Please try again later.';
            }
        }
        return $this->response();
    }

    /**
     * Get banner detail
     * @param int $id
     * @return object
     */
    public function details($id) {
        $bannerObj = Banner::find($id);
        if ($bannerObj) {
            $this->apiData->data = array();
            $bannerData = array();
            $bannerData["id"] = $bannerObj->id;
            $bannerData["name"] = $bannerObj->name;
            $bannerData["alias"] = $bannerObj->alias;
            $bannerData["description"] = $bannerObj->description;
            $bannerData["image"] = $bannerObj->image;
            $bannerData["image_url"] = $this->getImageUrl($bannerObj->image, 'banners');
            $bannerData["status"] = $bannerObj->status;
            $bannerData["banner_category_id"] = $bannerObj->banner_category_id;
            $bannerData["meta_title"] = $bannerObj->meta_title;
            $bannerData["meta_description"] = $bannerObj->meta_description;

            $this->apiMessage = "";
            $this->apiData->data = $bannerData;
            $this->apiData->count = count($bannerData);
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'No records found.';
        }
        return $this->response();
    }

    /**
     * Get banners of category
     * @return object
     */
    public function getAllBanners(Request $request) {
        $category_id = (isset($request->category_id) && $request->category_id != '') ? $request->category_id : 0;
        $bannerObj = Banner::select("id", "name", "image", "description")->where('status', 1)->where('banner_category_id', $category_id)->get();
        
        if ($bannerObj[0]->exists()) {
            foreach ($bannerObj as $eachBanner) {
                $bannerData = array();
                $bannerData["id"] = $eachBanner->id;
                $bannerData["name"] = $eachBanner->name;
                $bannerData["image"] = $eachBanner->image;
                $bannerData["description"] = $eachBanner->description;
                $bannerData['image_url'] = $this->getImageUrl($eachBanner->image, 'banners');
                $this->apiData->bannerlist[] = $bannerData;
            }

            $this->apiMessage = "";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'No records found.';
        }
        return $this->response();
    }

}
