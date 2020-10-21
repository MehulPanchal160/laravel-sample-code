<?php

namespace App\Http\Controllers\Admin;

use App\CmsPage;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;

class CMSPageController extends AdminController {

    public function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Get cms pages
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

        $cmsPageObj = CmsPage::select("id", "name", "alias", "image", "status")->where('status', '!=', 2);

        if ($search != '') {
            $cmsPageObj->Where(function ($query) use ($search) {
                $query->Where('name', 'like', "%$search%");
            });
        }
        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $cmsPageObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }
        //Where conditions for search by category
        if ((int) $category > 0) {
            $cmsPageObj->Where(function ($query) use ($category) {
                $query->Where('banner_category_id', '=', "$category");
            });
        }

        $cmsPageObj->orderBy($sort, $order);

        $allCmsPageData = $cmsPageObj->paginate($limit);
        $totalCmsPageCount = $allCmsPageData->total();

        if ($totalCmsPageCount > 0) {
            $this->apiData->cmsPagelist = [];
            foreach ($allCmsPageData as $eachCmsPage) {
                $cmsPageData = [];
                $cmsPageData["id"] = $eachCmsPage->id;
                $cmsPageData["name"] = $eachCmsPage->name;
                $cmsPageData["alias"] = $eachCmsPage->alias;
                $cmsPageData["image"] = $eachCmsPage->image;
                $cmsPageData["image_url"] = $this->getImageUrl($eachCmsPage->image, 'cms_pages');
                $cmsPageData["status"] = $eachCmsPage->status;
                $this->apiData->cmsPagelist[] = $cmsPageData;
            }
            $this->apiMessage = "";
            $this->apiData->count = $totalCmsPageCount;
            $this->apiData->currentPage = $allCmsPageData->currentPage();
            $this->apiData->lastPage = $allCmsPageData->lastPage();
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
     * Delete cms page
     * @param int $id
     * @return object
     */
    public function delete($id) {
        $cmsPageObj = CmsPage::find($id);
        $cmsPageObj->status = 2;
        if ($cmsPageObj->save()) {
            $this->apiMessage = "CMS Page deleted successfully.";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'CMS Page not deleted successfully. Please try again later.';
        }
        return $this->response();
    }

    /**
     * Save cms page data
     * @param Request $request
     * @return object
     */
    public function add(Request $request) {

        $user = $this->getUser();
        $rules = [
            'banner_category_id' => 'required',
            'name' => 'required',
            'content' => 'required',
            'status' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            // Upload file
            $image_name = '';
            $new_file_names = array();
            if ($request->hasFile('pdfs')) {
                $allowedfileExtension = ['pdf'];
                $files = $request->file('pdfs');
                foreach ($files as $file) {
                    $filename = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);
                    if ($check) {
                        $filename = str_replace_last('.' . $extension, '', $filename);
                        $rename = $this->toAscii($filename, array(), '_') . '_' . time() . '.' . $extension;
                        $file->storeAs('guides', $rename, 'uploads');
                        $new_file_names[] = $rename;
                    }
                }
                if (!empty($new_file_names)) {
                    $image_name = implode(",", $new_file_names);
                }
            }
//            $image_name = null;
//            if ($request->file('image') != null) {
//                $rules = [
//                    'image' =>
//                    'dimensions:min_width=200,max_width=600'
//                ];
//                $messages = [
//                    'image.dimensions' =>
//                    'Please select image with width between 200px to 600px.'
//                ];
//
//                $validator = Validator::make($request->all(), $rules, $messages);
//                if ($validator->fails()) {
//                    $this->apiCode = 500;
//                    $this->apiStatus = false;
//                    $this->apiMessage = $validator->errors()->all();
//                    return $this->response();
//                } else {
//                    $image_name = $this->uploadImage($request, 'cms_pages');
//                }
//            }
            $cmsPage = CmsPage::create([
                        'banner_category_id' => $request->banner_category_id,
                        'name' => $request->name,
                        'alias' => $request->alias,
                        'image' => $image_name,
                        'status' => $request->status,
                        'description' => $request->content,
                        'meta_title' => $request->meta_title,
                        'meta_description' => $request->meta_description
            ]);
            if ($cmsPage) {
                $this->apiData = $cmsPage->id;
                $this->apiCode = 200;
                $this->apiStatus = true;
                $this->apiMessage = "CMS Page added successfully.";
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'CMS Page not added successfully. Please try again later.';
            }
        }
        return $this->response();
    }

    /**
     * Update cms page data
     * @param Request $request
     * @return object
     */
    public function edit(Request $request, $id) {
        $user = $this->getUser();
        $rules = [
            'banner_category_id' => 'required',
            'name' => 'required',
            'content' => 'required',
            'status' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $cmsPageObj = CmsPage::find($id);
            if ($cmsPageObj) {
                $image_name = $cmsPageObj->image;
//                if ($request->file('image') != null) {
//                    $rules = [
//                        'image' => 'dimensions:min_width=200,max_width=600'
//                    ];
//                    $messages = [
//                        'image.dimensions' =>
//                        'Please select image with width between 200px to 600px.'
//                    ];
//
//                    $validator = Validator::make($request->all(), $rules, $messages);
//                    if ($validator->fails()) {
//                        $this->apiCode = 500;
//                        $this->apiStatus = false;
//                        $this->apiMessage = $validator->errors()->all();
//                        return $this->response();
//                    } else {
//                        $image_name = $this->uploadImage($request, 'cms_pages');
//                        // Delete existing image
//                        $this->deleteImage($cmsPageObj->image, 'cms_pages');
//                    }
//                }
                $new_file_names = array();

                if ($request->hasFile('pdfs')) {
                    $allowedfileExtension = ['pdf'];
                    $files = $request->file('pdfs');
                    foreach ($files as $file) {
                        $filename = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $check = in_array($extension, $allowedfileExtension);
                        if ($check) {
                            $filename = str_replace_last('.' . $extension, '', $filename);
                            $rename = $this->toAscii($filename, array(), '_') . '_' . time() . '.' . $extension;
                            $file->storeAs('guides', $rename, 'uploads');
                            $new_file_names[] = $rename;
                        }
                    }
                    $uploaded_file_names = $image_name;
                    if (!empty($new_file_names)) {
                        $uploaded_file_names = implode(",", $new_file_names);
                    }
                    // Delete existing file
                    if ($image_name != '') {
                        $files_name = explode(",", $image_name);
                        if (!empty($files_name)) {
                            foreach ($files_name as $file_name) {
                                $this->deleteImage($file_name, 'guides');
                            }
                        }
                    }
                    $image_name = $uploaded_file_names;
                    //dd($new_file_names);
                }
                $cmsPageObj->name = $request->name;
                $cmsPageObj->image = $image_name;
                $cmsPageObj->description = $request->content;
                $cmsPageObj->status = $request->status;
                $cmsPageObj->banner_category_id = $request->banner_category_id;
                $cmsPageObj->alias = $request->alias;
                $cmsPageObj->meta_title = $request->meta_title;
                $cmsPageObj->meta_description = $request->meta_description;
                if ($cmsPageObj->save()) {
                    $this->apiMessage = "CMS Page updated successfully.";
                    $this->apiCode = 200;
                    $this->apiStatus = true;
                } else {
                    $this->apiCode = 500;
                    $this->apiStatus = false;
                    $this->apiMessage = 'CMS Page not updated successfully. Please try again later.';
                }
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'CMS Page not updated successfully. Please try again later.';
            }
        }
        return $this->response();
    }

    /**
     * Get cms page detail
     * @param int $id
     * @return object
     */
    public function details($id) {
        $cmsPageObj = CmsPage::find($id);
        if ($cmsPageObj) {
            $this->apiData->data = [];
            $cmsPageData = [];
            $cmsPageData["id"] = $cmsPageObj->id;
            $cmsPageData["name"] = $cmsPageObj->name;
            $cmsPageData["alias"] = $cmsPageObj->alias;
            $cmsPageData["description"] = $cmsPageObj->description;
            $cmsPageData["image"] = $cmsPageObj->image;
            $files_name = array();
            $files = array();
            if ($cmsPageObj->image != '') {
                $files_name = explode(",", $cmsPageObj->image);
                foreach ($files_name as $file_name) {
                    $files[] = $this->getImageUrl($file_name, 'guides');
                }
            }
            $cmsPageData["files"] = $files;
            $cmsPageData['files_name'] = $files_name;
            $cmsPageData["status"] = $cmsPageObj->status;
            $cmsPageData["banner_category_id"] = $cmsPageObj
                    ->banner_category_id;
            $cmsPageData["meta_title"] = $cmsPageObj->meta_title;
            $cmsPageData["meta_description"] = $cmsPageObj->meta_description;

            $this->apiMessage = "";
            $this->apiData->data = $cmsPageData;
            $this->apiData->count = count($cmsPageData);
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
