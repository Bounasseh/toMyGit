<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;

class ListeVisitesMedController extends Controller
{
    //
    public function index()
    {
        return view('visites.liste_visites');
    }

    public function import(Request $request)
    {
         $ColumnsListNamesDélégué =
        [
            'id_user' => ['id_user'],
            'id_region' => ['id_region'],
            'id_ville' => ['id_ville'],
            'id_secteur' => ['id_secteur'],
            'nom' => ['nom'],
            'prenom' => ['prenom'],
            'gamme' => ['gamme']
            // 'id_type' => ['id_type']
        ];
        $ColumnsListNamesRapportMed =
    [
        'Date_de_visite' => ['Date', 'Date de visite'],
        'Nom_Prenom' => ['Nom Prenom', 'Nom Prénom'],
        'Specialité' => ['Specialité ', 'Specialité'],
        'Etablissement' => ['Etablissement'],
        'Potentiel' => ['Potentiel'],
        'Montant_Inv_Précédents' => ['Investissements Précédents', 'Montant Inv Précédents'],
        'Zone_Ville' => ['Zone-Ville'],
        'P1_présenté' => ['P1 présenté'],
        'P1_Feedback' => ['P1 Feedback'],
        'P1_Ech' => ['P1 Ech'],
        'P2_présenté' => ['P2 présenté'],
        'P2_Feedback' => ['P2 Feedback'],
        'P2_Ech' => ['P2 Ech'],
        'P3_présenté' => ['P3 présenté'],
        'P3_Feedback' => ['P3 Feedback'],
        'P3_Ech' => ['P3 Ech'],
        'P4_présenté' => ['P4 présenté', '?'],
        'P4_Feedback' => ['P4 Feedback', '?'],
        'P4_Ech' => ['P4 Ech', '?'],
        'P5_présenté' => ['P5 présenté', '?'],
        'P5_Feedback' => ['P5 Feedback', '?'],
        'P5_Ech' => ['P5 Ech', '?'],
        'Materiel_Promotion' => ['Materiel Promotion', '?'],
        'Invitation_promise' => ['Invitation promise', '?'],
        'Plan/Réalisé' => ['Plan/Réalisé'],
        'Visite Individuelle/Double' => ['Visite Individuelle/Double', '?']
        // 'DELEGUE' => ['Date'],
        // 'DELEGUE_id' => ['Date'],
        // 'ville_id' => ['Date']
    ];

        app('App\Http\Controllers\Controller')->importExcel($request, $ColumnsListNamesDélégué, $ColumnsListNamesRapportMed);
        /*
        $GLOBALS["i"] = 0;
        $start_time = microtime(true);

        if ($request->hasFile('list_med')) {

            $file = $request->file('list_med');
            (new FastExcel)->sheet(16)->import($file, function ($line) {
                 //var_dump($line);
                
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

        //return redirect()->route('show_list_med')->with('status', $GLOBALS["i"] . '  Row uploaded successfully in ' . strftime("%X ", $execution_time));

        return view('visites.liste_visites');*/
    }
}
