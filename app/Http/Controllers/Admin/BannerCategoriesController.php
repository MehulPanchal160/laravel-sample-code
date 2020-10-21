<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;
use Carbon\Carbon;
use Auth;
use App\BannerCategory;

class BannerCategoriesController extends AdminController {

    function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Get categories of banner
     * @return object
     */
    public function index() {
        $search = Input::get('search', '');
        $status = Input::get('status', '');
        $sort = Input::get('sort', 'id');
        $order = Input::get('order', 'desc');

        \DB::enableQueryLog();

        $bannerCategoryObj = BannerCategory::select("id", "name", "alias", "status")->where('status', '!=', 2);

        //Where conditions for search by String
        if ($search != '') {
            $bannerCategoryObj->Where(function ($query) use ($search) {
                $query->Where('name', 'like', "%$search%");
            });
        }
        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $bannerCategoryObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }

        $bannerCategoryObj->orderBy($sort, $order);
        $allData = $bannerCategoryObj->get();
        $total = $allData->count();
        if ($total > 0) {
            $this->apiData->categorieslist = array();
            foreach ($allData as $eachData) {
                $categoryData = array();
                $categoryData["id"] = $eachData->id;
                $categoryData["name"] = $eachData->name;
                $categoryData["alias"] = $eachData->alias;
                $categoryData["status"] = $eachData->status;
                $this->apiData->categorieslist[] = $categoryData;
            }
            $this->apiMessage = "";
            $this->apiData->count = $total;
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
     * Delete banner category
     * @param int $id
     * @return object
     */
    public function delete($id) {
        $bannerCategoryObj = BannerCategory::find($id);
        $bannerCategoryObj->status = 2;
        if ($bannerCategoryObj->save()) {
            $this->apiMessage = "Banner category deleted successfully.";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'Banner category not deleted successfully. Please try again later.';
        }
        return $this->response();
    }

    /**
     * Save banner category data
     * @param Request $request
     * @return object
     */
    public function add(Request $request) {
        $user = $this->getUser();
        $rules = array(
            'name' => 'required',
            'status' => 'required'
        );
        $messages = [
            
        ];

        $validator = Validator::make(Input::all(), $rules, $messages);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            // save data
            $bannerCategoryObj = new BannerCategory();
            $bannerCategoryObj->name = $request->name;
            $bannerCategoryObj->alias = $request->alias;
            $bannerCategoryObj->status = $request->status;
            
            if ($bannerCategoryObj->save()) {
                $this->apiCode = 200;
                $this->apiStatus = true;
                $this->apiMessage = "Banner category added successfully.";
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Banner category not added successfully. Please try again later.';
            }
        }
        return $this->response();
    }
    
    /**
     * Update banner category
     * @param Request $request
     * @param int $id
     * @return object
     */
    public function edit(Request $request, $id) {
        $user = $this->getUser();
        $rules = array(
            'name' => 'required',
            'status' => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $bannerCategoryObj = BannerCategory::find($id);
            if ($bannerCategoryObj) {
                // update data
                $bannerCategoryObj->name = $request->name;
                $bannerCategoryObj->alias = $request->alias;
                $bannerCategoryObj->status = $request->status;
                if ($bannerCategoryObj->save()) {
                    $this->apiMessage = "Banner category updated successfully.";
                    $this->apiCode = 200;
                    $this->apiStatus = true;
                } else {
                    $this->apiCode = 500;
                    $this->apiStatus = false;
                    $this->apiMessage = 'Banner category not updated successfully. Please try again later.';
                }
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Banner category not updated successfully. Please try again later.';
            }
        }
        return $this->response();
    }
    
    /**
     * Get banner category detail
     * @param int $id
     * @return object
     */
    public function details($id) {
        $bannerCategoryObj = BannerCategory::find($id);
        if ($bannerCategoryObj) {
            $this->apiData->data = array();
            $categoryData = array();
            $categoryData["id"] = $bannerCategoryObj->id;
            $categoryData["name"] = $bannerCategoryObj->name;
            $categoryData["alias"] = $bannerCategoryObj->alias;
            $categoryData["status"] = $bannerCategoryObj->status;
            $this->apiMessage = "";
            $this->apiData->data = $categoryData;
            $this->apiData->count = count($categoryData);
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
