<?php

namespace App\Api\V1\Controllers\CP\Road;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\CamCyber\FileUpload;
use App\Api\V1\Controllers\ApiController;
use App\Model\Authority\Ministry\Road as Main;
use App\Model\Road\PK as RoadPK;
use App\Model\Road\Main as Road;
use Dingo\Api\Routing\Helpers;
use JWTAuth;


class MinistryController extends ApiController
{
    use Helpers;
    function list($roadId=0){
        

        $data   =   Main::select('id', 'ministry_id', 'description', 'start_pk', 'end_pk', 'created_at')->where('road_id', $roadId)
                    ->with(['ministry:id,abbre']); 

        $fromPK      =   intval(isset($_GET['fromPK'])?$_GET['fromPK']:0);
        $toPK        =   intval(isset($_GET['toPK'])?$_GET['toPK']:0);


        if( $fromPK != 0 && $toPK == 0 ){
            $data = $data->where('start_pk', '<=', $fromPK);
        }else  if( $fromPK == "" && $toPK != "" ){
            $data = $data->where('start_pk', '<=', $toPK)->where('end_pk', '<=', $toPK); 
        }else if( $fromPK != 0 && $toPK != 0){
            $range = [$fromPK, $toPK]; 
            $data = $data->whereBetween('start_pk', $range)->orWhereBetween('end_pk', $range); 
        }

        $ministry      =   intval(isset($_GET['ministry'])?$_GET['ministry']:0);
        if($ministry != 0){
            $data = $data->where('ministry_id', $ministry); 
        } 

        $limit      =   intval(isset($_GET['limit'])?$_GET['limit']:10); 
        $data= $data->orderBy('start_pk', 'asc')->paginate($limit);

        return response()->json($data, 200);
    }

    function post(Request $request, $roadId=0){
        $this->validate($request, [
            'fromPK'   => 'required|numeric',
            'toPK'     => 'required|numeric',
            'ministry' => 'required|numeric|exists:ministry,id',
        ]);

        
        $fromPK         = intval($request->input('fromPK')); 
        $toPK           = intval($request->input('toPK')); 
        $ministry       = intval($request->input('ministry')); 

        if( $fromPK <= $toPK ){

            //Check if submitted ended PK is in the range. 
            $pk      = RoadPK::select('id', 'code')->where('road_id', $roadId)->orderBy('code', 'desc')->first(); 
            if($pk){
                if($pk->code >= $fromPK){
                    if($pk->code >= $toPK){
                        $range = [$fromPK, $toPK]; 
                        //Check if this ministry has redundancy pk range
                        $roadMinistry = Main::select('id', 'start_pk', 'end_pk', 'ministry_id')->with(['ministry:id,abbre'])->where('road_id', $roadId)->where(function($query) use ($range){
                            $query->whereBetween('start_pk', $range)->orWhereBetween('end_pk', $range); 
                        })->first(); 

                        if(!$roadMinistry){
                           
                            $data               = new Main;
                            $data->ministry_id  = $ministry; 
                            $data->road_id      = $roadId; 
                            $data->start_pk     = $request->input('fromPK');
                            $data->end_pk       = $request->input('toPK');
                            $data->updated_at   = now();
                            $data->created_at   = now(); 
                            $data->save();
                            
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Data has been added!', 
                                'data' => $data
                            ], 200);

                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Sorry! Redundancy pk range is found.',
                                'data' => $roadMinistry
                            ], 200);
                        }

                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Sorry! To PK must be smaller or equal to '.$pk->code
                        ], 200);
                    } 
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Sorry! From PK must be bigger or equal to '.$pk->code
                    ], 200);
                }  
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sorry! PK range is not aviable. 1'
                ], 200);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'From PK must be smaller than To pK'
            ], 200);
        }
           
            
    }

    function delete( $roadId = 0, $id = 0 ){
        $data = Main::where('road_id', $roadId)->find($id);
        if(!$data){
            return response()->json([
                'message' => 'រកមិនឃើញទិន្នន័យ', 
            ], 404);
        }
        $data->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'ទិន្នន័យត្រូវបានលុប!',
        ], 200);
    }

  
}
