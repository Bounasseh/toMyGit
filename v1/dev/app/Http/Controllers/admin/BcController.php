<?php

namespace App\Http\Controllers\admin;
use App\Bc;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BcController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:ADMIN|SUPADMIN');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->has(['dt_d','dt_f'])){
            $validator = $this->validate_search($request);
            if($validator->fails()){
                return redirect()->back()->withErrors($validator);
            }
            $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('dt_d'))->format('Y-m-d');
            $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('dt_f'))->format('Y-m-d');
            $user = explode("_", $request->input('user'));
            $bcs = Bc::when($date_debut,function($query) use ($date_debut){
                $query->where('date_demande','>=',$date_debut);
            })->when($date_fin,function($query) use ($date_fin){
                $query->where('date_demande','<=',$date_fin);
            })->when($user,function($query) use ($user){
                $query->where('user_id',$user[0]);
            });
        }else{
            $bcs = Bc::orderBy('date_demande','desc');
        }

        $bcs = $bcs->paginate(10);

        $users = User::whereHas('role',function (Builder $query){
            $query->where('libelle','!=','ADMIN')
                ->where('libelle','!=','SUPADMIN');
        })->get();

        

        return view('admin.bcs.bcs_index',compact(['bcs','users', 'medecins']));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $bc = Bc::findOrfail($id);
        return view('admin.bcs.bc_show',compact('bc'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $bc = Bc::findOrfail($id);

        if($bc->etat != 'en cours') {
            return redirect()->back()->withErrors(['Error' => 'Vous ne pouvez pas modifier ce business case']);
        }
        $validator = Validator::make($request->all(),[
            'etat'=>'nullable|in:validé,non validé,réalisé',
            'engagement_bc'=>'nullable|in:faible,moyen,elevé',
            'satisfaction_bc'=>'nullable|in:faible,moyenne,élevée',
        ],[
            'etat.*'=>'l\'etat est invalide',
            'engagement_bc.*'=> 'Engagement choisi invalid.',
            'satisfaction_bc'=>'Satisfaction choisi invalide.',
        ]);

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        if($request->input('etat') != null)  {
            $bc->etat = $request->input('etat');
            if($bc->etat === "validé") $bc->validated_by = Auth::user()->nom . ' ' .  Auth::user()->prenom;
        }
        $bc->engagement = $request->input('engagement_bc');
        $bc->satisfaction = $request->input('satisfaction_bc');
        $bc->save();
        return redirect()->route('admin.bc.show',['id'=>$bc->bc_id])->with('status','Vos modifications sont enregistrées avec succès.');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function validate_search(Request $request){
        $validator = Validator::make($request->all(),[
            'dt_d'=>'required|date_format:d/m/Y',
            'dt_f' => 'required|date_format:d/m/Y|after_or_equal:dt_d',
            'user'=>'nullable|exists:users,user_id',
        ],
            [
                'dt_d.*'=>'Date début invalide',
                'dt_f.*'=>'Date fin invalide',
                'user.*'=>'Délégué choisi invalid',
            ]
        );
        return $validator;
    }
}