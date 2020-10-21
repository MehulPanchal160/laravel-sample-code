<?php

namespace App\Http\Controllers\Front;

use App\CmsPage;
use App\Faq;
use App\OurStaff;
use App\User;
use Illuminate\Http\Request;

class CmsController extends FrontController {

    public function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Get about us page data for all tabs.
     * @return object
     */
    public function getAboutUsData() {

        $aboutUsObj = CmsPage::where('alias', 'about-us')->where('status', 1)
                ->first();
        if ($aboutUsObj) {
            $aboutUsObj->bannerUrl = $this->getImageUrl($aboutUsObj
                    ->categories->banners->first()->image, 'banners');
        }

        $whoWeAreObj = CmsPage::where('alias', 'who-we-are')
                        ->where('status', 1)->first();

        $staffObj = OurStaff::where('status', 1)->get();
        if ($staffObj->isNotEmpty()) {
            foreach ($staffObj as $staff) {
                $staff->imageUrl = $this->getImageUrl($staff->image, 'staff_images');
            }
        }

        $members = $staffObj->where('staff_type', 0)->values();
        $partners = $staffObj->where('staff_type', 1)->values();
        $inHouseAdvisors = $staffObj->where('staff_type', 2)
                        ->where('advisor_type', 0)->values();
        $externalAdvisors = $staffObj->where('staff_type', 2)
                        ->where('advisor_type', 1)->values();

        $faqs = Faq::where('status', 1)->get();

        $getInTouch = CmsPage::where('status', 1)->where('alias', 'get-in-touch')->first();

        $data = [
            'aboutUs' => $aboutUsObj,
            'whoWeAre' => $whoWeAreObj,
            'members' => $members,
            'partners' => $partners,
            'inHouseAdvisors' => $inHouseAdvisors,
            'externalAdvisors' => $externalAdvisors,
            'faqs' => $faqs,
            'getInTouch' => $getInTouch
        ];

        if ($aboutUsObj) {
            $this->apiData = $data;
            $this->apiMessage = "About Us found.";
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
     * Send mails for get in touch enquiry.
     * @return type
     */
    public function mailGetInTouchEnquiry(Request $request) {
        $data = $request->all();
        $admin = User::select('email')
                        ->where('user_type', 0)->first();
        $mailData = [];
        $mailData['data']['subject'] = config('constants.CLIENT.SITE_NAME') .
                ' Enquiry';
        $mailData['data']['body'] = [
            'site' => config('constants.CLIENT.SITE_NAME'),
            'enquiry' => $data['enquiry'],
            'name' => $data['first_name'] . ' ' . $data['surname'],
            'email' => $data['email'],
            'number' => $data['number'],
            'text' => $data['message']
        ];

        $mailData['data']['name'] = $data['first_name'] . ' ' .
                $data['surname'];

        if ($data['copy_mail']) {
            $mailData['data']['recipients'] = [
                    [
                    'email' => $data['email'],
                    'name' => $data['first_name'] . ' ' . $data['surname']
                ]
            ];

            $mailData['template'] = 'emails.getintouchuser';
            $isUserMailSend = $this->mail($mailData);

            $mailData['data']['recipients'] = [
                    [
                    'email' => $admin->email
                ]
            ];

            $mailData['template'] = 'emails.getintouchadmin';
            $isAdminMailSend = $this->mail($mailData);
        } else {
            $mailData['data']['recipients'] = [
                    [
                    'email' => $admin->email
                ]
            ];

            $isUserMailSend = true;
            $mailData['template'] = 'emails.getintouchadmin';
            $isAdminMailSend = $this->mail($mailData);
        }

        if ($isUserMailSend && $isAdminMailSend) {
            $this->apiMessage = "Mails Sent";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "Something went wrong. Please try again.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Ask a question 
     * @param Request $request
     */
    public function askQuestion(Request $request) {
        $data = $request->all();
        $admin = User::select('email')->where('user_type', 0)->first();
        $mailData = [];
        $mailData['data']['subject'] = config('constants.CLIENT.SITE_NAME') . ' Ask a Question Enquiry';
        $mailData['data']['body'] = [
            'site' => config('constants.CLIENT.SITE_NAME'),
            'enquiry' => $data['enquiry_type'],
            'name' => $data['first_name'] . ' ' . $data['surname'],
            'email' => $data['email'],
            'number' => $data['contact_number'],
            'text' => $data['message']
        ];
        $mailData['data']['name'] = $data['first_name'] . ' ' . $data['surname'];
        $isUserMailSend = true;
        if ($data['send_copy']) {
            $mailData['data']['recipients'] = [[
            'email' => $data['email'],
            'name' => $data['first_name'] . ' ' . $data['surname']
                ]
            ];
            $mailData['template'] = 'emails.askaquestion';
            $isUserMailSend = $this->mail($mailData);
        }
        // Send mail to admin
        $mailData['data']['recipients'] = [['email' => $admin->email]];
        $mailData['template'] = 'emails.askaquestionadmin';
        
        $isAdminMailSend = $this->mail($mailData);

        if ($isUserMailSend && $isAdminMailSend) {
            $this->apiMessage = "Mails Sent";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "Something went wrong. Please try again.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

}
