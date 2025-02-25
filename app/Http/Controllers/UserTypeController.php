<?php

namespace App\Http\Controllers;

use App\Models\UserType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserTypeController extends Controller
{
  public function index(){
    return view('content.usertypes.list');
  }

  public function create(Request $request){
    $validator = Validator::make($request->all(), [
      'name_ar' => 'required|string',
      'name_en' => 'required|string',
      'description_ar' => 'sometimes|string',
      'description_en' => 'sometimes|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status'=> 0,
        'message' => $validator->errors()->first()
      ]);
    }
    try{


      $userType = UserType::create($request->all());

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => $userType
      ]);

    }catch(Exception $e){
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]
    );
    }
  }

  public function update(Request $request){

    $validator = Validator::make($request->all(), [
      'user_type_id' => 'required',
      'name_ar' => 'sometimes|string',
      'name_en' => 'sometimes|string',
      'description_ar' => 'sometimes|string',
      'description_en' => 'sometimes|string',
    ]);

    if ($validator->fails()){
      return response()->json([
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try{

      $userType = UserType::findOrFail($request->user_type_id);

      $userType->update($request->except('user_type_id'));

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => $userType
      ]);

    }catch(Exception $e){
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]
    );
    }

  }

  public function delete(Request $request){

    $validator = Validator::make($request->all(), [
      'user_type_id' => 'required',
    ]);

    if ($validator->fails()){
      return response()->json([
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try{

      $userType = UserType::findOrFail($request->user_type_id);

      $userType->prices()->delete();

      $userType->delete();

      return response()->json([
        'status' => 1,
        'message' => 'success',
      ]);

    }catch(Exception $e){
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]
    );
    }

  }

  public function restore(Request $request){

    $validator = Validator::make($request->all(), [
      'user_type_id' => 'required',
    ]);

    if ($validator->fails()){
      return response()->json([
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try{

      $userType = UserType::withTrashed()->findOrFail($request->user_type_id);

      $userType->restore();

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => $userType
      ]);

    }catch(Exception $e){
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]
    );
    }

  }

  public function get(Request $request){  //paginated
    $validator = Validator::make($request->all(), [
      'search' => 'sometimes|string',
    ]);

    if ($validator->fails()){
      return response()->json([
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try{

    $userTypes = UserType::orderBy('created_at','DESC');

    if($request->has('search')){

      $userTypes = $userTypes->where('name_ar', 'like', '%' . $request->search . '%')
                    ->orwhere('name_en', 'like', '%' . $request->search . '%');
    }

    $userType = $userTypes->get();

    //return($units);

    return response()->json([
      'status' => 1,
      'message' => 'success',
      'data' => $userTypes
    ]);

  }catch(Exception $e){
    return response()->json([
      'status' => 0,
      'message' => $e->getMessage()
    ]
  );
  }

  }
}


