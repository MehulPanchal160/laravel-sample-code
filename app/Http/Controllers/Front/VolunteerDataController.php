<?php

namespace App\Http\Controllers\Front;

use App\CmsPage;
use App\Volunteer;
use App\VolunteerStory;
use App\VolunteerHelp;
use Illuminate\Http\Request;
use Validator;

class VolunteerDataController extends FrontController {

    public function __construct() {
        $this->apiData = new \stdClass();
    }

    public function getVolunteerData() {
        $volunteerObj = CmsPage::where('alias', 'volunteer')->where('status', 1)
                ->first();
        if ($volunteerObj) {
            $volunteerObj->bannerUrl = $this->getImageUrl($volunteerObj
                    ->categories->banners->first()->image, 'banners');
        }

        $volunteerStoryObj = VolunteerStory::select("id", "firstname", "lastname", "other_information", "description", "image", "status")
                        ->where('status', '!=', 2)->get();

        if ($volunteerStoryObj->isNotEmpty()) {
            foreach ($volunteerStoryObj as $volunteerStory) {
                $volunteerStory->imageUrl = $this
                        ->getImageUrl($volunteerStory->image, 'volunteers_stories');
            }
        }

        // Get volunteer help list
        $helpObj = VolunteerHelp::where('status', 1)->get();
        $helpData = array();
        if ($helpObj->isNotEmpty()) {
            foreach ($helpObj as $eachHelp) {
                $helpData[] = array(
                    'name' => $eachHelp->name,
                    'description' => $eachHelp->description,
                    'image' => $this->getImageUrl($eachHelp->image, 'volunteers_help')
                );
            }
        }

        $data = [
            'volunteer' => $volunteerObj,
            'volunteerdetail' => $volunteerStoryObj,
            'helpData' => $helpData
        ];

        if ($volunteerObj) {
            $this->apiData = $data;
            $this->apiMessage = "Volunteer found.";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiData = $data;
            $this->apiMessage = "No records found!";
            $this->apiCode = 200;
            $this->apiStatus = true;
        }
        return $this->response();
    }

    /**
     * Store become volunteer form
     * @return object
     */
    public function becomeVolunteer(Request $request) {
        $rules = [
            'full_name' => 'required',
            'gender' => 'required',
            'dob' => 'required',
            'over_sixteen' => 'required',
            'mobile_number' => 'required',
            'telephone_number' => 'required',
            'region' => 'required',
            'address_line_1' => 'required',
            'address_line_2' => 'required',
            'city' => 'required',
            'postcode' => 'required',
            'requirements' => 'required',
            'skills' => 'required',
            'interests' => 'required',
            'experience' => 'required',
            'contact_name' => 'required',
            'contact_number' => 'required',
            'contact_relationship' => 'required',
            'referee_name' => 'required',
            'referee_number' => 'required',
            'referee_email' => 'required',
            'referee_known_years' => 'required',
            'referee_relationship' => 'required',
            'volunteering_status' => 'required',
            'not_employment' => 'required',
            'reference_check' => 'required',
            'comply_with_charity' => 'required',
            'contacting' => 'required',
            'personal_information' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiCode = 500;
            $this->apiStatus = false;
            $this->apiMessage = $validator->errors()->all();
        } else {
            $informed = '';
            if ($request->email_notify) {
                $informed .= '1';
            }
            if ($request->post_notify) {
                $informed .= (strlen($informed) > 0) ?
                        (ends_with($informed, ',') ? '2' : ',2') : '2';
            }
            if ($request->text_notify) {
                $informed .= (strlen($informed) > 0) ?
                        (ends_with($informed, ',') ? '3' : ',3') : '3';
            }
            if ($request->phone_notify) {
                $informed .= (strlen($informed) > 0) ?
                        (ends_with($informed, ',') ? '4' : ',4') : '4';
            }

            $monday = $this->getAvailabilityString($request, 'mon');
            $tuesday = $this->getAvailabilityString($request, 'tue');
            $wednesday = $this->getAvailabilityString($request, 'wed');
            $thursday = $this->getAvailabilityString($request, 'thu');
            $friday = $this->getAvailabilityString($request, 'fri');
            $saturday = $this->getAvailabilityString($request, 'sat');
            $sunday = $this->getAvailabilityString($request, 'sun');

            $arr = [
                'fullname' => $request->full_name,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'over_sixteen' => $request->over_sixteen,
                'mobile_number' => $request->mobile_number,
                'telephone_number' => $request->telephone_number,
                'region' => $request->region,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'postcode' => $request->postcode,
                'medical_conditions' => $request->requirements,
                'skills' => $request->skills,
                'interests' => $request->interests,
                'experience' => $request->experience,
                'availability_of_mon' => ((strlen($monday) > 0) ?
                $monday : 0),
                'availability_of_tue' => ((strlen($tuesday) > 0) ?
                $tuesday : 0),
                'availability_of_wed' => ((strlen($wednesday) > 0) ? $wednesday : 0),
                'availability_of_thu' => ((strlen($thursday) > 0) ? $thursday : 0),
                'availability_of_fri' => ((strlen($friday) > 0) ?
                $friday : 0),
                'availability_of_sat' => ((strlen($saturday) > 0) ? $saturday : 0),
                'availability_of_sun' => ((strlen($sunday) > 0) ?
                $sunday : 0),
                'emergency_contact_name' => $request->contact_name,
                'emergency_contact_number' => $request->contact_number,
                'emergency_contact_relationship' => $request
                ->contact_relationship,
                'reference_name' => $request->referee_name,
                'reference_number' => $request->referee_number,
                'reference_email' => $request->referee_email,
                'how_long_they_known' => $request
                ->referee_known_years,
                'how_do_you_know' => $request
                ->referee_relationship,
                'keeping_informed' => $informed,
                'legally_stay' => $request
                ->volunteering_status,
                'not_employment' => ($request->not_employment ? 1 : 0),
                'reference_check' => ($request->reference_check ? 1 : 0),
                'comply_with_charity' => ($request
                ->comply_with_charity ? 1 :
                0),
                'contacting' => ($request->contacting ? 1 : 0),
                'personal_information' => ($request
                ->personal_information ? 1 : 0)
            ];

            $volunteer = Volunteer::create($arr);
            if ($volunteer) {
                $this->apiMessage = "Volunteer saved!";
                $this->apiCode = 200;
                $this->apiStatus = true;
            } else {
                $this->apiMessage = "Something went wrong. Please try again.";
                $this->apiCode = 500;
                $this->apiStatus = false;
            }
        }
        return $this->response();
    }

    /**
     * send availability string
     * @return string
     */
    public function getAvailabilityString(Request $request, $day) {
        $data = $request->all();
        $availabilityString = '';
        if ($data[$day . '_mor']) {
            $availabilityString .= '1';
        }
        if ($data[$day . '_aft']) {
            $availabilityString .= (strlen($availabilityString) > 0) ?
                    (ends_with($availabilityString, ',') ? '2' : ',2') : '2';
        }
        if ($data[$day . '_eve']) {
            $availabilityString .= (strlen($availabilityString) > 0) ?
                    (ends_with($availabilityString, ',') ? '3' : ',3') : '3';
        }
        if ($data[$day . '_all']) {
            $availabilityString .= (strlen($availabilityString) > 0) ?
                    (ends_with($availabilityString, ',') ? '4' : ',4') : '4';
        }
        return $availabilityString;
    }

}
