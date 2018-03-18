<?php

namespace Bantenprov\PerijinanSatuPintu\Http\Controllers;

/* Require */
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Bantenprov\BudgetAbsorption\Facades\PerijinanSatuPintuFacade;

/* Models */
use Bantenprov\PerijinanSatuPintu\Models\Bantenprov\PerijinanSatuPintu\PerijinanSatuPintu;
use Bantenprov\GroupEgovernment\Models\Bantenprov\GroupEgovernment\GroupEgovernment;
use App\User;

/* Etc */
use Validator;

/**
 * The PerijinanSatuPintuController class.
 *
 * @package Bantenprov\PerijinanSatuPintu
 * @author  bantenprov <developer.bantenprov@gmail.com>
 */
class PerijinanSatuPintuController extends Controller
{  
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $group_egovernmentModel;
    protected $perijinan_satu_pintu;
    protected $user;

    public function __construct(PerijinanSatuPintu $perijinan_satu_pintu, GroupEgovernment $group_egovernment, User $user)
    {
        $this->perijinan_satu_pintu      = $perijinan_satu_pintu;
        $this->group_egovernmentModel    = $group_egovernment;
        $this->user             = $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->has('sort')) {
            list($sortCol, $sortDir) = explode('|', request()->sort);

            $query = $this->perijinan_satu_pintu->orderBy($sortCol, $sortDir);
        } else {
            $query = $this->perijinan_satu_pintu->orderBy('id', 'asc');
        }

        if ($request->exists('filter')) {
            $query->where(function($q) use($request) {
                $value = "%{$request->filter}%";
                $q->where('label', 'like', $value)
                    ->orWhere('description', 'like', $value);
            });
        }

        $perPage = request()->has('per_page') ? (int) request()->per_page : null;
        $response = $query->paginate($perPage);

        foreach($response as $group_egovernment){
            array_set($response->data, 'group_egovernment', $group_egovernment->group_egovernment->label);
        }

        foreach($response as $user){
            array_set($response->data, 'user', $user->user->name);
        }

        return response()->json($response)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $group_egovernment = $this->group_egovernmentModel->all();
        $users = $this->user->all();

        foreach($users as $user){
            array_set($user, 'label', $user->name);
        }

        $response['group_egovernment'] = $group_egovernment;
        $response['user'] = $users;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PerijinanSatuPintu  $perijinan_satu_pintu
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $perijinan_satu_pintu = $this->perijinan_satu_pintu;

        $validator = Validator::make($request->all(), [
            'group_egovernment_id' => 'required',
            'user_id' => 'required',
            'label' => 'required|max:16|unique:perijinan_satu_pintus,label',
            'description' => 'max:255',
        ]);

        if($validator->fails()){
            $check = $perijinan_satu_pintu->where('label',$request->label)->whereNull('deleted_at')->count();

            if ($check > 0) {
                $response['message'] = 'Failed, label ' . $request->label . ' already exists';
            } else {
                $perijinan_satu_pintu->group_egovernment_id = $request->input('group_egovernment_id');
                $perijinan_satu_pintu->user_id = $request->input('user_id');
                $perijinan_satu_pintu->label = $request->input('label');
                $perijinan_satu_pintu->description = $request->input('description');
                $perijinan_satu_pintu->save();

                $response['message'] = 'success';
            }
        } else {
            $perijinan_satu_pintu->group_egovernment_id = $request->input('group_egovernment_id');
            $perijinan_satu_pintu->user_id = $request->input('user_id');
            $perijinan_satu_pintu->label = $request->input('label');
            $perijinan_satu_pintu->description = $request->input('description');
            $perijinan_satu_pintu->save();
            $response['message'] = 'success';
        }

        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $perijinan_satu_pintu = $this->perijinan_satu_pintu->findOrFail($id);

        $response['perijinan_satu_pintu'] = $perijinan_satu_pintu;
        $response['group_egovernment'] = $perijinan_satu_pintu->group_egovernment;
        $response['user'] = $perijinan_satu_pintu->user;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PerijinanSatuPintu  $perijinan_satu_pintu
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $perijinan_satu_pintu = $this->perijinan_satu_pintu->findOrFail($id);

        array_set($perijinan_satu_pintu->user, 'label', $perijinan_satu_pintu->user->name);

        $response['perijinan_satu_pintu'] = $perijinan_satu_pintu;
        $response['group_egovernment'] = $perijinan_satu_pintu->group_egovernment;
        $response['user'] = $perijinan_satu_pintu->user;
        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PerijinanSatuPintu  $perijinan_satu_pintu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $perijinan_satu_pintu = $this->perijinan_satu_pintu->findOrFail($id);

        if ($request->input('old_label') == $request->input('label'))
        {
            $validator = Validator::make($request->all(), [
                'label' => 'required|max:16',
                'description' => 'max:255',
                'group_egovernment_id' => 'required',
                'user_id' => 'required',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'label' => 'required|max:16|unique:perijinan_satu_pintus,label',
                'description' => 'max:255',
                'group_egovernment_id' => 'required',
                'user_id' => 'required',
            ]);
        }

        if ($validator->fails()) {
            $check = $perijinan_satu_pintu->where('label',$request->label)->whereNull('deleted_at')->count();

            if ($check > 0) {
                $response['message'] = 'Failed, label ' . $request->label . ' already exists';
            } else {
                $perijinan_satu_pintu->label = $request->input('label');
                $perijinan_satu_pintu->description = $request->input('description');
                $perijinan_satu_pintu->group_egovernment_id = $request->input('group_egovernment_id');
                $perijinan_satu_pintu->user_id = $request->input('user_id');
                $perijinan_satu_pintu->save();

                $response['message'] = 'success';
            }
        } else {
            $perijinan_satu_pintu->label = $request->input('label');
            $perijinan_satu_pintu->description = $request->input('description');
            $perijinan_satu_pintu->group_egovernment_id = $request->input('group_egovernment_id');
            $perijinan_satu_pintu->user_id = $request->input('user_id');
            $perijinan_satu_pintu->save();

            $response['message'] = 'success';
        }

        $response['status'] = true;

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PerijinanSatuPintu  $perijinan_satu_pintu
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $perijinan_satu_pintu = $this->perijinan_satu_pintu->findOrFail($id);

        if ($perijinan_satu_pintu->delete()) {
            $response['status'] = true;
        } else {
            $response['status'] = false;
        }

        return json_encode($response);
    }
}
