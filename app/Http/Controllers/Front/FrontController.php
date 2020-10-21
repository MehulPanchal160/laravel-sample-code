<?php

namespace App\Http\Controllers\Front;

use App\Children;
use App\Country;
use App\FamilyMember;
use App\Http\Controllers\APIController;
use App\Member;
use App\MonthlyExpense;
use App\MonthlyGrossIncome;
use App\NetAsset;
use App\OtherOrganisation;
use App\Partner;
use App\Referee;
use App\Situation;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Mail;
use PDF;
use Validator;

class FrontController extends APIController {

    protected $apiData = '';
    protected $apiCode = 500;
    protected $apiStatus = false;
    protected $apiMessage = '';

    public function __construct() {

        // Init basic parameters
        $this->page = Input::get('page', 1);
        $this->pageLimit = Input::get('limit', 10);
        $this->apiData = new \stdClass();
        // $this->auth = auth()->guard('donor');
        $this->auth = auth();
    }

    /**
     * Check if user is loggedin or not and return details
     * @return type
     */
    public function checkLoginAndReturnDetails() {

        $user = Auth::user();
        if (empty($user)) {
            $this->apiMessage = "Please Login first!";
            $this->apiCode = 500;
            $this->apiStatus = false;
        } else {
            if ($user->user_type == 1) {
                $user->image = $this->getImageUrl($user->photo, 'profile_photos');
            }
            $this->apiData = $user;
            $this->apiCode = 200;
            $this->apiStatus = true;
        }
        return $this->response();
    }

    /**
     * Get logged in user
     * @return Object
     */
    protected function getUser() {
        $user = Auth::user();
        return $user;
    }

    /**
     * Get all countries
     * @return type
     */
    public function getCountries($locations = '') {
        $countries = Country::where('status', 1);
        if ($locations != '' && $locations != null) {
            $arr = explode(',', $locations);
            $arr[] = 1; //where most needed
            $countries = $countries->whereIn('id', $arr);
        } else {
            $countries = $countries->whereNotIn('id', [1]);
        }
        $countries = $countries->get();
        if ($countries->isNotEmpty()) {
            $this->apiData = $countries;
            $this->apiMessage = "Countries";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "No records found.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Get all states
     * @param Request $request
     * @return type
     */
    public function getStates(Request $request) {
        $states = State::where('country_id', $request->country_id)
                        ->where('status', 1)->get();
        if ($states->isNotEmpty()) {
            $this->apiData = $states->toArray();
            $this->apiMessage = "States";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "No records found.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Get all cities
     * @param Request $request
     * @return type
     */
    public function getCities(Request $request) {
        $cities = City::where('state_id', $request->state_id)->where('status', 1)->get();
        if ($cities->isNotEmpty()) {
            $this->apiData = $cities->toArray();
            $this->apiMessage = "City";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "No records found.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Check table alias is existing or not
     * @param Request $request
     * @return Object
     */
    public function isAliasExist(Request $request) {
        $alias = $request->alias;
        $tableName = $request->table_name;
        $id = $request->id;
        $tableAlias = [
            'blog_categories',
            'blog_posts',
            'categories',
            'cms_pages',
            'projects',
            'banner_categories',
            'banners',
            'payment_categories',
            'fundraiser'
        ];
        // Check table alias & alias value
        if (!in_array($tableName, $tableAlias)) {
            $this->apiMessage = "Bad table alias value";
            $this->apiCode = 401;
        } else if (empty($alias)) {
            $this->apiMessage = "Bad Name value";
            $this->apiCode = 401;
        } else {
            if ($tableName == 'fundraiser') {
                $checkAlias = DB::table("$tableName")->where('alias', '=', $alias)->count();
            } else {
                $checkAlias = DB::table("$tableName")->where('alias', '=', $alias)->where('status', '!=', 2)->count();
            }
            if ($checkAlias > 0) {
                if ($id != 0) {
                    if ($tableName == 'fundraiser') {
                        $checkOtherAlias = DB::table("$tableName")->where('alias', '=', $alias)->where('id', '!=', $id)->count();
                    } else {
                        $checkOtherAlias = DB::table("$tableName")->where('alias', '=', $alias)->where('status', '!=', 2)->where('id', '!=', $id)->count();
                    }
                    if ($checkOtherAlias > 0) {
                        // $alias = $this->createUniqueAlias($tableName, $alias, 1, $id);
                        $this->apiMessage = "Name Already exists";
                        $this->apiCode = 201;
                    } else {
                        $this->apiData = $alias;
                        $this->apiMessage = "Name is ok";
                        $this->apiCode = 200;
                    }
                } else {
                    // $alias = $this->createUniqueAlias($tableName, $alias, 1, 0);
                    $this->apiMessage = "Name Already exists";
                    $this->apiCode = 201;
                }
            } else {
                $this->apiData = $alias;
                $this->apiMessage = "Name is ok";
                $this->apiCode = 200;
            }
        }
        $this->apiStatus = true;
        return $this->response();
    }

    /**
     * Create Unique Alias
     * @param Request $request
     * @return Object
     */
    public function createUniqueAlias($tableName, $alias, $i, $id) {
        $newalias = $alias . "-" . $i;
        if ($id != 0) {
            $checkOtherAlias = DB::table("$tableName")->where('alias', '=', $newalias)->where('id', '!=', $id)->count();
            if ($checkOtherAlias > 0) {
                return $this->createUniqueAlias($tableName, $alias, $i + 1, $id);
            } else {
                return $newalias;
            }
        } else {
            $checkAlias = DB::table("$tableName")->where('alias', '=', $newalias)->count();
            if ($checkAlias > 0) {
                return $this->createUniqueAlias($tableName, $alias, $i + 1, 0);
            } else {
                return $newalias;
            }
        }
    }

    public function getPageDetail(Request $request) {
        $page = DB::table("cms_pages")->where('alias', '=', $request->alias)->where('status', 1)
                ->first();
        if ($page !== null) {
            $pageData = [];
            $pageData['name'] = $page->name;
            $pageData['description'] = $page->description;
            $pageData['meta_title'] = $page->meta_title;
            $pageData['meta_description'] = $page->meta_description;
            $files = array();
            if ($page->image != '') {
                $files_name = explode(",", $page->image);
                foreach ($files_name as $file_name) {
                    $files[] = $this->getImageUrl($file_name, 'guides');
                }
            }
            $pageData['files'] = $files;

            $this->apiData = $pageData;
            $this->apiMessage = "Page detail is available";
            $this->apiCode = 200;
            $this->apiStatus = true;
        } else {
            $this->apiMessage = "No records found.";
        }
        return $this->response();
    }

    /**
     * Send mail to admin for apply for zakat form
     * @return type
     */
    public function applyForZakat(Request $request) {
        $data = $request->all();
        $about = $data['about'];
        $finance = $data['finance'];
        $situation = $data['situation'];
        $declaration = $data['declaration'];
        $admin = User::where('user_type', 0)->first();

        $memberArray = [
            'title' => $about['title'],
            'first_name' => $about['first_name'],
            'last_name' => $about['surname'],
            'dob' => Carbon::parse($about['dob']),
            'age' => $about['age'],
            'marital_status' => $about['marital_status'],
            'religion' => $about['religion'],
            'nationality' => $about['nationality'],
            'languages' => '',
            'lived_years' => $about['years_lived_in_uk'],
            'lived_address' => $about['address'],
            'lived_post_code' => $about['postcode'],
            'lived_county' => $about['county'],
            'living_property_years' =>
            $about['years_living_in_property'],
            'living_property_phone_number' => $about['phone_number'],
            'living_property_mobile_number' => $about['mobile_number'],
            'living_property_email' => $about['email'],
            'current_employement' => $about['currently_employed'],
            'own_property' => $about['own_property'],
            'type_of_accommodation' => $about['accomodation_type']
        ];

        for ($i = 0; $i < count($about['languages']); $i++) {
            $memberArray['languages'] .= $about['languages'][$i]['language_' .
                    ($i + 1)] . ',';
        }

        $memberArray['languages'] = str_replace_last(',', '', $memberArray['languages']);

        $member = Member::create($memberArray);

        $partnerArray = [
            'member_id' => $member->id,
            'title' => $about['title_for_partner'],
            'first_name' =>
            $about['first_name_for_partner'],
            'last_name' => $about['surname_for_partner'],
            'dob' =>
            Carbon::parse($about['dob_for_partner']),
            'age' => $about['age_for_partner'],
            'marital_status' =>
            $about['marital_status_for_partner'],
            'religion' => $about['religion_for_partner'],
            'nationality' =>
            $about['nationality_for_partner'],
            'languages' => '',
            'lived_years' =>
            $about['years_lived_in_uk_for_partner'],
            'lived_address' => $about['address_for_partner'],
            'lived_post_code' => $about['postcode_for_partner'],
            'lived_county' => $about['county_for_partner'],
            'living_property_years' =>
            $about['years_living_in_property_for_partner'],
            'living_property_phone_number' =>
            $about['phone_number_for_partner'],
            'living_property_mobile_number' =>
            $about['mobile_number_for_partner'],
            'living_property_email' => $about['email_for_partner'],
            'current_employement' =>
            $about['currently_employed_for_partner'],
            'own_property' =>
            $about['own_property_for_partner'],
            'type_of_accommodation' =>
            $about['accomodation_type_for_partner']
        ];

        for ($i = 0; $i < count($about['languages_for_partner']); $i++) {
            $partnerArray['languages'] .= $about['languages_for_partner'][$i]['language_' .
                    ($i + 1) . '_for_partner'] . ',';
        }

        $partnerArray['languages'] = str_replace_last(',', '', $partnerArray['languages']);

        $partner = Partner::create($partnerArray);

        if (!$partner) {
            $this->apiMessage = "Partner not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        for ($i = 0; $i < count($about['childs']); $i++) {
            $childrenArray = [
                'member_id' => $member->id,
                'first_name' =>
                $about['childs'][$i]['child_' . ($i + 1) . '_first_name'],
                'last_name' =>
                $about['childs'][$i]['child_' . ($i + 1) . '_surname'],
                'dob' =>
                Carbon::parse($about['childs'][$i]['child_' . ($i + 1) .
                        '_dob']),
                'age' =>
                $about['childs'][$i]['child_' . ($i + 1) . '_age'],
                'gender' =>
                $about['childs'][$i]['child_' . ($i + 1) . '_gender'],
                'marital_status' =>
                $about['childs'][$i]['child_' . ($i + 1) . '_marital_status'],
                'any_child_benefit' => $about['childs'][$i]['child_' . ($i +
                1) . '_benefits'],
                'benefit_description' =>
                $about['childs'][$i]['child_' . ($i + 1) .
                '_benefit_description']
            ];

            $children = Children::create($childrenArray);

            if (!$children) {
                $this->apiMessage = "Children not created!";
                $this->apiCode = 500;
                $this->apiStatus = false;
                return $this->response();
            }
        }

        for ($i = 0; $i < count($about['persons']); $i++) {
            $familyMemberArray = [
                'member_id' => $member->id,
                'first_name' => $about['persons'][$i]['person_' .
                ($i + 1) . '_first_name'],
                'last_name' => $about['persons'][$i]['person_' .
                ($i + 1) . '_surname'],
                'dob' =>
                Carbon::parse($about['persons'][$i]['person_' . ($i + 1) .
                        '_dob']),
                'age' => $about['persons'][$i]['person_' .
                ($i + 1) . '_age'],
                'gender' => $about['persons'][$i]['person_' .
                ($i + 1) . '_gender'],
                'marital_status' => $about['persons'][$i]['person_' .
                ($i + 1) . '_marital_status'],
                'relationship' => $about['persons'][$i]['person_' .
                ($i +
                1) . '_relationship'],
                'financially_responsible' => $about['persons'][$i]['person_' .
                ($i + 1) . '_financially_responsible'],
                'responsible_description' => $about['persons'][$i]['person_' .
                ($i + 1) . '_finance_description'],
                'income_benefit' => $about['persons'][$i]['person_' .
                ($i + 1) . '_get_income'],
                'benefit_description' => $about['persons'][$i]['person_' .
                ($i + 1) . '_income_description']
            ];

            $familyMember = FamilyMember::create($familyMemberArray);

            if (!$familyMember) {
                $this->apiMessage = "familyMember not created!";
                $this->apiCode = 500;
                $this->apiStatus = false;
                return $this->response();
            }
        }

        $referee1Array = [
            'member_id' => $member->id,
            'title' => $about['referee_1_title'],
            'first_name' => $about['referee_1_first_name'],
            'last_name' => $about['referee_1_surname'],
            'years_to_applicant' => $about['referee_1_known_years'],
            'relationship' => $about['referee_1_relation_to_applicant'],
            'address' => $about['referee_1_address'],
            'post_code' => $about['referee_1_postcode'],
            'county' => $about['referee_1_county'],
            'phone_number' => $about['referee_1_phone_number'],
            'mobile_number' => $about['referee_1_mobile_number'],
            'email' => $about['referee_1_email']
        ];

        $referee1 = Referee::create($referee1Array);

        if (!$referee1) {
            $this->apiMessage = "referee1 not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $referee2Array = [
            'member_id' => $member->id,
            'title' => $about['referee_2_title'],
            'first_name' => $about['referee_2_first_name'],
            'last_name' => $about['referee_2_surname'],
            'years_to_applicant' => $about['referee_2_known_years'],
            'relationship' => $about['referee_2_relation_to_applicant'],
            'address' => $about['referee_2_address'],
            'post_code' => $about['referee_2_postcode'],
            'county' => $about['referee_2_county'],
            'phone_number' => $about['referee_2_phone_number'],
            'mobile_number' => $about['referee_2_mobile_number'],
            'email' => $about['referee_2_email']
        ];

        $referee2 = Referee::create($referee2Array);

        if (!$referee2) {
            $this->apiMessage = "referee2 not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $grossIncomeArray = [
            'member_id' => $member->id,
            'salary' => $finance['salary'],
            'social_income' => $finance['ssi'],
            'food_stamp' => $finance['food_stamp'],
            'subsidized_housing' => $finance['public_housing'],
            'wic' => $finance['wic_programme'],
            'energy_assistance' =>
            $finance['energy_assistance_programme'],
            'scholarships' => $finance['scholarships'],
            'child_support' => $finance['child_support'],
            'medicare' => $finance['medicare'],
            'senior_services' => $finance['senior_services'],
            'alimony' => $finance['alimony'],
            'cash' => $finance['cash'],
            'unemployement' => $finance['unemployment'],
            'assistance_from_other' => $finance['other_aids'],
            'goverment_aid' => $finance['other_government_aid'],
            'other' => $finance['other']
        ];

        $grossIncome = MonthlyGrossIncome::create($grossIncomeArray);

        if (!$grossIncome) {
            $this->apiMessage = "grossIncome not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $expenseArray = [
            'member_id' => $member->id,
            'food' => $finance['food'],
            'rent' => $finance['rent'],
            'mortgage' => $finance['mortgage'],
            'utilities' => $finance['utilities'],
            'phone' => $finance['phone'],
            'others' => $finance['other_expenses']
        ];

        $expense = MonthlyExpense::create($expenseArray);

        if (!$expense) {
            $this->apiMessage = "expense not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $assetsArray = [
            'member_id' => $member->id,
            'car_book_value' => $finance['car_value'],
            'year' => $finance['year'],
            'model' => $finance['model'],
            'bank_account' => $finance['bank_account'],
            'house' => $finance['house'],
            'cash' => $finance['cash_assets'],
            'others' => $finance['other_assets']
        ];

        $assets = NetAsset::create($assetsArray);

        if (!$assets) {
            $this->apiMessage = "assets not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        $organisationsArray = [
            'member_id' => $member->id,
            'name' => $finance['organisation_name'],
            'case_number' => $finance['case_number'],
            'contact_person' => $finance['contact_person'],
            'contact_number' => $finance['contact_number']
        ];

        $organisations = OtherOrganisation::create($organisationsArray);

        if (!$organisations) {
            $this->apiMessage = "organisations not created!";
            $this->apiCode = 500;
            $this->apiStatus = false;
            return $this->response();
        }

        foreach ($situation as $key => $value) {
            $situationsArray = [
                'member_id' => $member->id,
                'question' => $key,
                'answer' => $value
            ];

            $situations = Situation::create($situationsArray);

            if (!$situations) {
                $this->apiMessage = "situations not created!";
                $this->apiCode = 500;
                $this->apiStatus = false;
                return $this->response();
            }
        }

        $pdf = PDF::loadView('apply-for-zakat.applyForZakatPdf', ['data' =>
                    $data]);
        try {
            Mail::send('apply-for-zakat.applyForZakatMail', $data, function
                    ($message) use ($pdf, $admin) {
                $message->to($admin->email)
                        ->subject('New Request For Zakat');
                $message->attachData($pdf->output(), "new-request-for-zakat.pdf");
            });
            if (count(Mail::failures()) == 0) {
                $this->apiMessage = "Mail sent!";
                $this->apiCode = 200;
                $this->apiStatus = true;
            } else {
                $this->apiMessage = "Mail not sent!";
                $this->apiCode = 500;
                $this->apiStatus = false;
            }
        } catch (Swift_RfcComplianceException $e) {
            $this->apiMessage = "Something went wrong. Please try again.";
            $this->apiCode = 500;
            $this->apiStatus = false;
        }
        return $this->response();
    }

    /**
     * Perform Signup
     * @param Request $request
     * @return object
     */
    public function helpForm(Request $request) {
        $rules = [
            'First_name' => 'required|min:3|max:30', // make sure
            'Surname' => 'required|min:3|max:30', // make sure
            'Phone_number' => 'required|min:3|max:15' // make sure
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $this->apiMessage = $validator->errors()->all();
            $this->apiCode = 500;
            $this->apiStatus = false;
        } else {
            // Send email to admin
            $admin = User::select('email')
                            ->where('user_type', 0)->first();
            $mailData = [];
            $mailData['data']['subject'] = config('constants.CLIENT.SITE_NAME') . ' Callback Enquiry';
            $mailData['data']['body'] = [
                'name' => $request->First_name . ' ' . $request->Surname,
                'number' => $request->Phone_number
            ];
            $mailData['data']['recipients'] = [
                    [
                    'email' => $admin->email
                ]
            ];
            $mailData['data']['name'] = $request->First_name . ' ' . $request
                    ->Surname;
            $mailData['template'] = 'emails.helpForm';
            $isMailSend = $this->mail($mailData);

            $this->apiData = $request;
            $this->apiMessage = "Mail Sent!";
            $this->apiCode = 200;
            $this->apiStatus = true;
        }
        return $this->response();
    }

}
