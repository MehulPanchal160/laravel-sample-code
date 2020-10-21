<?php

namespace App\Http\Controllers\Admin;

use App\Volunteer;
use App\VolunteerStory;
use App\VolunteerHelp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;

class VolunteerController extends AdminController {

    public function __construct() {
        $this->apiData = new \stdClass();
    }

    //insert data
    public function add(Request $request) {
        $user = $this->getUser();
        $rules = [
            'firstname' => 'required',
            'lastname' => 'required',
            'description' => 'required',
            'other_information' => 'required',
            'status' => 'required',
            'image' => 'bail|image'
        ];
        $messages = [
            'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $image_name = '';
            if ($request->file('image') != null) {
                $image_name = $this->uploadImage($request, 'volunteers_stories');
            }
            $volunteerStoryObj = new VolunteerStory();
            $volunteerStoryObj->firstname = $request->firstname;
            $volunteerStoryObj->lastname = $request->lastname;
            $volunteerStoryObj->other_information = $request
                    ->other_information;
            $volunteerStoryObj->image = $image_name;
            $volunteerStoryObj->description = $request->description;
            $volunteerStoryObj->status = $request->status;
            $volunteerStoryObj->meta_title = $request->meta_title;
            $volunteerStoryObj->meta_description = $request->meta_description;
            $volunteerStoryObj->created_by = $user->id;
            $volunteerStoryObj->updated_by = $user->id;

            if ($volunteerStoryObj->save()) {
                $this->apiCode = 200;
                $this->apiStatus = true;
                $this->apiMessage = "volunteer story added";
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = "volunteer story not added";
            }
        }
        return $this->response();
    }

    public function index() {

        $search = Input::get('search', '');
        $status = Input::get('status', '');
        $sort = Input::get('sort', 'id');
        $order = Input::get('order', 'desc');
        $limit = Input::get('limit', 10);
        $volunteerStoryObj = VolunteerStory::select("id", "firstname", "lastname", "other_information", "image", "status")->where('status', '!=', 2);

        //Where conditions for search by String
        if ($search != '') {
            $volunteerStoryObj->Where(function ($query) use ($search) {
                $query->Where('firstname', 'like', "%$search%");
                $query->orWhere('lastname', 'like', "%$search%");
                $query->orWhere('other_information', 'like', "%$search%");
            });
        }
        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $volunteerStoryObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }
        $volunteerStoryObj->orderBy($sort, $order);
        if ($limit == 0) {
            $allData = $volunteerStoryObj->get();
            $total = $allData->count();
        } else {
            $allData = $volunteerStoryObj->paginate($limit);
            $total = $allData->total();
            $this->apiData->currentPage = $allData->currentPage();
            $this->apiData->lastPage = $allData->lastPage();
        }
        if ($total > 0) {
            $this->apiData->volunteerlist = [];
            foreach ($allData as $eachData) {
                $volunteerData = [];
                $volunteerData["id"] = $eachData->id;
                $volunteerData["firstname"] = $eachData->firstname;
                $volunteerData["lastname"] = $eachData->lastname;
                $volunteerData["other_information"] = $eachData
                        ->other_information;
                $volunteerData["status"] = $eachData->status;
                $volunteerData["image"] = $eachData->image;
                $volunteerData["image_url"] = $this->getImageUrl($eachData
                        ->image, 'volunteers_stories');
                $this->apiData->volunteerlist[] = $volunteerData;
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

    public function edit(Request $request, $id) {
        $user = $this->getUser();
        $rules = [
            'firstname' => 'required',
            'lastname' => 'required',
            'description' => 'required',
            'other_information' => 'required',
            'status' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $volunteerStoryObj = VolunteerStory::find($id);
            if ($volunteerStoryObj) {
                // Upload image
                $image_name = $volunteerStoryObj->image;

                if ($request->file('image') != null) {
                    $rules = [
                        'image' => 'bail|image'
                    ];
                    $messages = [
                        'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.'
                    ];
                    $validator = Validator::make($rules, $messages);
                    if ($validator->fails()) {
                        $this->apiMessage = $validator->errors()->all();
                        $this->apiCode = 500;
                        $this->apiStatus = false;
                        return $this->response();
                    } else {
                        $image_name = $this->uploadImage($request, 'volunteers_stories');
                        // Delete existing image
                        $this->deleteImage($volunteerStoryObj->image, 'volunteers_stories');
                    }
                }
                //update data
                $volunteerStoryObj->firstname = $request->firstname;
                $volunteerStoryObj->lastname = $request->lastname;
                $volunteerStoryObj->other_information = $request
                        ->other_information;
                $volunteerStoryObj->description = $request->description;
                $volunteerStoryObj->image = $image_name;
                $volunteerStoryObj->status = $request->status;
                $volunteerStoryObj->meta_title = $request->meta_title;
                $volunteerStoryObj->meta_description = $request
                        ->meta_description;
                $volunteerStoryObj->updated_by = $user->id;
            }
            if ($volunteerStoryObj->save()) {
                $this->apiMessage = "Volunteer Story edited successfully.";
                $this->apiCode = 200;
                $this->apiStatus = true;
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Volunteer Story not updated. Please try again later.';
            }
        }
        return $this->response();
    }

    public function details($id) {

        $volunteer = VolunteerStory::find($id);

        if ($volunteer) {
            $volunteerData = [];
            $volunteerData["id"] = $volunteer->id;
            $volunteerData["firstname"] = $volunteer->firstname;
            $volunteerData["lastname"] = $volunteer->lastname;
            $volunteerData["description"] = $volunteer->description;
            $volunteerData["other_information"] = $volunteer
                    ->other_information;
            $volunteerData["image"] = $volunteer->image;
            $volunteerData["image_url"] = $this->getImageUrl($volunteer
                    ->image, 'volunteers_stories');
            $volunteerData["status"] = $volunteer->status;
            $volunteerData["meta_title"] = $volunteer->meta_title;
            $volunteerData["meta_description"] = $volunteer
                    ->meta_description;

            $this->apiData = $volunteerData;
            $this->apiMessage = "Data updates succesfully";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "No Volunteer found";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    public function delete($id) {
        $volunteer = VolunteerStory::find($id);
        $this->apiCode = 500;
        $this->apiStatus = false;
        $this->apiMessage = 'Volunteer not deleted...Please try again.';

        if ($volunteer) {
            $volunteer->status = 2;
            if ($volunteer->save()) {
                $this->apiMessage = "Volunteer deleted successfully.";
                $this->apiCode = 200;
                $this->apiStatus = true;
            }
        }
        return $this->response();
    }

    /**
     * Get volunteers
     * @return Object
     */
    public function volunteerList() {

        $search = Input::get('search', '');
        $status = Input::get('status', '');
        $sort = Input::get('sort', 'id');
        $order = Input::get('order', 'desc');
        $page = Input::get('page', 0);
        $limit = Input::get('limit', 10);

        \DB::enableQueryLog();

        $volunteersObj = Volunteer::where('status', '!=', 2);

        if ($search != '') {
            $volunteersObj->Where(function ($query) use ($search) {
                $query->Where('fullname', 'like', "%$search%");
            });
        }
        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $volunteersObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }

        $volunteersObj->orderBy($sort, $order);

        $allVolunteerData = $volunteersObj->paginate($limit);
        $totalCount = $allVolunteerData->total();

        if ($totalCount > 0) {
            $this->apiData->volunteers = [];
            foreach ($allVolunteerData as $eachVolunteer) {
                $this->apiData->volunteers[] = $eachVolunteer;
            }
            $this->apiMessage = "";
            $this->apiData->count = $totalCount;
            $this->apiData->currentPage = $allVolunteerData->currentPage();
            $this->apiData->lastPage = $allVolunteerData->lastPage();
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
     * Get volunteers
     * @return Object
     */
    public function volunteerDetails($id) {
        $volunteerObj = Volunteer::find($id);

        if ($volunteerObj) {
            $volunteerObj->mon = $this->getAvailabilityString($volunteerObj
                    ->availability_of_mon);
            $volunteerObj->tue = $this->getAvailabilityString($volunteerObj
                    ->availability_of_tue);
            $volunteerObj->wed = $this->getAvailabilityString($volunteerObj
                    ->availability_of_wed);
            $volunteerObj->thu = $this->getAvailabilityString($volunteerObj
                    ->availability_of_thu);
            $volunteerObj->fri = $this->getAvailabilityString($volunteerObj
                    ->availability_of_fri);
            $volunteerObj->sat = $this->getAvailabilityString($volunteerObj
                    ->availability_of_sat);
            $volunteerObj->sun = $this->getAvailabilityString($volunteerObj
                    ->availability_of_sun);
            $volunteerObj->informed = str_replace('1', 'Email', str_replace('2', 'Post', str_replace('3', 'Text', str_replace('4', 'Phone', $volunteerObj->keeping_informed))));
            $volunteerObj->date = $volunteerObj->created_at->format('d-m-Y');
            $this->apiMessage = "Found";
            $this->apiData = $volunteerObj;
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
     * send availability string
     * @return string
     */
    public function getAvailabilityString($string) {
        $availabilityString = str_replace('0', 'Not Available', str_replace('1', 'Morning', str_replace('2', 'Afternoon', str_replace('3', 'Evening', str_replace('4', 'All Day', $string)))));
        return $availabilityString;
    }

    /**
     * Get volunteer help list
     * @return object
     */
    public function helpListing() {
        $search = Input::get('search', '');
        $status = Input::get('status', '');
        $sort = Input::get('sort', 'id');
        $order = Input::get('order', 'desc');
        $limit = Input::get('limit', 10);

        $helpObj = VolunteerHelp::select("id", "name", "image", "status", "description")->where('status', '!=', 2);

        //Where conditions for search by String
        if ($search != '') {
            $helpObj->Where(function ($query) use ($search) {
                $query->Where('name', 'like', "%$search%");
            });
        }

        //Where conditions for search by Status
        if ((int) $status >= 0) {
            $helpObj->Where(function ($query) use ($status) {
                $query->Where('status', '=', "$status");
            });
        }

        $helpObj->orderBy($sort, $order);
        $allData = $helpObj->paginate($limit);
        $totalCount = $allData->total();
        if ($totalCount > 0) {
            $this->apiData->helpList = array();
            foreach ($allData as $eachData) {
                $helpData = array();
                $helpData["id"] = $eachData->id;
                $helpData["name"] = $eachData->name;
                $helpData["description"] = htmlspecialchars_decode(html_entity_decode(strip_tags($eachData->description)));
                $helpData["image_url"] = $this->getImageUrl($eachData->image, 'volunteers_help');
                $helpData["status"] = $eachData->status;
                $this->apiData->helpList[] = $helpData;
            }
            $this->apiMessage = "";
            $this->apiData->count = $totalCount;
            $this->apiData->currentPage = $allData->currentPage();
            $this->apiData->lastPage = $allData->lastPage();
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
     * Delete volunteer help
     * @param int $id
     * @return object
     */
    public function helpDelete($id) {
        $helpObj = VolunteerHelp::find($id);
        $helpObj->status = 2;
        if ($helpObj->save()) {
            $this->apiMessage = "Volunteer help deleted successfully.";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = 'Volunteer help not deleted successfully. Please try again later.';
        }
        return $this->response();
    }
    /**
     * Save volunteer help data
     * @param Request $request
     * @return object
     */
    public function helpAdd(Request $request) {
        $user = $this->getUser();
        $rules = array(
            'name' => 'required',
            'description' => 'required',
            'image' => 'bail|image|dimensions:min_width=345,max_width=1000',
            'status' => 'required'
        );
        $messages = [
            'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.',
            'image.dimensions' => 'Please select image with width between 345px to 1000px.',
        ];

        $validator = Validator::make(Input::all(), $rules, $messages);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            // Upload image
            $image_name = '';
            if ($request->file('image') != null) {
                $image_name = $this->uploadImage($request, 'volunteers_help');
            }
            $helpObj = new VolunteerHelp();
            $helpObj->name = $request->name;
            $helpObj->description = $request->description;
            $helpObj->image = $image_name;
            $helpObj->status = $request->status;

            if ($helpObj->save()) {
                $this->apiCode = 200;
                $this->apiStatus = true;
                $this->apiMessage = "Volunteer help added successfully.";
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Volunteer help not added successfully. Please try again later.';
            }
        }
        return $this->response();
    }
    /**
     * Update volunteer help data
     * @param Request $request
     * @return object
     */
    public function helpEdit(Request $request, $id) {
        $user = $this->getUser();
        $rules = array(
            'name' => 'required',
            'description' => 'required',
            'status' => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $helpObj = VolunteerHelp::find($id);
            if ($helpObj) {
                // Upload image
                $image_name = $helpObj->image;
                if ($request->file('image') != null) {
                    $rules = array(
                        'image' => 'bail|image|dimensions:min_width=345,max_width=1000'
                    );
                    $messages = [
                        'image.image' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed.',
                        'image.dimensions' => 'Please select image with width between 345px to 1000px.',
                    ];
                    $validator = Validator::make(Input::all(), $rules, $messages);
                    if ($validator->fails()) {
                        $this->apiMessage = $validator->errors()->all();
                        $this->apiCode = 500;
                        $this->apiStatus = false;
                        return $this->response();
                    } else {
                        $image_name = $this->uploadImage($request, 'volunteers_help');
                        // Delete existing image
                        $this->deleteImage($helpObj->image, 'volunteers_help');
                    }
                }
                $helpObj->name = $request->name;
                $helpObj->description = $request->description;
                $helpObj->status = $request->status;
                $helpObj->image = $image_name;
                if ($helpObj->save()) {
                    $this->apiMessage = "Volunteer help updated successfully.";
                    $this->apiCode = 200;
                    $this->apiStatus = true;
                } else {
                    $this->apiCode = 500;
                    $this->apiStatus = false;
                    $this->apiMessage = 'Volunteer help not updated successfully. Please try again later.';
                }
            } else {
                $this->apiCode = 500;
                $this->apiStatus = false;
                $this->apiMessage = 'Volunteer help not updated successfully. Please try again later.';
            }
        }
        return $this->response();
    }
    
    /**
     * Get volunteer help detail
     * @param int $id
     * @return object
     */
    public function helpDetails($id) {
        $helpObj = VolunteerHelp::find($id);
        if ($helpObj) {
            $this->apiData->data = array();
            $helpData = array();
            $helpData["id"] = $helpObj->id;
            $helpData["name"] = $helpObj->name;
            $helpData["description"] = $helpObj->description;
            $helpData["image_url"] = $this->getImageUrl($helpObj->image, 'volunteers_help');
            $helpData["status"] = $helpObj->status;
            $this->apiMessage = "";
            $this->apiData->data = $helpData;
            $this->apiData->count = count($helpData);
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
