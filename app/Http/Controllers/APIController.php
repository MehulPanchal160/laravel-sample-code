<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Input;
use Validator;
use Redirect;
use Auth;
use DB;
use Hash;
use Session;
use File;
use Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class APIController extends Controller {

    protected $apiData = '';
    protected $apiCode = 500;
    protected $apiStatus = false;
    protected $apiMessage = '';

    function __construct() {
        $this->apiData = new \stdClass();
    }

    /**
     * Send JSON response
     * @return type
     */
    protected function response() {
        $response['status'] = $this->apiStatus;
        $response['code'] = $this->apiCode;
        $response['message'] = $this->apiMessage;
        $response['data'] = $this->apiData;

        return response()->json($response, 200, array(), JSON_NUMERIC_CHECK);
    }

    /**
     * Convert a string to desired ASCII format
     * @param String to br converted, Array of characters to be replaced, Delimiter to be used.
     * @return converted string.
     */
    protected function toAscii($str, $replace = array(), $delimiter = '-') {
        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    /**
     * Upload image with ASCII name conversion to LARAVEL/public/uploads
     * @param Request $request, Folder to upload image.
     * @return Uploladed image name.
     */
    protected function uploadImage(Request $request, $directory_name = '') {
        $current_time = Carbon::now()->timestamp;

        $image_name = $request->file('image')->getClientOriginalName();
        $extension = $request->file('image')->getClientOriginalExtension();

        $image_name = str_replace_last('.' . $extension, '', $image_name);

        $rename = $this->toAscii($image_name, array(), '_') . '_' . $current_time . '.' . $extension;

        $stored = $request->image->storeAs($directory_name, $rename, 'uploads');

        if ($stored == false) {
            return $stored;
        } else {
            return $rename;
        }
    }

    /**
     * Delete image from LARAVEL/public/uploads
     * @param Image Name, Folder to delete image from.
     */
    protected function deleteImage($imagename, $directory_name = '') {
        if ($imagename != '' && $imagename != null) {
            $exists = Storage::disk('uploads')->exists($directory_name . '/' . $imagename);
            if ($exists) {
                Storage::disk('uploads')->delete($directory_name . '/' . $imagename);
            }
        }
    }

    /**
     * Get image with full path name
     * @param Image Name, Folder to find image from.
     * @return Image name with full path.
     */
    protected function getImageUrl($imagename, $directory_name = '') {
        if ($imagename != '' && $imagename != null) {
            $exists = Storage::disk('uploads')->exists($directory_name . '/' . $imagename);
            if ($exists) {
                $imageUrl = Storage::disk('uploads')->url($directory_name . '/' . $imagename);
            } else if ($directory_name == 'profile_photos') {
                $imageUrl = Storage::disk('uploads')->url('default-user-avatar.jpg');
            } else if ($directory_name == 'fundraiser_images') {
                $imageUrl = Storage::disk('uploads')->url('default-fundraiser-banner.jpg');
            } else {
                $imageUrl = Storage::disk('uploads')->url('no-image.png');
            }
        } else if ($directory_name == 'profile_photos') {
            $imageUrl = Storage::disk('uploads')->url('default-user-avatar.jpg');
        } else if ($directory_name == 'fundraiser_images') {
            $imageUrl = Storage::disk('uploads')->url('default-fundraiser-banner.jpg');
        } else {
            $imageUrl = Storage::disk('uploads')->url('no-image.png');
        }
        return $imageUrl;
    }

    /**
     * Send Mail
     * @param Data to be sent.
     * @return true/false - Mail sent or not.
     */
    protected function mail($mailData = array()) {
        if (!empty($mailData['data']['subject']) && !empty($mailData['data']['recipients']) && $mailData['template']) {
            $data = $mailData['data'];

            try {
                Mail::send(array(
                    'html' => $mailData['template']
                        ), $data['body'], function($message) use ($data) {
                    // Subject
                    $message->subject($data['subject']);

                    // Recipients
                    foreach ($data['recipients'] as $recipient) {
                        if (!empty($recipient['fullname'])) {
                            $message->to($recipient['email'], $recipient['fullname']);
                        } else {
                            $message->to($recipient['email']);
                        }
                    }

                    // Attachments
                    if (!empty($data['attachments'])) {
                        foreach ($data['attachments'] as $attachment) {
                            $message->attach($attachment);
                        }
                    }
                });
                if (count(Mail::failures()) == 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (Swift_RfcComplianceException $e) {
                return false;
            }
        } else {
            return false;
        }
    }
}
