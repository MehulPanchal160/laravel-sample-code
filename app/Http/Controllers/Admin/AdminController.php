<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\APIController;
use App\SiteConstant;
use Auth;
use Illuminate\Http\Request;

class AdminController extends APIController
{

    public function __construct()
    {
        $this->auth = auth();
    }

    public function checkLogin()
    {
        $user = Auth::user();
        if (empty($user)) {
            $this->apiMessage = "Please Login first!";
            $this->apiCode    = 500;
            $this->apiStatus  = false;
        } else {
            $this->apiData   = $user;
            $this->apiCode   = 200;
            $this->apiStatus = true;
        }

        return $this->response();
    }

    protected function getUser()
    {
        $user = Auth::user();
        return $user;
    }

    public function ramadanDate(Request $request)
    {
        $user = Auth::user();
        if (empty($user)) {
            $this->apiMessage = "Please Login first!";
            $this->apiCode    = 500;
            $this->apiStatus  = false;
        } else {
            if ($request->ramadanDate) {
                $setting = SiteConstant::where('key',
                    'RAMADAN_DATE')->first();
                $setting->value      = $request->ramadanDate;
                $setting->updated_by = $user->id;
                if ($setting->save()) {
                    $this->apiMessage = "Ramadan Date successfully updated.";
                    $this->apiCode    = 200;
                    $this->apiStatus  = true;
                } else {
                    $this->apiMessage = "Ramadan Date not updated!";
                    $this->apiCode    = 500;
                    $this->apiStatus  = false;
                }
            } else {
                $this->apiMessage = "Ramadan Date not sent!";
                $this->apiCode    = 500;
                $this->apiStatus  = false;
            }
        }
        return $this->response();
    }

}
