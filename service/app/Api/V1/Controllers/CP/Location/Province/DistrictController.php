<?php

namespace App\Api\V1\Controllers\CP\Location\Province;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\CamCyber\FileUpload;
use App\Api\V1\Controllers\ApiController;
use App\Model\Location\District as Main;

use App\Model\User\Main as User;
use Dingo\Api\Routing\Helpers;
use JWTAuth;


class DistrictController extends ApiController
{
    use Helpers;
    function list($id = 0){
       

        $data = Main::select('*')
        ->with(array('province'=>function($query){
            $query->select('id', 'name','en_name', 'code')->orderBy('name', 'asc');
        }))
        ->where(['province_id'=> $id])
        ->withCount([
                'communes as n_of_communes',
                'villages as n_of_villages'
                ]);
        $limit      =   intval(isset($_GET['limit'])?$_GET['limit']:100); 
        $key        =   isset($_GET['key'])?$_GET['key']:"";
        
        if( $key != "" ){
            $data = $data->where('name', 'like', '%'.$key.'%')->orWhere('code', 'like', '%'.$key.'%');
           
        }
        $from = isset($_GET['from'])?$_GET['from']:"";
        $to   = isset($_GET['to'])?$_GET['to']:"";
        if(isValidDate($from)){
            if(isValidDate($to)){
                $appends['from'] = $from;
                $appends['to'] = $to;
                $from .=" 00:00:00";
                $to .=" 23:59:59";
                $data = $data->whereBetween('created_at', [$from, $to]);
            }
        }

        $data= $data->orderBy('name', 'asc')->paginate(100);
        return response()->json($data, 200);
    }

    function view($id = 0){
        if($id!=0){
            $data = Main::select('*')->findOrFail($id);
            if($data){
                return response()->json(['data'=>$data], 200);
            }else{
                return response()->json(['status_code'=>404], 404);
            }
        }
    }
}
