<?php

namespace App\Http\Controllers\admin;

use App\Secteur;
use App\Specialite;
use App\VisiteMedical;
use App\User;
use App\VisitePharmacie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VisiteController extends Controller
{
    function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:ADMIN|SUPADMIN');
    }

    public function index_med(){
        $users = User::whereHas('role',function (Builder $query){
            $query->where('libelle','!=','ADMIN')
                ->where('libelle','!=','SUPADMIN');
        })->get();

        $secteurs = Secteur::orderBy('libelle')->get();
        $specialites = Specialite::orderBy('libelle')->get();

        return view('admin.visites.medicals.medvisites_filter',compact('users','secteurs','specialites'));
    }

    public function visitesMed(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'dt_d'=>'required|date_format:d/m/Y',
            'dt_f' => 'required|date_format:d/m/Y|after_or_equal:dt_d',
            'etat.*'=>'nullable|in:plan,réalisé,réalisé hors plan,reporté',
            'user.*'=>'nullable|exists:users,user_id',
            'spec.*'=>'nullable|exists:specialites,specialite_id',
            'sect.*'=>'nullable|exists:secteurs,secteur_id',

        ],
            [
                'dt_d.*'=>'Date début invalide',
                'dt_f.*'=>'Date fin invalide',
                'etat.*'=>'Etat des visites invalide.',
                'spec.*'=>'specialités choisis invalides.',
                'sect.*'=>'secteurs choisis invalid',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        else{

            $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('dt_d'))->format('Y-m-d');
            $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('dt_f'))->format('Y-m-d');
            $etats = $request->input('etat');
            $users = $request->input('user');
            $spec = $request->input('spec');
            $sect = $request->input('sect');

            $visites = VisiteMedical::when($date_debut,function($query) use ($date_debut){
                                    $query->where('date_visite','>=',$date_debut);
                             })->when($date_fin,function($query) use ($date_fin){
                                $query->where('date_visite','<=',$date_fin);
                             })->when($etats,function($query) use ($etats){
                                $query->whereIn('etat',$etats);
                             })->when($users,function($query) use ($users){
                                $query->whereIn('user_id',$users);
                            });

            if(!empty($spec)){
                $visites = $visites->whereHas('medecin',function ($query) use ($spec){
                    $query->whereIn('specialite_id',$spec);
                });
            }

            if(!empty($sect)){
                $visites = $visites->whereHas('user.ville',function ($query) use ($sect){
                    $query->whereIn('secteur_id',$sect);
                });
            }

            $visites = $visites->paginate(30);
            return view('admin.visites.medicals.medvisites_results',compact('visites'));

        }
    }

    public function show_visite_med($id){
        $visite = VisiteMedical::findOrfail($id);

        return view('admin.visites.medicals.medvisite_show',compact('visite'));
    }

    public function index_ph(){
        $users = User::whereHas('role',function (Builder $query){
            $query->where('libelle','!=','ADMIN')
                ->where('libelle','!=','SUPADMIN');
        })->get();

        $secteurs = Secteur::orderBy('libelle')->get();

        return view('admin.visites.pharma.phvisites_filter',compact('users','secteurs','specialites'));
    }

    public function visitesPh(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'dt_d'=>'required|date_format:d/m/Y',
            'dt_f' => 'required|date_format:d/m/Y|after_or_equal:dt_d',
            'etat.*'=>'nullable|in:plan,réalisé,réalisé hors plan,reporté',
            'user.*'=>'nullable|exists:users,user_id',
            'sect.*'=>'nullable|exists:secteurs,secteur_id',
            ],
            [
                'dt_d.*'=>'Date début invalide',
                'dt_f.*'=>'Date fin invalide',
                'etat.*'=>'Etat des visites invalide.',
                'sect.*'=>'secteurs choisis invalid',
            ]
        );

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        else{

            $date_debut = Carbon::createFromFormat('d/m/Y',$request->input('dt_d'))->format('Y-m-d');
            $date_fin =  Carbon::createFromFormat('d/m/Y',$request->input('dt_f'))->format('Y-m-d');
            $etats = $request->input('etat');
            $users = $request->input('user');
            $sect = $request->input('sect');

            $visites = VisitePharmacie::when($date_debut,function($query) use ($date_debut){
                $query->where('date_visite','>=',$date_debut);
            })->when($date_fin,function($query) use ($date_fin){
                $query->where('date_visite','<=',$date_fin);
            })->when($etats,function($query) use ($etats){
                $query->whereIn('etat',$etats);
            })->when($users,function($query) use ($users){
                $query->whereIn('user_id',$users);
            });

            if(!empty($sect)){
                $visites = $visites->whereHas('user.ville',function ($query) use ($sect){
                    $query->whereIn('secteur_id',$sect);
                });
            }

            $visites = $visites->paginate(30);

            return view('admin.visites.pharma.phvisites_results',compact('visites'));

        }
    }

    public function show_visite_ph($id){
        $visite = VisitePharmacie::findOrfail($id);

        return view('admin.visites.pharma.phvisites_show',compact('visite'));
    }
}
