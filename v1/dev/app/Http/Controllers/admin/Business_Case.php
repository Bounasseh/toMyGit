<?php

namespace App\Http\Controllers\admin;

// use App\vr;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Http\Controllers\Controller;
use App\BusinessCasesMedecin;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\SheetCollection;
use App\RapportMed;
use App\Ville;
use App\Exports\RapportMedExport;
use Carbon\Carbon;
use DateTime;
use GMP;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Imports\FileImport;
use Spatie\Async\Pool;

class Business_Case extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.Import.Business_Case');
        //
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
     * @param  \App\vr  $vr
     * @return \Illuminate\Http\Response
     */
    public function show($vr)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\vr  $vr
     * @return \Illuminate\Http\Response
     */
    public function edit($vr)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\vr  $vr
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $vr)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\vr  $vr
     * @return \Illuminate\Http\Response
     */
    public function destroy($vr)
    {
        //
    }

    private function IsExistColumnInLine($Line, $ColumnsListNames)
    {
        // dd($Line, $ColumnsListNames);
        $Result = true;
        $ColumnsListNamesValues = array_values($ColumnsListNames);
        for ($i = 0; $i < (count($Line) >= count($ColumnsListNames) ? count($Line) : count($ColumnsListNames)); $i++) {
            $result = false;
            if (isset($ColumnsListNamesValues[$i]))
                if (in_array('?', (array)$ColumnsListNamesValues[$i])) $result = true;
                else
                    foreach ((array)$ColumnsListNamesValues[$i] as $value) {
                        $result = array_key_exists($value, $Line);
                        // dd($value, $Line, $result);
                        // $result = $result || isset($Line[$value]);
                        if ($result === true) break;
                    }
            $Result = $Result && $result;
            if ($Result === false) break;
        }

        // if ($Result == false) dd($Result, $ColumnsListNamesValues[$i], $value, $Line, isset($Line[$value]), array_key_exists($value, $Line));

        // dd($Result);
        return $Result;
    }

    private function GetValueInLineByColumn($line, $ColumnsListNames, $Column)
    {
        foreach ($ColumnsListNames as $Key => $Value)
            if ($Column == $Key)
                foreach ($Value as $key => $value)
                    if (isset($line[$value]))
                        return $line[$value];
        return null;
    }

    private $ColumnsListNamesBusinessCase =
    [
        'Date Demande' => ['Date Demande'],
        'Date Réalisation' => ['Date Réalisation'],
        'Nom Prenom' => ['Nom Prenom'],
        'Specialité' => ['Specialité '],
        'Etablissement' => ['Etablissement'],
        'Potentiel' => ['Potentiel'],
        'Zone-Ville' => ['Zone-Ville'],
        'Montant Inv Précédents' => ['Montant Inv Précédents'],
        'Mois Différents' => ['Mois Différents'],
        'Type Inv' => ['Type Inv'],
        'Destination' => ['Destination'],
        'Détail' => ['Détail'],
        'Montant Inv' => ['Montant Inv'],
        'P1 concerné' => ['P1 concerné'],
        'P1 Achetés par jour' => ['P1 Achetés par jour'],
        'P2 concerné' => ['P2 concerné'],
        'P2 Achetés par jour' => ['P2 Achetés par jour'],
        'P3 concerné' => ['P3 concerné'],
        'P3 Achetés par jour' => ['P3 Achetés par jour'],
        'Status' => ['Status'],
        'Satisfaction' => ['Satisfaction'],
        'Engagement' => ['Engagement']
    ];

    private $ColumnsListNamesDélégué =
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

    public function import(Request $request)
    {
        session(['ActiveAffichageResultat' => $ActiveAffichageResultat = $request->ActiveAffichageResultat != null]);

        $GLOBALS["CNF"] = '';
        $GLOBALS['date_last'] = Carbon::today()->subDays(5);
        $GLOBALS['date_from'] = Carbon::today()->subDays(11);
        $GLOBALS["Number Rows Uploaded"] = 0;
        $GLOBALS["NombreLineDejaExiste"] = 0;
        $GLOBALS["NombreLineRefusé"] = 0;
        $ListMessages['Erreurs'] = [];
        $ListMessages['Success'] = [];
        $GLOBALS["ListMessages"] = $ListMessages;

        if ($request->hasFile('import_file')) {
            $files = $request->file('import_file');

            foreach ($files as $file) {
                $GLOBALS["CountListMessagesErreur"] = count($GLOBALS["ListMessages"]['Erreurs']);
                $start_time = microtime(true);

                $GLOBALS["file_name"] = $file->getClientOriginalName();
                $GLOBALS["Sheet_Number"] = false;
                $GLOBALS["Sheet_Index_Délégué"] = false;

                //Recherche index de feuille
                for ($i = 1; $i <= 15; $i++) {
                    $SheetLine = (new FastExcel)->sheet($i)->import($file);
                    if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesBusinessCase) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
                }

                // if ($GLOBALS["Sheet_Number"] == false) $GLOBALS["CNF"] = 0;
                $GLOBALS["array"] = [];

                
                if (gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") array_push($GLOBALS["ListMessages"]['Erreurs'], 'La feuille de delegue n\'existe pas!');

                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']) && $GLOBALS["Sheet_Number"] > 0) {

                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") {
                        $SheetLine = (new FastExcel)->sheet($GLOBALS["Sheet_Index_Délégué"])->import($file);
                        $GLOBALS["Delegue_PrenomNom"] = $SheetLine[0]['prenom'] . ' ' . $SheetLine[0]['nom'];
                        $GLOBALS["DELEGUE_id"] = $SheetLine[0]['id_user'];
                    } else {
                        $GLOBALS["Delegue_PrenomNom"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
                        $GLOBALS["DELEGUE_id"] = 0;
                        if ($GLOBALS["Delegue_PrenomNom"] == false) array_push($GLOBALS["ListMessages"]['Erreurs'], 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]);
                    }
                    
                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) (new FastExcel)->sheet($GLOBALS["Sheet_Number"])->import($file, function ($line) {

                        $Nom_Prenom = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Nom_Prenom');
                        $Date_de_visite = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Date_de_visite');
                        // $GLOBALS["IsValideDateDeVisite"] = (gettype($Date_de_visite) == 'string');
                        if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                        if (gettype($Date_de_visite) == 'string')
                        array_push($GLOBALS["ListMessages"]['Erreurs'], 'La date ' . (empty($Date_de_visite) ? 'est vide  ' : '(' . $Date_de_visite . ') de format incorrect') . ' dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                        $Plan_Réalisé = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Plan/Réalisé');
                        
                        if (!(!empty($Nom_Prenom) && !empty($Date_de_visite) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan"))) $GLOBALS["NombreLineRefusé"]++;
                        else
                        {
                            $IsExistsVisite = RapportMed::select(['Nom_Prenom', 'Date_de_visite'])
                                ->where([
                                    ['Nom_Prenom', $Nom_Prenom],
                                    ['Date_de_visite', $Date_de_visite],
                                    ['DELEGUE_id', $GLOBALS["DELEGUE_id"]]
                                ])->exists();
                            // if($GLOBALS["num"] == 2) dd($IsExistsVisite, RapportMed::where([
                            //     ['Nom_Prenom', $Nom_Prenom],
                            //     ['Date_de_visite', $Date_de_visite],
                            //     ['DELEGUE_id', $GLOBALS["DELEGUE_id"]]
                            // ])->get(), $line);

                            if ($IsExistsVisite) $GLOBALS["NombreLineDejaExiste"]++;
                            else {
                                $Specialité = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Specialité');
                                $Etablissement = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Etablissement');
                                $Potentiel = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Potentiel');
                                $Montant_Inv_Précédents = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Montant_Inv_Précédents');
                                $Zone_Ville = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Zone_Ville');
                                $P1_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P1_présenté');
                                $P1_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P1_Feedback');
                                $P1_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P1_Ech');
                                $P2_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P2_présenté');
                                $P2_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P2_Feedback');
                                $P2_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P2_Ech');
                                $P3_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P3_présenté');
                                $P3_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P3_Feedback');
                                $P3_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P3_Ech');
                                $P4_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P4_présenté');
                                $P4_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P4_Feedback');
                                $P4_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P4_Ech');
                                $P5_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P5_présenté');
                                $P5_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P5_Feedback');
                                $P5_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P5_Ech');
                                // $Materiel_Promotion = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Materiel_Promotion');
                                // $Invitation_promise = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Invitation_promise');

                                if ((gettype($Montant_Inv_Précédents) == 'integer' && $Montant_Inv_Précédents == 0) || gettype($Montant_Inv_Précédents) == 'string') $Montant_Inv_Précédents = 0;
                                if (empty($Etablissement)) $Etablissement = '#ERREUR';
                                if (empty($Specialité)) $Specialité = '#ERREUR';

                                if (empty($P1_Ech)) $P1_Ech = 0;
                                if (empty($P2_Ech)) $P2_Ech = 0;
                                if (empty($P3_Ech)) $P3_Ech = 0;
                                if (empty($P4_Ech)) $P4_Ech = 0;
                                if (empty($P5_Ech)) $P5_Ech = 0;

                                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) if (gettype($P1_Ech) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'Le nombre d\'echantient P1 (' . $P1_Ech . ') de format incorrect dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) if (gettype($P2_Ech) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'Le nombre d\'echantient P2 (' . $P2_Ech . ') de format incorrect dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) if (gettype($P3_Ech) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'Le nombre d\'echantient P3 (' . $P3_Ech . ') de format incorrect dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) if (gettype($P4_Ech) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'Le nombre d\'echantient P4 (' . $P4_Ech . ') de format incorrect dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) if (gettype($P5_Ech) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'Le nombre d\'echantient P5 (' . $P4_Ech . ') de format incorrect dans le fichier : "' . $GLOBALS["file_name"] . "\"");

                                $ville = $this->id_ville($Zone_Ville);
                                // $ville = Ville::select('ville_id')->where('libelle', '=', $Zone_Ville)->first();

                                $liste = [
                                    // 'Date_de_visite' => Carbon::parse($Date_de_visite)->toDateTimeString(),
                                    'Date_de_visite' => $Date_de_visite,
                                    'Nom_Prenom' => $Nom_Prenom,
                                    'Specialité' => $Specialité,
                                    'Etablissement' => $Etablissement,
                                    'Potentiel' => $Potentiel,
                                    'Montant_Inv_Précédents' => $Montant_Inv_Précédents,
                                    'Zone_Ville' => $Zone_Ville,

                                    'P1_présenté' => $P1_présenté,
                                    'P1_Feedback' => $P1_Feedback,
                                    'P1_Ech' => $P1_Ech,

                                    'P2_présenté' => $P2_présenté,
                                    'P2_Feedback' => $P2_Feedback,
                                    'P2_Ech' => $P2_Ech,

                                    'P3_présenté' => $P3_présenté,
                                    'P3_Feedback' => $P3_Feedback,
                                    'P3_Ech' => $P3_Ech,

                                    'P4_présenté' => $P4_présenté,
                                    'P4_Feedback' => $P4_Feedback,
                                    'P4_Ech' => $P4_Ech,

                                    'P5_présenté' => $P5_présenté,
                                    'P5_Feedback' => $P5_Feedback,
                                    'P5_Ech' => $P5_Ech,

                                    'ville_id' => $ville,
                                    // 'Materiel_Promotion' => $Materiel_Promotion,
                                    // 'Invitation_promise' => $Invitation_promise,
                                    'Plan/Réalisé' => $Plan_Réalisé,
                                    //'Visite_Individuelle/Double' => $line['Name'],
                                    'DELEGUE' => $GLOBALS["Delegue_PrenomNom"],
                                    // 'DELEGUE' => $GLOBALS["Delegue_PrenomNom"],
                                    'DELEGUE_id' => $GLOBALS["DELEGUE_id"],
                                    'created_at' => now()->format('Y-m-d H-i-s')
                                ];

                                array_push($GLOBALS["array"], $liste);
                            }
                        }
                    });

                    $GLOBALS["Number Rows Uploaded"] += count($GLOBALS["array"]);

                    //Ajouter les rapport_med separé a 2000 rapport vers la base de donne
                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                    if (count($GLOBALS["array"]) > 2000)
                        foreach (array_chunk($GLOBALS["array"], 2000) as $smallerArray) {
                            foreach ($smallerArray as $index => $value)
                            $temp[$index] = $value;
                            DB::table('rapport_meds')->insert($temp);
                        }
                    elseif (count($GLOBALS["array"]) > 0) DB::table('rapport_meds')->insert($GLOBALS["array"]);
                } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifier les Colonnes Business Case du Ficher : "' . $GLOBALS["file_name"] . '"');

                if ($GLOBALS["CNF"] === 10) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]);
                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) {
                    if ($GLOBALS["Number Rows Uploaded"] == 0) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun visite à ajouté : ' . $GLOBALS["file_name"]);
                    else array_push($GLOBALS["ListMessages"]['Success'], 'Temps : ' . strftime("%X ", microtime(true) - $start_time) . ', Fichier : "' . $GLOBALS["file_name"] . "\"");
                }
            }
        } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun fichier à importer');

        return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with(['ListMessages' => $GLOBALS["ListMessages"]]);


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // session(['ActiveAffichageResultat' => $ActiveAffichageResultat = $request->ActiveAffichageResultat != null]);

        // $GLOBALS["CNF"] = '';
        // $GLOBALS['date_last'] = Carbon::today()->subDays(5);
        // $GLOBALS['date_from'] = Carbon::today()->subDays(11);
        // $GLOBALS["Number Rows Uploaded"] = 0;
        // $GLOBALS["NombreLineDejaExiste"] = 0;
        // $GLOBALS["NombreLineRefusé"] = 0;
        // $ListMessages['Erreurs'] = [];
        // $ListMessages['Success'] = [];
        // $GLOBALS["ListMessages"] = $ListMessages;




        // $GLOBALS["start_time"] = $start_time = microtime(true);
        // $GLOBALS["Number Rows Uploaded"] = 0;
        // $GLOBALS["NombreLineDejaExiste"] = 0;
        // $GLOBALS["NombreLineRefusé"] = 0;
        // $ListErreur = [];

        // if ($request->hasFile('import_file')) {
        //     $files = $request->file('import_file');
        //     foreach ($files as $file) {

        //         $GLOBALS["CountListMessagesErreur"] = count($GLOBALS["ListMessages"]['Erreurs']);
        //         $GLOBALS["Sheet_Number"] = $GLOBALS["Sheet_Index_Délégué"] = false;

        //         for ($i = 1; $i <= 15; $i++) {
        //             $SheetLine = (new FastExcel)->sheet($i)->import($file);
        //             if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesBusinessCase) ? $i : false) : false;
        //             if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
        //             if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
        //         }

        //         $GLOBALS["array"] = [];
        //         if ($GLOBALS["Sheet_Number"] > 0) {
        //             (new FastExcel)->sheet($GLOBALS["Sheet_Number"])->import($file, function ($line) {

        //                 $Date_Demande = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Date Demande');
        //                 $Date_Réalisation = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Date Réalisation');
        //                 $Nom_Prenom = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Nom Prenom');
        //                 $Specialité = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Specialité');
        //                 $Etablissement = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Etablissement');
        //                 $Potentiel = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Potentiel');
        //                 $Zone_Ville = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Zone-Ville');
        //                 $Montant_Inv_Précédents = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Montant Inv Précédents');
        //                 $Mois_Différents = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Mois Différents');
        //                 $Type_Inv = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Type Inv');
        //                 $Destination = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Destination');
        //                 $Détail = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Détail');
        //                 $Montant_Inv = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Montant Inv');
        //                 $P1_concerné = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P1 concerné');
        //                 $P1_Achetés_par_jour = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P1 Achetés par jour');
        //                 $P2_concerné = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P2 concerné');
        //                 $P2_Achetés_par_jour = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P2 Achetés par jour');
        //                 $P3_concerné = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P3 concerné');
        //                 $P3_Achetés_par_jour = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'P3 Achetés par jour');
        //                 $Status = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Status');
        //                 $Satisfaction = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Satisfaction');
        //                 $Engagement = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesBusinessCase, 'Engagement');

        //                 if(gettype($Montant_Inv)=="string" ) $Montant_Inv = floatval($Montant_Inv);
        //                 if(gettype($Montant_Inv_Précédents)=="string" ) $Montant_Inv_Précédents = floatval($Montant_Inv_Précédents);
        //                 if(gettype($Date_Demande) != "object") $Date_Demande = null;
        //                 if(gettype($Date_Réalisation) != "object") $Date_Réalisation = null;

        //                 $liste = [
        //                     'Date Demande' => $Date_Demande,
        //                     'Date Réalisation' => $Date_Réalisation,
        //                     'Nom Prenom' => $Nom_Prenom,
        //                     'Specialité' => $Specialité,
        //                     'Etablissement' => $Etablissement,
        //                     'Potentiel' => $Potentiel,
        //                     'Zone-Ville' => $Zone_Ville,
        //                     'Montant Inv Précédents' => $Montant_Inv_Précédents,
        //                     'Montant Inv Précédents' => null,
        //                     'Mois Différents' => $Mois_Différents,
        //                     'Type Inv' => $Type_Inv,
        //                     'Destination' => $Destination,
        //                     'Détail' => $Détail,
        //                     'Montant Inv' => $Montant_Inv,
        //                     'P1 concerné' => $P1_concerné,
        //                     'P1 Achetés par jour' => $P1_Achetés_par_jour,
        //                     'P2 concerné' => $P2_concerné,
        //                     'P2 Achetés par jour' => $P2_Achetés_par_jour,
        //                     'P3 concerné' => $P3_concerné,
        //                     'P3 Achetés par jour' => $P3_Achetés_par_jour,
        //                     'Status' => $Status,
        //                     'Satisfaction' => $Satisfaction,
        //                     'Engagement' => $Engagement
        //                 ];

        //                 // dd($liste );

        //                 array_push($GLOBALS["array"], $liste);
        //             });
        //             // dd($GLOBALS["array"]);

        //             $GLOBALS["Number Rows Uploaded"] += count($GLOBALS["array"]);

        //             //Ajouter les BC separé a 2000 rapport vers la base de donne
        //             if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
        //             DB::table('Business_Cases_Medecins')->insert($GLOBALS["array"]);
        //         }
        //         else array_push($ListErreur, 'Ne contient pas la feuille de busines case dans le fichier : ' . $file->getClientOriginalName());
        //     }
        // }
        // else
        // {
        //     array_push($ListErreur, 'Vous n\'avez joins aucun fichier !');
        //     return redirect()->route('Import_Business_Case')->withErrors($ListErreur);
        // }
        // // return redirect()->route('Import_Business_Case')->withErrors(['Erreur 1','Erreur 2', 'Erreur 2', 'Erreur 2', 'Erreur 2','Erreur 2', 'Erreur 2']);
        // // else redirect()->route('Import_Business_Case')->withErrors(['Error' => 'N\'existe aucun fichier n\'est joint']);

        // // dd($ListErreur);
        // return redirect()->route('Import_Business_Case')->withErrors($ListErreur);
        // return redirect()->route($ActiveAffichageResultat ? 'admin.bcs.bcs_index' : 'Import_Business_Case')->with(['ListMessages' => $GLOBALS["ListMessages"]]);

    }

}
