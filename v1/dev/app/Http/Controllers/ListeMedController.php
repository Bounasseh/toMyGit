<?php

namespace App\Http\Controllers;

use App\Liste_Med;
use App\Medecin;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Secteur;
use Illuminate\Support\Facades\DB;

class ListeMedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:SUPADMIN|ADMIN');
    }

    public function index()
    {
        return view('import.list_med.index');
    }

    public function get_Liste_med(Request $request)
    {
        $list_med  = Liste_Med::all();
        return response()->json($list_med);
    }

    public function import(Request $request)
    {
        $GLOBALS["i"] = 0;
        $start_time = microtime(true);

        if ($request->hasFile('list_med')) {

            $file = $request->file('list_med');
            (new FastExcel)->sheet(16)->import($file, function ($line) {
                // var_dump($line);
                $row = Liste_Med::where([
                    ['Nom_Prenom', '=', $line['Nom Prenom']],
                    ['Adresse', '=', $line["Adresse"]],
                ])->first();
                if ($row === null) {
                    if (empty($line["Montant Investissements Précédents"])) {
                        $line["Montant Investissements Précédents"] = 0;
                    }
                    try {
                        $GLOBALS["i"]++;
                        return Liste_Med::create([
                            'Nom_Prenom' => $line["Nom Prenom"],
                            'Specialité' => $line["Specialité"],
                            'Secteur' => $line["Secteur"],
                            'Etablissement' => $line["Etablissement"],
                            'Tel' => $line["Tel"],
                            'Adresse' => $line["Adresse"],
                            'Zone_Sous-Secteur' => $line["Zone Sous-Secteur"],
                            'Ville' => $line["Ville"],
                            'Potentiel' => $line["Potentiel"],
                            'Inv_Précédents' => $line["Investissements Précédents"],
                            'Montant_Inv_Précédents' => $line["Montant Investissements Précédents"]
                        ]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                }
            });
        }

        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);

        return redirect()->route('show_list_med')->with('status', $GLOBALS["i"] . '  Row uploaded successfully in ' . strftime("%X ", $execution_time));
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
     * @param  \App\Liste_Med  $liste_Med
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $liste_medecins = DB::table('medecins')
                              ->join('villes', 'medecins.ville_id', '=', 'villes.ville_id')
                              ->join('specialites', 'medecins.specialite_id', '=', 'specialites.specialite_id')
                              ->select('nom', 'prenom', 'etablissement', 'potentiel', 'Zone_med', 'villes.libelle', 'specialites.libelle')
                              ->paginate(50);
        //dd($liste_medecins);
        return view('import.list_med.show', compact('liste_medecins'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Liste_Med  $liste_Med
     * @return \Illuminate\Http\Response
     */
    public function edit(Liste_Med $liste_Med)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Liste_Med  $liste_Med
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Liste_Med $liste_Med)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Liste_Med  $liste_Med
     * @return \Illuminate\Http\Response
     */
    public function destroy(Liste_Med $liste_Med)
    {
        //
    }
}
