<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;
use App\BlogCategories;
use File;
use Auth;

class BlogCategoriesController extends FrontController
{
    
    function __construct() {
        $this->apiData = new \stdClass();
    }  
     public function getBlogCategories() {
        $limit = (isset($request->limit) && $request->limit != '') ? $request->limit : 0;
//        dd($limit);
        $blogCategoriesObj = BlogCategories::select("id", "name", "alias", "image", "description")
                                                ->where('status', 1)
                                                ->orderBy('id', 'desc')
                                                ->when($limit, function ($query, $limit) {
                                                    return $query->limit($limit);
                                                }, function ($query) {
                                                    return $query;
                                                })
                                                ->get();

        if ($blogCategoriesObj[0]->exists()) {
            foreach ($blogCategoriesObj as $eachBlogCategory) {
                $blogCategoryData = array();
                $blogCategoryData["id"] = $eachBlogCategory->id;
                $blogCategoryData["name"] = $eachBlogCategory->name;
                $blogCategoryData["alias"] = $eachBlogCategory->alias;
                $blogCategoryData["image"] = $eachBlogCategory->image;
                $blogCategoryData["description"] = $eachBlogCategory->description;
                $blogCategoryData['image_url'] = $this->getImageUrl($eachBlogCategory->image, 'blog_categories');
                $this->apiData->blogcategorieslist[] = $blogCategoryData;
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
