<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RapportMed;
use App\RapportPh;
use DateTime;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use App\Ville;
use App\User;
use App\Role;

class Export_File extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:SUPADMIN|ADMIN');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function View_Exporte_Synthese_Journalier()
    {
        $ListJours = [];
        $AddDay = -10;

        $RapportMed = RapportMed::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportMed as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }

        $RapportPh = RapportPh::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportPh as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }
        // dd($ListJours);
        return view('admin.ExportFile.Exporte_Synthese_Journalier', compact('ListJours'));
    }

    public function View_Exporte_Synthese_Hebdomadaire_DM()
    {
        $ListJours = [];
        $AddDay = -10;

        $RapportMed = RapportMed::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportMed as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }

        $RapportPh = RapportPh::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportPh as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }
        // dd($ListJours);
        return view('admin.ExportFile.Exporte_Synthese_Hebdomadaire_DM', compact('ListJours'));
    }
    
    public function View_Exporte_Synthese_Hebdomadaire_DPH()
    {
        $ListJours = [];
        $AddDay = -10;

        $RapportMed = RapportMed::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportMed as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }

        $RapportPh = RapportPh::select('date_de_visite')->where('date_de_visite', '>', (now()->addDay($AddDay))->format('Y-m-d'))->groupBy('date_de_visite')->orderBy('date_de_visite', 'desc')->get()->toArray();
        foreach ($RapportPh as $Key => $Value) {
            foreach ($Value as $key => $value) {
                $ListJours[(new DateTime($value))->format('Ymd')] = (new DateTime($value))->format('d F Y');
            }
        }
        // dd($ListJours);
        return view('admin.ExportFile.Exporte_Synthese_Hebdomadaire_DPH', compact('ListJours'));
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
        //
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
        //
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

    public function export(Request $request)
    {
        if($request->TypeExport == 'Exporte_Synthese_Journalier')
        {
            foreach ($request->mois as $Value) {
                $IdNomDelegue = [];
                $Date = \Carbon\Carbon::createFromFormat('Ymd', $Value)->format('Y-m-d');

                // Ajoute tout les delegue qui se trouve dans le rapport Médecin
                $RapportMed = RapportMed::
                select('DELEGUE_id', DB::raw('count(*) as Nombre_Rapport_Medecin'))->
                where('date_de_visite', $Date)->
                groupBy('DELEGUE_id')->
                get()->
                toArray();
                // Ajouter les nom des délégué
                foreach ($RapportMed as $Value2) {
                    $IdNomDelegue[] = $Value2['DELEGUE_id'];
                }
                //Fin Commentaire

                // Ajoute tout les delegue qui se trouve dans le rapport Pharmacie
                $RapportPh = RapportPh::
                select('DELEGUE_id', DB::raw('count(*) as Nombre_Rapport_Pharmacie'))->
                where('date_de_visite', $Date)->
                groupBy('DELEGUE_id')->
                get()->
                toArray();
                // Ajouter les nom des délégué sans doubelon
                foreach ($RapportPh as $Value3) {
                    if (!in_array($Value3['DELEGUE_id'], $IdNomDelegue)) $IdNomDelegue[] = $Value3['DELEGUE_id'];
                }
                //Fin Commentaire

                // Ajouter les line (date, delegue, secteur, nombre med, nombre ph, nombre bc)
                foreach (User::hasRoles(['DSM', 'KAM', 'DM', 'DPH'])->pluck('user_id') as $IdDelegue) {
                    
                    // Initialisation les variable par defaut
                    $Rapport_NombreVisite['Nombre_Rapport_Medecin'] = $Rapport_NombreVisite['Nombre_Rapport_Pharmacie'] = 0;
                    //Fin Commentaire

                    foreach ($RapportMed as $Visite1) {
                        if($Visite1['DELEGUE_id'] == $IdDelegue){
                            $Rapport_NombreVisite['Nombre_Rapport_Medecin'] = $Visite1['Nombre_Rapport_Medecin'];
                            break;
                        }
                    }
                    foreach ($RapportPh as $Visite2) {
                        if ($Visite2['DELEGUE_id'] == $IdDelegue) {
                            $Rapport_NombreVisite['Nombre_Rapport_Pharmacie'] = $Visite2['Nombre_Rapport_Pharmacie'];
                            break;
                        }
                    }

                    $User = User::find($IdDelegue);

                    $SyntheseJournaliere[] = [
                        'Date' => $Date,
                        'Delegue' => $User == null ? $IdDelegue : $User->prenom . ' ' . $User->nom,
                        'Secteur' => $User == null ? 'Secteur' : Ville::find($User->ville_id)->libelle,
                        'Nombre visites MED' => $Rapport_NombreVisite['Nombre_Rapport_Medecin'],
                        'Nombre visites PH' => $Rapport_NombreVisite['Nombre_Rapport_Pharmacie'],
                        'Nombre BC' => 0
                    ];

                    // if(($User == null ? $IdDelegue : $User->nom . ' ' . $User->prenom) == "TAZI OMAR") dd(
                    //     $Rapport_NombreVisite
                    //     ,
                    //     [
                    //     'Date' => $Date,
                    //     'Delegue' => $User == null ? $IdDelegue : $User->nom . ' ' . $User->prenom,
                    //     'Secteur' => $User == null ? 'Secteur' : Ville::find($User->ville_id)->libelle,
                    //     'Nombre visites MED' => $Rapport_NombreVisite['Nombre_Rapport_Medecin'],
                    //     'Nombre visites PH' => $Rapport_NombreVisite['Nombre_Rapport_Pharmacie'],
                    //     'Nombre BC' => 0
                    // ]
                    // );

                }
            }
            // dd($SyntheseJournaliere);
            $sheets = new SheetCollection([
                'Synthèse Journalière' => $SyntheseJournaliere
            ]);
            return (new FastExcel($sheets))->download('Synthèse Journalière ' . (new DateTime())->format("Y-m-d H-i-s") . '.xlsx');
        }
        elseif($request->TypeExport == 'Exporte_Synthese_Hebdomadaire_DM')
        {
            return true;
        }
        elseif($request->TypeExport == 'Exporte_Synthese_Hebdomadaire_DPH')
        {
            return true;
        }

    }
}
