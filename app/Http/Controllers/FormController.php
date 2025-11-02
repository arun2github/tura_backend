<?php

namespace App\Http\Controllers;

use App\Exceptions\MunicipalBoardException;
use App\Models\FormEntityModel;
use App\Models\FormMasterTblModel;
use App\Models\TradeLicenseFee;
use App\Models\Forms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class FormController extends Controller
{
    /**
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForms() {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }

            $forms = Forms::select('id','name','status')->where('status','Active')->get();
            return response()->json([
                'status' => "success",
                'forms_list' => json_decode($forms,true),
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Something went worng in getForms API');
            return $exception->message();
        }
    }
    /**
     * Takes the POST request for Non Availability Certificate: Birth,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function nacBirth(Request $request) {
        try {
            Log::error('Something went worng in getForms API');
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'applicant_name' => 'required|string|between:2,50',
                'wardOrArea' => 'required|string|between:2,50',
                'locality' => 'required|string|between:2,50',
                'place_town_village' => 'required|string|between:2,50',
                'dist_state' => 'required|string|between:2,50',
                'postal_code' => 'required|string|min:6|max:10',
                'phone_no' => 'required|string|min:10|max:15',
                'email' => 'required|string|email|max:100',
                'name_of_nac' => 'required|string|between:2,50',
                'gender' => 'required|string',
                'dob' => 'required|date_format:Y-m-d',
                'time_of_birth' => 'required|date_format:H:i',
                'father_name' => 'required|string|between:2,50',
                'mother_name' => 'required|string|between:2,50',
                'place_of_birth' => 'required|string|between:2,50',
                'address_of_birth' => 'required|string|between:2,100',
                'upload_files' => 'required|array',
                // 'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:2048', // Validate each file
                'document_list' => 'required|array',
                'declaration' => 'required|string|between:2,500',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }
            Log::error('Something went worng in getForms API');


            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            
            $check_if_exists = FormMasterTblModel::where([
                'inserted_by' => $user->id,
                'form_id' => 1
            ])->first();
            
            if ($check_if_exists) {
                // Log a specific message for clarity instead of logging validator errors
                Log::error("NAC Form with form_id 1 already submitted by user_id: " . $user->id);
                
                return response()->json([
                    'status' => "failed",
                    'message' => 'NAC Form has already been submitted.'
                ], 400);
            }
            
            $app_id = "TMBNAC".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];
            
            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Nac Form Error');
            return $exception->message();
        }
    }
    
    /**
     * Takes the POST request for Complaint Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function complaintForm(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'complainants_name' => 'required|string|between:2,50',
                'father_name' => 'required|string|between:2,50',
                'mother_name' => 'required|string|between:2,50',
                'age' => 'required|string',
                'gender' => 'required|string',
                'current_address' => 'required|string|between:2,100',
                'permanant_address' => 'required|string|between:2,100',
                'phone_no' => 'required|string|min:10|max:15',
                'email' => 'required|string|email|max:100',
                'defendant_name' => 'required|string|between:2,50',
                'defendant_age' => 'required|string',
                'defendant_gender' => 'required|string',
                'defendant_address' => 'required|string|between:2,100',
                'relation' => 'string',
                'defendant_phone_no' => 'string|min:10|max:15',
                'defendant_email' => 'string|email|max:100',
                'is_defendant_know' => 'required',
                'is_first_incident' => 'required',
                'any_other_relevant_info' => 'string|between:2,100',
                'complaint_filed_by' => 'required|string',
                'date' => 'required|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }
            Log::error(json_encode($request->all()));
            

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "TMBCOM".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);
            
            $formData = [];
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            
            // Insert all form data at once
            FormEntityModel::insert($formData);

            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Complaint Form Error');
            return $exception->message();
        }
    }
    
    
      /**
     * Takes the POST request for Water Tanker Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function waterTankerForm(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'name' => 'required|string|between:2,50',
                'phone_no' => 'required|string|min:10|max:15',
                'house_no' => 'required|string|between:2,50',
                'streetOrLocality' => 'required|string|between:2,50',
                'landmark' => 'required|string|between:2,50',
                'district' => 'required|string|between:2,50',
                'date' => 'required|date_format:Y-m-d',
                'water_tanker_list' => 'required|array',
                'declaration' => 'required|string|between:2,500',
            ]);

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }
            Log::error(json_encode($request->all()));

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "TMBWTANKER".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);
            
             $formData = [];
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            
            // Insert all form data at once
            FormEntityModel::insert($formData);

            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Water Tanker Form Error');
            return $exception->message();
        }
    }
    
     /**
     * Takes the POST request for Cesspool Tanker Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cesspoolTanker(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'name' => 'required|string|between:2,50',
                'phone_no' => 'required|string|min:10|max:15',
                'house_no' => 'required|string|between:2,50',
                'streetOrLocality' => 'required|string|between:2,50',
                'landmark' => 'required|string|between:2,50',
                'district' => 'required|string|between:2,50',
                'date' => 'required|date_format:Y-m-d',
                'cesspoolTankerGeneral' => 'array|required_without_all:cesspoolTankerGoverment,cesspoolTankerOutsideMuni',
                'cesspoolTankerGoverment' => 'array|required_without_all:cesspoolTankerGeneral,cesspoolTankerOutsideMuni',
                'cesspoolTankerOutsideMuni' => 'array|required_without_all:cesspoolTankerGeneral,cesspoolTankerGoverment',
                'declaration' => 'required|string|between:2,500',
            ]);

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }
            Log::error(json_encode($request->all()));

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "TMBWCESSTANKER".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);
            
              $formData = [];
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            
            // Insert all form data at once
            FormEntityModel::insert($formData);

            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Water Tanker Form Error');
            return $exception->message();
        }
    }
    
     /**
     * Takes the POST request for Trade License Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tradeLicense(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'application_for_year' => 'required|string|between:2,50',
                'date_of_commencement_trade' => 'required|date_format:Y-m-d',
                'type_of_trade' => 'required|string|between:2,50',
                'market_name' => 'required|string|between:2,50',
                'nature_of_business' => 'required|string|between:2,50',
                'address_of_business' => 'required|string|between:2,300',
                'area' => 'required|string|between:2,50',
                'monthly_rent' => 'required|string|between:2,50',
                'weather_air_condition' => ['required', 'string', 'in:Yes,No'],
                'hp_of_motor' => ['required', 'string', 'in:Upto 10 H.P,Above 10 H.P'],
                'registration_no' => 'nullable|string|between:2,50',
                'name_of_properietor' => 'required|string|between:2,50',
                'dob' => 'required|date_format:Y-m-d',
                'caste' => 'required|string|between:2,50',
                'phone_no' => 'required|string|min:10|max:15',
                'father_or_mother_name' => 'required|string|between:2,50',
                'director_name' => 'required|string|between:2,50',
                'partner_name' => 'required|string|between:2,50',
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:50000 ',
                'document_list' => 'required|array',
                'declaration' => 'required|string|between:2,1000',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "TMBTRADE".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];

            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Trade licene Form Error');
            return $exception->message();
        }
    }
    
     /**
     * Takes the POST request for New Trade License Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function newTradeLicense(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'application_for_year' => 'required|string|between:2,50',
                'date_of_commencement_trade' => 'required|date_format:Y-m-d',
                'type_of_trade' => 'required|string|between:2,50',
                'market_name' => 'required|string|between:2,50',
                'nature_of_business' => 'required|string|between:2,50',
                'address_of_business' => 'required|string|between:2,300',
                'area' => 'required|string|between:2,50',
                'monthly_rent' => 'required|string|between:2,50',
                'weather_air_condition' => ['required', 'string', 'in:Yes,No'],
                'hp_of_motor' => ['required', 'string', 'in:Upto 10 H.P,Above 10 H.P'],
                'registration_no' => 'nullable|string|between:2,50',
                'name_of_properietor' => 'required|string|between:2,50',
                'dob' => 'required|date_format:Y-m-d',
                'caste' => 'required|string|between:2,50',
                'phone_no' => 'required|string|min:10|max:15',
                'father_or_mother_name' => 'required|string|between:2,50',
                'director_name' => 'required|string|between:2,50',
                'partner_name' => 'required|string|between:2,50',
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:50000 ',
                'document_list' => 'required|array',
                'declaration' => 'required|string|between:2,1000',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "TMBNEWTRADE".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];

            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Trade licene Form Error');
            return $exception->message();
        }
    }
    
     /**
     * Takes the POST request for New Trade License Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function nocTelORElc(Request $request) {
        Log::error(json_encode($request->all()));
        try {
            $validator = Validator::make($request->all(), [
                 'form_id' => 'required',
                'applicant_name' => 'required|string|between:2,50',
                'father_or_mother_name' => 'required|string|between:2,50',
                'street_address' => 'required|string|between:2,300',
                'house_number' => 'required|string|between:2,300',
                'town_or_village' => 'required|string|between:2,300',
                'district' => 'required|string|between:2,30',
                'zip_or_postal' => 'string|between:2,10',
                'phone_no' => 'string|min:10|max:15',
                'requirement' => ['required', 'string', 'in:Telephone Connection,Water Connection,Electricity Connection'],
                'where_required' => 'required|string|between:2,300',
                'weather_own_land' => ['required', 'string', 'in:Yes,No'],
                'building_type' => ['required', 'string', 'in:RCC,Semi-RCC,Assam Type with CGI/ Asbestos/ Thatch roofing,Other'],
                'usage_of_building' => ['required', 'string', 'in:Purely Residential,Purely Commercial,Mixed,Other'],
                'year_of_construction' => 'required|date_format:Y',
                'plinth_area' => 'required|string|between:2,300',
                'land_patta_no' => 'required|string|between:2,300',
                'dag_no' => 'required|string|between:2,300',
                'property_tax_paid' => ['required', 'string', 'in:Yes,No'],
                'building_permission' => 'required|string|between:2,300',
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:2048',
                'document_list' => 'required|array',
                'declaration' => 'required|string|between:2,1000',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "NOCTE".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];

            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error("Exception Trace: " . $exception->getTraceAsString());
            Log::error('NOC TEL OR ELC Form Error');
            return $exception->message();
        }
    }
    
      /**
     * Takes the POST request for New Trade License Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function nocEstablishment(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'full_name' => 'required|string|between:2,50',
                'gender' => ['required', 'string', 'in:Male,Female'],
                'dob' => 'required|date_format:Y-m-d',
                'address' => 'required|string|between:2,300',
                'town_or_village' => 'required|string|between:2,300',
                'district' => 'required|string|between:2,30',
                'zip_or_postal' => 'string|between:2,10',
                'phone_no' => 'string|min:10|max:15',
                'relation_name' => 'required|string|between:2,50',
                'relation_type' => 'required|string|between:2,50',
                'trade_for_which' => 'required|string|between:2,300',
                'locality_where_to_be' => 'required|string|between:2,300',
                'location_with_landmark' => 'required|string|between:2,300',
                'own_land' => ['required', 'string', 'in:Yes,No'],
                'building_type' => ['required', 'string', 'in:RCC,Semi-RCC,Assam Type with CGI Roofing'],
                'year_of_construction' => 'required|date_format:Y',
                'plinth_area' => 'required|string|between:2,300',
                'usage_of_building' =>  ['required', 'string', 'in:Purely Residential,Purely Commercial,Mixed,Other'],
                'monthly_rent' => 'required|string|between:2,50',
                'land_owner_name' => 'required|string|between:2,50',
                'land_patta_no' => 'required|string|between:2,300',
                'dag_no' => 'required|string|between:2,300',
                'weather_building_permission' => ['required', 'string', 'in:Yes,No'],
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:2048',
                'document_list' => 'required|array',
                'building_permission_date' => 'date_format:Y-m-d',
                'building_permission_no' => 'string',
                'weather_tax_paid' => 'required|string',
                'declaration' => 'required|string|between:2,1000',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "NOCEST".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];

            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('NOC EST Form Error');
            return $exception->message();
        }
    }
    
     /**
     * Takes the POST request for New Trade License Form,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bannerAndPoster(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'full_name' => 'required|string|between:2,50',
                'phone_no' => 'string|min:10|max:15',
                'address' => 'required|string|between:2,300',
                'town_or_village' => 'required|string|between:2,300',
                'district' => 'required|string|between:2,30',
                'zip_or_postal' => 'string|between:2,10',
                'no_of_banners' => 'string',
                'size_of_banner' => 'string',
                'requirementTypeOf' => 'string',
                'locality_where_it_set' => 'required|string|between:2,300',
                'declaration' => 'required|string|between:2,1000',
            ]);
            Log::error(json_encode($request->all()));

            if ($validator->fails()) {
                Log::error( $validator->errors()->toArray());
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $app_id = "BANPOST".date("Ymdhis");
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store($file_path, 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store($file_path, 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }

            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
                'application_id' => $app_id,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('NOC BANNER OR POSTER Form Error');
            return $exception->message();
        }
    }
    
    /**
     * Takes the POST request for Death Certificate,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deathCertificate(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'name' => 'required|string|between:2,50',
                'gender' => 'required|string',
                'dod' => 'required|date_format:Y-m-d',
                'father_name' => 'required|string|between:2,50',
                'mother_name' => 'required|string|between:2,50',
                'place_of_death' => 'required|string|between:2,50',
                'address_of_death' => 'required|string|between:2,100',
                'permenent_address_of_parent' => 'required|string|between:2,100',
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:2048', // Validate each file
                'declaration' => 'required|string|between:2,500',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => "TMBDEATH".date("Ymdhis"),
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            $file_path = 'uploads/' . date('Ymdhis');
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store('uploads/' . date('Ymdhis'), 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store('uploads/' . date('Ymdhis'), 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $file_path,
                'form_id' => $form_id
            ];
            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => "success",
                'message' => 'Form Submitted successfully',
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Nac Form Error');
            return $exception->message();
        }
    }
    
    /**
     * Takes the POST request for Birth Certificate,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function birthCertificate(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required',
                'name_of_child' => 'required|string|between:2,50',
                'gender' => ['required', 'string', 'in:male,female'],
                'dod' => 'required|date_format:Y-m-d',
                'father_name' => 'required|string|between:2,50',
                'mother_name' => 'required|string|between:2,50',
                'place_of_birth' => 'required|string|between:2,50',
                'current_address_of_parent' => 'required|string|between:2,100',
                'permenent_address_of_parent' => 'required|string|between:2,100',
                'upload_files.*' => 'required|file|mimes:jpeg,png,pdf|max:2048' // Validate each file
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            $form_data = [
                'form_id' => $request->form_id,
                'application_id' => "TMBBIRTH".date("Ymdhis"),
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            $formData = [];
            
            $file_path = 'uploads/' . date('Ymdhis');
            foreach($request->all() as $key => $req){
                if($key == "upload_files" || $key == "form_id"){
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $filePath = $file->store('uploads/' . date('Ymdhis'), 'public');
                                // Save the $filePath to the database as needed
                            }
                        } else {
                            // Handle the case where only a single file is uploaded
                            $filePath = $files->store('uploads/' . date('Ymdhis'), 'public');
                            // Save the $filePath to the database as needed
                        }
                    }
                }else{
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $file_path,
                'form_id' => $form_id
            ];
            // Insert all form data at once
            FormEntityModel::insert($formData);
            return response()->json([
                'status' => 'success',
                'message' => 'Form Submitted successfully',
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Nac Form Error');
            return $exception->message();
        }
    }
    
    //paginate
    public function paginateArray($items, $perPage = 10, $currentPage = 1) {
        $offset = ($currentPage - 1) * $perPage;
        $pagedItems = array_slice($items, $offset, $perPage);
        
        return $pagedItems;
    }
    
    /**
     * Takes the POST request for All Forms,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllForms(Request $request) {
        try {
            
            $validator = Validator::make($request->all(), [
                'stage' => ['required', 'in:ceo,employee,consumer'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
            
            if($request->stage == "ceo"){
                $column = "ceo_status";
            }else if($request->stage == "employee"){
                $column = "employee_status";
            }
            
            $page = request()->get('page', 1);  // Get the page number from the request, default to 1
            $limit = request()->get('limit', 10); // Get the limit (number of items per page), default to 10

            $forms = FormMasterTblModel::select('form_master_tbl.*', 'fe.*','f.name','P.status as payment_status','f.id as formNumber','form_master_tbl.id as formID')
                ->leftJoin('form_entity as fe', 'form_master_tbl.id', '=', 'fe.form_id')
                ->leftJoin('payment_details as P', 'form_master_tbl.application_id', '=', 'P.form_id')
                ->leftJoin('forms as f', 'form_master_tbl.form_id', '=', 'f.id');
            if($request->search != "" && $request->status != ""){
                if($request->stage == "consumer"){
                    $forms = $forms->where(['application_id'=>$request->search,'inserted_by' => $user->id]);
                }else{
                    $forms = $forms->where(['application_id'=>$request->search,$column=> $request->status]);
                }
            }else if($request->search != ""){
                if($request->stage == "ceo"){
                    $forms = $forms->where(['application_id'=>$request->search,'employee_status' => "Approved"]);
                }else if($request->stage == "employee"){
                    $forms = $forms->where(['application_id'=>$request->search]);
                }else{
                    $forms = $forms->where(['application_id'=>$request->search,'inserted_by' => $user->id,'ceo_status' => 'Approved']);
                }
            }else if($request->status != ""){
                if($request->stage == "ceo"){
                    $forms = $forms->where([$column=>$request->status,'employee_status'=>"Approved"]);
                }else{
                    $forms = $forms->where([$column=>$request->status]);
                }
            }else{
                if($request->stage == "ceo"){
                    $forms = $forms->where(['employee_status'=>"Approved"]);
                }else if($request->stage == "consumer"){
                    $forms = $forms->where(['inserted_by' => $user->id]);
                    // $forms = $forms->where([$column=>$request->status]);
                }
            }
            
            if($request->form_type != ""){
                $forms = $forms->where('form_master_tbl.form_id',$request->form_type);
            }
            $forms = $forms->orderBy('fe.id', 'desc')->get()
                ->toArray();
                // print_r(json_encode($forms));die();
            $formsArray = [];
            foreach ($forms as $form) {
                // Check if the form_id under the specific status already exists
                if (!isset($formsArray[$form['application_id']]['application_id'])) {
                    $formsArray[$form['application_id']]['application_id'] = $form['application_id'];
                    $formsArray[$form['application_id']]['application_submited_at'] = $form['inserted_at'];
                    $formsArray[$form['application_id']]['application_for'] = $form['name'];
                    $formsArray[$form['application_id']]['status'] = $request->stage == "consumer" ? $form['ceo_status'] : $form[$column];
                    $formsArray[$form['application_id']]['formNumber'] = $form['formNumber'];
                    $formsArray[$form['application_id']]['form_id'] = $form['formID'];
                    if($form['formNumber'] == 5 || $form['formNumber'] == 7 || $form['formNumber'] == 6 || $form['formNumber'] == 8 || $form['formNumber'] == 10){
                        $formsArray[$form['application_id']]['payment'] = "Yes";
                    }else{
                        $formsArray[$form['application_id']]['payment'] = "No";
                    }
                    $formsArray[$form['application_id']]['payment_status'] = isset($form['payment_status']) ? $form['payment_status'] : 'pending';
                }
                $formsArray[$form['application_id']]['form'][$form['parameter']] = ($form['parameter'] == "document_list" || $form['parameter'] == "water_tanker_list" || $form['parameter'] == "cesspoolTankerGeneral" || $form['parameter'] == "cesspoolTankerGoverment" || $form['parameter'] == "cesspoolTankerOutsideMuni") ? json_decode($form['value'],true) : $form['value'];
            }
            $newArray = [];
            foreach($formsArray as $formData){
                 $formData['form']['form_id'] = $formData['form_id'];
                $newArray[] = $formData;
            }
            
            $paginatedForms = $this->paginateArray($newArray, $limit, $page);
            
            $pendingCount = 0;
            $approvedCount = 0;
            $rejectedCount = 0;
            
            // Count Pending forms
            if($request->stage == "ceo"){
                $pendingCount = FormMasterTblModel::where([$column=>'Pending','employee_status'=>"Approved"])->count();
            }else if($request->stage == "employee"){
                 $pendingCount = FormMasterTblModel::where([$column=> 'Pending'])->count();
            }else if($request->stage == "employee"){
                $pendingCount = FormMasterTblModel::where(['employee_status'=> 'Pending'])->orWhere('ceo_status','Pending')->count();
            }
           
            if($request->stage != "consumer"){
                // Count Approved forms
                $approvedCount = FormMasterTblModel::where($column, 'Approved')->count();
        
                // Count Rejected forms
                $rejectedCount = FormMasterTblModel::where($column, 'Rejected')->count();
            }else{
                 // Count Approved forms
                $approvedCount = FormMasterTblModel::where(['employee_status'=> 'Approved','ceo_status'=>'Approved'])->count();
        
                // Count Rejected forms
                $rejectedCount = FormMasterTblModel::where('employee_status', 'Rejected')->orWhere('ceo_status','Rejected')->count();
            }

            return response()->json([
                'status' => "success",
                'message' => 'Form Data retrived successfully',
                'data' => $paginatedForms,
                'counts' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                ]
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Nac Form Error');
            return $exception->message();
        }
    }
    
    /**
     * Takes the POST request for All Forms,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFormsPercentageBasedOnDate(Request $request){
        try {
            // Validation for stage and dates
            $validator = Validator::make($request->all(), [
                'stage' => ['required', 'in:ceo,employee'],
                'from_date' => 'nullable|date_format:Y-m-d|before_or_equal:today', // Changed form_date to from_date
                'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date|before_or_equal:today',
            ]);
    
            // If validation fails, return errors
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }
    
            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }
    
            // Determine the status column based on the stage
            $column = $request->stage == 'ceo' ? 'ceo_status' : 'employee_status';
    
            // Initialize counts
            $totalCount = 0;
            $pendingCount = 0;
            $approvedCount = 0;
            $rejectedCount = 0;
    
            // Query for Pending forms based on stage and date range
            $pendingQuery = FormMasterTblModel::where($column, 'Pending');
    
            if ($request->stage == 'ceo') {
                $pendingQuery->where('employee_status', 'Approved');
            }
    
            if (!empty($request->form_date)) {
                if (!empty($request->to_date)) {
                    $pendingQuery->whereBetween('inserted_at', [$request->form_date, $request->to_date]);
                } else {
                    $pendingQuery->where('inserted_at', '>=', $request->form_date);
                }
            }
    
            // Get the pending form count
            $pendingCount = $pendingQuery->count();
    
            // Query for Approved forms based on date range
            $approvedQuery = FormMasterTblModel::where($column, 'Approved');
    
            if (!empty($request->form_date)) {
                if (!empty($request->to_date)) {
                    $approvedQuery->whereBetween('inserted_at', [$request->form_date, $request->to_date]);
                } else {
                    $approvedQuery->where('inserted_at', '>=', $request->form_date);
                }
            }
    
            // Get the approved form count
            $approvedCount = $approvedQuery->count();
    
            // Query for Rejected forms based on date range
            $rejectedQuery = FormMasterTblModel::where($column, 'Rejected');
    
            if (!empty($request->form_date)) {
                if (!empty($request->to_date)) {
                    $rejectedQuery->whereBetween('inserted_at', [$request->form_date, $request->to_date]);
                } else {
                    $rejectedQuery->where('inserted_at', '>=', $request->form_date);
                }
            }
    
            // Get the rejected form count
            $rejectedCount = $rejectedQuery->count();
    
            // Calculate total forms
            $totalCount = $pendingCount + $approvedCount + $rejectedCount;
            
            $pendingPercentage = $totalCount > 0 ? ($pendingCount / $totalCount) * 100 : 0;
            $approvedPercentage = $totalCount > 0 ? ($approvedCount / $totalCount) * 100 : 0;
            $rejectedPercentage = $totalCount > 0 ? ($rejectedCount / $totalCount) * 100 : 0;
    
            // Return response with form counts
            return response()->json([
                'status' => 'success',
                'message' => 'Form percentage.',
                'counts' => [
                    'total' => $totalCount,
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                ],
                'percentages' => [
                    'pending' => round($pendingPercentage, 2),
                    'approved' => round($approvedPercentage, 2),
                    'rejected' => round($rejectedPercentage, 2),
            ]
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Nac Form Error: ' . $exception->getMessage());
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], $exception->getCode());
        }
    }

    
      /**
     * Takes the POST request for Approve Forms,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function approvedOrRejectForm(Request $request){
        $validator = Validator::make($request->all(), [
            'form_id' => 'required',
            'application_id' => 'required',
            'approver' => ['required', 'in:ceo,employee'],
            'status' => ['required', 'in:Approved,Rejected'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = Auth::user();
        if (!$user) {
            throw new MunicipalBoardException("Invalid authorization token", 401);
        }

        $form = FormMasterTblModel::where([
            'application_id' => $request->application_id,
            'id' => $request->form_id
        ])->first();

        if (is_null($form)) {
            // Handle the case where the form is not found
            // For example, return an error response or redirect
            return response()->json([
                'status' => "failed",
                'message' => 'Form not found'
            ], 404);
        }

        // Update the status based on the approver
        if ($request->approver == "ceo") {
            $form->ceo_status = $request->status;
        } elseif ($request->approver == "employee") {
            $form->employee_status = $request->status;
        }

        // Save the updated form and check if the save was successful
        if ($form->save()) {
            // Handle successful save, e.g., return a success response
            return response()->json([
                'status' => "success",
                'message' => 'Form updated successfully'
            ]);
        } else {
            // Handle the case where the save fails
            return response()->json([
                'status' => "failed",
                'message' => 'Failed to update form'
            ], 500);
        }
    }
    
    /**
     * Download the specified file from storage.
     *
     * @param string $filename
     * @return BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadFile($folder)
    {
       // Define the folder path
        $folderPath = storage_path('app/public/uploads/' . $folder);

        // Check if the folder exists
        if (!is_dir($folderPath)) {
            return response()->json(['error' => 'Folder not found.'], 404);
        }

        // Create a ZIP file
        $zipFileName = $folder . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            // Add files to the ZIP archive
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderPath));

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
        } else {
            return response()->json(['error' => 'Failed to create ZIP file.'], 500);
        }

        // Return the ZIP file as a download
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
    
    /**
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tradeLicenseFee(Request $request) {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }

            if(isset($request->search) && $request->search != ""){
                $tradeLicenseFee = TradeLicenseFee::select('*')
                    ->where('status', 'Active')
                    ->where('trade_type', 'like', '%' . $request->search . '%')
                    ->get()
                    ->toArray();
            }else{
                $tradeLicenseFee = TradeLicenseFee::select('*')->where('status','Active')->get()->toArray();
            }
            return response()->json([
                'status' => "success",
                'message' => 'List of Trade License Fees',
                'data' => $tradeLicenseFee,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Something went worng in getForms API');
            return $exception->message();
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}