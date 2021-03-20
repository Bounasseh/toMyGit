<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use App\RapportMed;
use App\Ville;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //1- liste des fichier
    //2- importer la listes des columns "ColumnsListNamesRapportMed"
    //3- liste column a importer BD
    //4- le chemen((string)route) de resultat true et si le resultat false
    //5-
    //$files = $request->file('import_file');
    //nomfuntion($files, [], [], 'fiytfoygfu', 'moiubhmjkbh');

    //25 Columns => table 'rapport_meds' => Column File Excel Sheet 'Rapport Medecin'


    //function returns name of delegue from file name

    public function delegue_from_name_file($name_file, $DMs)
    {
        foreach ($DMs as $DM) {
            $contains = Str::contains(Str::upper($name_file), Str::upper($DM));
            if ($contains == true) {
                return $DM;
                break;
            }
        }
        return $contains;
    }

    public function id_ville($ville)
    {
        if (count($GLOBALS['villes']) == 0) $GLOBALS['villes'] = Ville::get()->toArray();
        foreach ((array)$GLOBALS['villes'] as $v) {
            $contains = Str::contains(Str::upper($v['libelle']), Str::upper($ville));
            if ($contains == true) {
                return $v['ville_id'];
                break;
            }
            if (empty($contains)) {
                return null;
            }
        }
        return $contains;
    }

    public function IsExistsMedecin_($NomPrenom, $DateDeVisite)
    {
        foreach ($this->RapportMedAll as $RapportMed)
            if (Str::upper($RapportMed->Nom_Prenom) == Str::upper($NomPrenom) && $DateDeVisite->format('Y-m-d') == date('Y-m-d', strtotime($RapportMed->Date_de_visite)))
                return true;
        return false;
    }

    private function IsExistColumnInLine($Line, $ColumnsListNames)
    {
        // dd($Line, $ColumnsListNames);
        $Result = true;
        $ColumnsListNamesValues = array_values($ColumnsListNames);
        for ($i = 0; $i < (count($Line) >= count($ColumnsListNames) ? count($Line) : count($ColumnsListNames)); $i++) {
            $result = false;
            // dd($ColumnsListNamesValues[$i]['List_Nom']);
            if (isset($ColumnsListNamesValues[$i]['List_Nom']))
                if (in_array('?', $ColumnsListNamesValues[$i]['List_Nom'])) $result = true;
                else
                    foreach ($ColumnsListNamesValues[$i]['List_Nom'] as $value) {
                        $result = array_key_exists($value, $Line);
                        if ($result === true) break;
                        // if(!$result) dd($value, $Line, $result);
                    }
            $Result = $Result && $result;
            // dd($Result, $ColumnsListNames, $Line);
            // if (!$Result) dd($ColumnsListNames, $Line, $ColumnsListNamesValues[$i]);
            if ($Result === false) break;
        }
        // dd($Result, $ColumnsListNames, $Line);
        return $Result;
    }

    private $RapportMedAll_;

    //25 Columns => table 'rapport_meds' => Column File Excel Sheet 'Rapport Medecin'
    private $ColumnsListNamesRapportMed_ =
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

    private $ColumnsListNamesDélégué =
    [
        'id_user' => [
            'List_Nom' => ['id_user']
        ],
        'id_region' => [
            'List_Nom' => ['id_region']
        ],
        'id_ville' => [
            'List_Nom' => ['id_ville']
        ],
        'id_secteur' => [
            'List_Nom' => ['id_secteur']
        ],
        'nom' => [
            'List_Nom' => ['nom']
        ],
        'prenom' => [
            'List_Nom' => ['prenom']
        ],
        'gamme' => [
            'List_Nom' => ['gamme']
        ]
        // 'id_type' => ['id_type']
    ];

    private function GetValueInLineByColumn($line, $ColumnsListNames, $Column)
    {
        foreach ($ColumnsListNames as $Key => $Value)
            if ($Column == $Key)
                foreach ($Value['List_Nom'] as $value)
                    if (isset($line[$value]))
                        return $line[$value];
        return null;
    }

    public function importExcel(Request $request)
    {
    //     session(['ActiveAffichageResultat' => $ActiveAffichageResultat = $request->ActiveAffichageResultat != null]);

    //     $GLOBALS["CNF"] = '';
    //     $GLOBALS['date_last'] = Carbon::today()->subDays(5);
    //     $GLOBALS['date_from'] = Carbon::today()->subDays(11);
    //     $GLOBALS["Number Rows Uploaded"] = 0;
    //     $GLOBALS["NombreLineDejaExiste"] = 0;
    //     $GLOBALS["NombreLineRefusé"] = 0;
    //     $ListMessages['Erreurs'] = [];
    //     $ListMessages['Seccess'] = [];

    //     if ($request->hasFile('import_file')) {
    //         $files = $request->file('import_file');

    //         foreach ($files as $file) {
    //             $start_time = microtime(true);

    //             $GLOBALS["file_name"] = $file->getClientOriginalName();
    //             $DMs = [
    //                 'ELOUADEH',
    //                 'IDDER',
    //                 'NABIL BISTFALEN',
    //                 // 'NABIL',
    //                 'LAAMRAOUI',
    //                 'AMINE EL MOUTAOUAKKIL ALAOUI',
    //                 // 'AMINE',
    //                 // 'GHIZLANE',
    //                 'GHIZLANE EL OUADEH',
    //                 'MARNISSI',
    //                 // 'TARIK F',
    //                 'TARIK',
    //                 'FIRDAOUSSE',
    //                 'NAOUFEL BOURHIME',
    //                 'KARIMA BENHLIMA',
    //                 'BERRADY',
    //                 'HASSAN BELAHCEN',
    //                 'HICHAM EL HANAFI',
    //                 'MOSTAFA',
    //                 'HASNAOUI',
    //                 'MHAMED BOUHMADI',
    //                 'MOHAMED EL OUADEH',
    //                 // 'EL OUADEH',
    //                 'CHAMI',
    //                 'IDDER HAMDANI',
    //                 'FOUAD BOUZIYANE',
    //                 'NAJIB',
    //                 'RAJA',
    //                 'BOURRAGAT',
    //                 'TAREK',
    //                 // 'TAREK BAJJOU',
    //                 'SALIM',
    //                 'IMANE',
    //                 'MOUNA',
    //                 'HASSAN IAJIB',
    //                 // 'HANANE DLIMI',
    //                 'DLIMI',
    //                 'ABDERRAHMANE',
    //                 'FARES',
    //                 'NAOUAL',
    //                 'HAROUCHA',
    //                 // 'BADRADDINE',//mcha f7alo
    //                 'HMIDAY',
    //                 'FIRDAOUSSE',
    //                 'NABIL',
    //                 'GHIZLANE',
    //                 'SAMIRA',
    //                 'AMINE',
    //                 'FOUAD',
    //                 'MHAMED',
    //                 'WAHID',
    //                 'ASMAE',
    //                 'CHAKIR',
    //                 'NAJLAA',
    //                 'HOUDA',
    //                 'BOUTALOUZ',
    //                 'AYMAN',
    //                 'IMAD',
    //                 'ZAKARIA',
    //                 'KENITRA MOHAMED',
    //                 'DALILA',
    //                 'BENANI',
    //                 'JALILA',
    //                 'OUAFAE',
    //                 'NAJAT'
    //             ];

    //             $GLOBALS["Sheet_Number"] = false;
    //             $GLOBALS["Sheet_Index_Délégué"] = false;

    //             //Recherche index de feuille
    //             for ($i = 1; $i <= 10; $i++) {
    //                 $SheetLine = (new FastExcel)->sheet($i)->import($file);
    //                 //dd($SheetLine);
    //                 if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesRapportMed) ? $i : false) : false;
    //                 if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
    //                 if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
    //             }


    //             if ($GLOBALS["Sheet_Number"] == false) $GLOBALS["CNF"] = 0;

    //             $GLOBALS["array"] = [];
    //             if ($GLOBALS["Sheet_Number"] > 0) {

    //                 if (gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") {
    //                     $SheetLine = (new FastExcel)->sheet($GLOBALS["Sheet_Index_Délégué"])->import($file);
    //                     $GLOBALS["Nom_Prenom_Délégué"] = $SheetLine[0]['nom'] . ' ' . $SheetLine[0]['prenom'];
    //                     $GLOBALS["DELEGUE_id"] = $SheetLine[0]['id_user'];
    //                 } else {
    //                     $GLOBALS["Nom_Prenom_Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
    //                     $GLOBALS["DELEGUE_id"] = 0;
    //                     if ($GLOBALS["Nom_Prenom_Délégué"] == false) array_push($ListMessages['Erreurs'], 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]);
    //                 }

    //                 (new FastExcel)->sheet($GLOBALS["Sheet_Number"])->import($file, function ($line) {

    //                     $Nom_Prenom = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Nom_Prenom');
    //                     $Date_de_visite = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Date_de_visite');
    //                     $Plan_Réalisé = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Plan/Réalisé');
    //                     // dd($Nom_Prenom, $Date_de_visite, $Plan_Réalisé);

    //                     if (!(!empty($Nom_Prenom) && !empty($Date_de_visite) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan"))) $GLOBALS["NombreLineRefusé"]++;
    //                     elseif (isset($Nom_Prenom) && isset($Date_de_visite)) {

    //                         // $row = Arr::where($GLOBALS["RapportMedAll"], function ($value) {
    //                         //     return Str::contains(Str::upper($value['Nom_Prenom']), Str::upper($GLOBALS['Nom_Prenom'])) && date('Y-m-d', strtotime($value['Date_de_visite'])) == $GLOBALS['Date_de_visite']->format('Y-m-d');
    //                         // });

    //                         // dd($row);
    //                         // $IsExistsMedecin = false;
    //                         $IsExistsMedecin = RapportMed::select(['Nom_Prenom', 'Date_de_visite'])->where([
    //                             // ['Nom_Prenom', '=', $line['Nom Prenom']],
    //                             ['Nom_Prenom', '=', $Nom_Prenom],
    //                             // ['Date_de_visite', '=', $Date_de_visite]
    //                             ['Date_de_visite', '=', $Date_de_visite]
    //                         ])->exists();
    //                         // if ($Nom_Prenom != 'Nom Prenom') {
    //                         //     dd($line['Nom Prenom'], $Date_de_visite, $row, $line);
    //                         // }

    //                         // $IsExistsMedecin = $this->IsExistsMedecin($Nom_Prenom, $Date_de_visite);
    //                         if ($IsExistsMedecin) $GLOBALS["NombreLineDejaExiste"]++;

    //                         // if ($row != null) $GLOBALS["NombreLineDejaExiste"]++;

    //                         // dd($GLOBALS["NombreLineDejaExiste"], $GLOBALS["NombreLineRefusé"]);
    //                         // if (!empty($Nom_Prenom) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan") && $row == null) // && $line["Date de visite"] > $GLOBALS['date_from'] && $line["Date de visite"] < $GLOBALS['date_last']
    //                         // if ($Nom_Prenom == "EL ACHAARI ADIL") dd(!empty($Nom_Prenom) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan") && $IsExistsMedecin==false);
    //                         // if (!empty($Nom_Prenom) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan") && !$IsExistsMedecin) dd($Nom_Prenom, $Date_de_visite);
    //                         // if (!$IsExistsMedecin) dd($Nom_Prenom, $Date_de_visite);
    //                         elseif (!empty($Nom_Prenom) && !empty($Date_de_visite) && $Nom_Prenom != 'Nom Prenom' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan")) // && $line["Date de visite"] > $GLOBALS['date_from'] && $line["Date de visite"] < $GLOBALS['date_last']
    //                         {
    //                             // if ($IsExistsMedecin) dd($Nom_Prenom, $Date_de_visite);
    //                             // if ($Nom_Prenom == "EL ACHAARI ADIL") dd(!empty($Nom_Prenom) , $Nom_Prenom != 'Nom Prenom' , ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan") , $IsExistsMedecin == false);
    //                             $Specialité = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Specialité');
    //                             $Etablissement = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Etablissement');
    //                             $Potentiel = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Potentiel');
    //                             $Montant_Inv_Précédents = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Montant_Inv_Précédents');
    //                             $Zone_Ville = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Zone_Ville');
    //                             $P1_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_présenté');
    //                             $P1_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_Feedback');
    //                             $P1_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_Ech');
    //                             $P2_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_présenté');
    //                             $P2_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_Feedback');
    //                             $P2_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_Ech');
    //                             $P3_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_présenté');
    //                             $P3_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_Feedback');
    //                             $P3_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_Ech');
    //                             $P4_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_présenté');
    //                             $P4_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_Feedback');
    //                             $P4_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_Ech');
    //                             $P5_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_présenté');
    //                             $P5_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_Feedback');
    //                             $P5_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_Ech');
    //                             // $Materiel_Promotion = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Materiel_Promotion');
    //                             // $Invitation_promise = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Invitation_promise');

    //                             if ((gettype($Montant_Inv_Précédents) == 'integer' && $Montant_Inv_Précédents == 0) || gettype($Montant_Inv_Précédents) == 'string') $Montant_Inv_Précédents = 0;
    //                             if (empty($Etablissement)) $Etablissement = '#ERREUR';
    //                             if (empty($Specialité)) $Specialité = '#ERREUR';
    //                             if (empty($Potentiel)) $Potentiel = '#ERREUR';
    //                             if (empty($P1_Ech)) $P1_Ech = 0;
    //                             if (empty($P2_Ech)) $P2_Ech = 0;
    //                             if (empty($P3_Ech)) $P3_Ech = 0;
    //                             if (empty($P4_Ech)) $P4_Ech = 0;
    //                             if (empty($P5_Ech)) $P5_Ech = 0;

    //                             $ville = $this->id_ville($Zone_Ville);
    //                             // $ville = Ville::select('ville_id')->where('libelle', '=', $Zone_Ville)->first();

    //                             // $GLOBALS["i"]++;

    //                             // try {
    //                             $liste = [
    //                                 // 'Date_de_visite' => Carbon::parse($Date_de_visite)->toDateTimeString(),
    //                                 'Date_de_visite' => $Date_de_visite,
    //                                 'Nom_Prenom' => $Nom_Prenom,
    //                                 'Specialité' => $Specialité,
    //                                 'Etablissement' => $Etablissement,
    //                                 'Potentiel' => $Potentiel,
    //                                 'Montant_Inv_Précédents' => $Montant_Inv_Précédents,
    //                                 'Zone_Ville' => $Zone_Ville,

    //                                 'P1_présenté' => $P1_présenté,
    //                                 'P1_Feedback' => $P1_Feedback,
    //                                 'P1_Ech' => $P1_Ech,

    //                                 'P2_présenté' => $P2_présenté,
    //                                 'P2_Feedback' => $P2_Feedback,
    //                                 'P2_Ech' => $P2_Ech,

    //                                 'P3_présenté' => $P3_présenté,
    //                                 'P3_Feedback' => $P3_Feedback,
    //                                 'P3_Ech' => $P3_Ech,

    //                                 'P4_présenté' => $P4_présenté,
    //                                 'P4_Feedback' => $P4_Feedback,
    //                                 'P4_Ech' => $P4_Ech,

    //                                 'P5_présenté' => $P5_présenté,
    //                                 'P5_Feedback' => $P5_Feedback,
    //                                 'P5_Ech' => $P5_Ech,

    //                                 'ville_id' => $ville,
    //                                 // 'Materiel_Promotion' => $Materiel_Promotion,
    //                                 // 'Invitation_promise' => $Invitation_promise,
    //                                 'Plan/Réalisé' => $Plan_Réalisé,
    //                                 //'Visite_Individuelle/Double' => $line['Name'],
    //                                 'DELEGUE' => $GLOBALS["Nom_Prenom_Délégué"],
    //                                 // 'DELEGUE' => $GLOBALS["Nom_Prenom_Délégué"],
    //                                 'DELEGUE_id' => $GLOBALS["DELEGUE_id"],
    //                                 'created_at' => now()->format('Y-m-d H-i-s')
    //                             ];

    //                             array_push($GLOBALS["array"], $liste);
    //                             //dd($liste);
    //                             // } catch (\Exception $e) {
    //                             //     // $GLOBALS["msg_error"] = $e->getMessage();
    //                             //     $GLOBALS["CNF"] = 0;
    //                             // }
    //                             // RapportMed::create($liste);
    //                         }
    //                     } else {
    //                         // dd($GLOBALS["array"] , $GLOBALS["Number Rows Uploaded"] , $GLOBALS["NombreLineRefusé"], $line);
    //                         $GLOBALS["CNF"] = 10;
    //                         return null;
    //                     }
    //                 });

    //                 $GLOBALS["Number Rows Uploaded"] += count($GLOBALS["array"]);
    //                 //dd($GLOBALS["array"]);
    //                 if (count($GLOBALS["array"]) > 2000)
    //                     foreach (array_chunk($GLOBALS["array"], 2000) as $smallerArray) {
    //                         foreach ($smallerArray as $index => $value)
    //                             $temp[$index] = $value;
    //                         DB::table('rapport_meds')->insert($temp);
    //                     }
    //                 elseif (count($GLOBALS["array"]) > 0) DB::table('rapport_meds')->insert($GLOBALS["array"]);
    //             } else array_push($ListMessages['Erreurs'], 'Vérifier les Colonnes Rapport Médecin du Ficher : ' . $GLOBALS["file_name"]);
    //             if ($GLOBALS["CNF"] === 4) array_push($ListMessages['Erreurs'],  'Data already exits : ' . $GLOBALS["file_name"]);
    //             elseif ($GLOBALS["CNF"] === 10) array_push($ListMessages['Erreurs'], 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]);

    //             if ( $GLOBALS["NombreLineDejaExiste"] != 0 || $GLOBALS["NombreLineRefusé"] != 0 || $GLOBALS["Number Rows Uploaded"] != 0) array_push($ListMessages['Seccess'], $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", microtime(true) - $start_time));
    //         }
    //     } else array_push($ListMessages['Erreurs'], 'Aucun fichier à importer');

    //     return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with(['ListMessages' => $ListMessages]);
    //     // else return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with('status', $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", $execution_time));
    }

    public function show1()
    {
        return view('import.rapportMed.show');
    }

    public function ImportRapport(Request $request, $ColumnsListNamesRapport)
    {
        $GLOBALS['ColumnsListNamesRapport'] = $ColumnsListNamesRapport;
        session(['ActiveAffichageResultat' => $ActiveAffichageResultat = $request->ActiveAffichageResultat != null]);

        $GLOBALS["CNF"] = '';
        $GLOBALS['date_last'] = Carbon::today()->subDays(5);
        $GLOBALS['date_from'] = Carbon::today()->subDays(11);
        $GLOBALS["Number Rows Uploaded"] = 0;
        $GLOBALS["NombreLineDejaExiste"] = 0;
        $GLOBALS["NombreLineRefusé"] = 0;
        $ListMessages['Erreurs'] = [];
        $ListMessages['Seccess'] = [];
        $GLOBALS["ListMessages"] = $ListMessages;

        if ($request->hasFile('import_file')) {
            $files = $request->file('import_file');

            foreach ($files as $file) {
                $GLOBALS["CountListMessagesErreur"] = count($GLOBALS["ListMessages"]['Erreurs']);
                $start_time = microtime(true);

                $GLOBALS["file_name"] = $file->getClientOriginalName();
                $DMs = [
                    'ELOUADEH',
                    'IDDER',
                    'NABIL BISTFALEN',
                    // 'NABIL',
                    'LAAMRAOUI',
                    'AMINE EL MOUTAOUAKKIL ALAOUI',
                    // 'AMINE',
                    // 'GHIZLANE',
                    'GHIZLANE EL OUADEH',
                    'MARNISSI',
                    // 'TARIK F',
                    'TARIK',
                    'FIRDAOUSSE',
                    'NAOUFEL BOURHIME',
                    'KARIMA BENHLIMA',
                    'BERRADY',
                    'HASSAN BELAHCEN',
                    'HICHAM EL HANAFI',
                    'MOSTAFA',
                    'HASNAOUI',
                    'MHAMED BOUHMADI',
                    'MOHAMED EL OUADEH',
                    // 'EL OUADEH',
                    'CHAMI',
                    'IDDER HAMDANI',
                    'FOUAD BOUZIYANE',
                    'NAJIB',
                    'RAJA',
                    'BOURRAGAT',
                    'TAREK',
                    // 'TAREK BAJJOU',
                    'SALIM',
                    'IMANE',
                    'MOUNA',
                    'HASSAN IAJIB',
                    // 'HANANE DLIMI',
                    'DLIMI',
                    'ABDERRAHMANE',
                    'FARES',
                    'NAOUAL',
                    'HAROUCHA',
                    // 'BADRADDINE',//mcha f7alo
                    'HMIDAY',
                    'FIRDAOUSSE',
                    'NABIL',
                    'GHIZLANE',
                    'SAMIRA',
                    'AMINE',
                    'FOUAD',
                    'MHAMED',
                    'WAHID',
                    'ASMAE',
                    'CHAKIR',
                    'NAJLAA',
                    'HOUDA',
                    'BOUTALOUZ',
                    'AYMAN',
                    'IMAD',
                    'ZAKARIA',
                    'KENITRA MOHAMED',
                    'DALILA',
                    'BENANI',
                    'JALILA',
                    'OUAFAE',
                    'NAJAT'
                ];

                $GLOBALS["Sheet_Number"] = false;
                $GLOBALS["Sheet_Index_Délégué"] = false;

                //Recherche index de feuille
                for ($i = 1; $i <= 10; $i++) {
                    $SheetLine = (new FastExcel)->sheet($i)->import($file);
                    if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $ColumnsListNamesRapport) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
                }

                // dd($GLOBALS["Sheet_Number"], $GLOBALS["Sheet_Index_Délégué"]);


                if ($GLOBALS["Sheet_Number"] == false) $GLOBALS["CNF"] = 0;

                $GLOBALS["array"] = [];
                if ($GLOBALS["Sheet_Number"] > 0) {

                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") {
                        $SheetLine = (new FastExcel)->sheet($GLOBALS["Sheet_Index_Délégué"])->import($file);
                        $GLOBALS["Nom_Prenom_Délégué"] = $SheetLine[0]['nom'] . ' ' . $SheetLine[0]['prenom'];
                        $GLOBALS["DELEGUE_id"] = $SheetLine[0]['id_user'];
                    } else {
                        $GLOBALS["Nom_Prenom_Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
                        $GLOBALS["DELEGUE_id"] = 0;
                        if ($GLOBALS["Nom_Prenom_Délégué"] == false) array_push($GLOBALS["ListMessages"]['Erreurs'], 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]);
                    }

                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                    (new FastExcel)->sheet($GLOBALS["Sheet_Number"])->import($file, function ($line) {

                        // return preg_match("/^[a-zA-Z ]+$/", "aaz faz");


                        foreach ($GLOBALS['ColumnsListNamesRapport'] as $key => $value) {
                            // if (gettype($Line['Date_de_visite']) == 'object') preg_match("/^\d\d\d\d-\d\d-\d\d$/", $Line['Date_de_visite']->format('Y-m-d'));
                            $Line[$key] = $this->GetValueInLineByColumn($line, $GLOBALS['ColumnsListNamesRapport'], $key);
                        }

                        $Line['DELEGUE'] = $GLOBALS["Nom_Prenom_Délégué"];
                        $Line['DELEGUE_id'] = $GLOBALS["DELEGUE_id"];
                        $Line['created_at'] = now()->format('Y-m-d H-i-s');

                        // dd(gettype($Line['Date_de_visite']));
                        // dd($Line['Date_de_visite']);

                        // $Nom_Prenom = $this->GetValueInLineByColumn($line, $GLOBALS['ColumnsListNamesRapport'], 'Nom_Prenom');
                        // $Date_de_visite = $this->GetValueInLineByColumn($line, $GLOBALS['ColumnsListNamesRapport'], 'Date_de_visite');

                        // if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                        // if (gettype($Date_de_visite) == 'string') array_push($GLOBALS["ListMessages"]['Erreurs'], 'La date ' . (empty($Date_de_visite) ? 'est vide  ' : '(' . $Date_de_visite . ') de format incorrect') . ' dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                        // $Plan_Réalisé = $this->GetValueInLineByColumn($line, $GLOBALS['ColumnsListNamesRapport'], 'Plan/Réalisé');

                        // if (!(($Line['Plan/Réalisé'] == "Réalisé" || $Line['Plan/Réalisé'] == "Réalisé hors Plan"))) $GLOBALS["NombreLineRefusé"]++;

                        if (isset($Line['Nom_Prenom']) && isset($Date_de_visite)) {

                            // $IsExistsMedecin = RapportMed::select(['Nom_Prenom', 'Date_de_visite'])->where([
                            //     ['Nom_Prenom', '=', $Nom_Prenom],
                            //     ['Date_de_visite', '=', $Date_de_visite]
                            // ])->exists();
                            // if ($IsExistsMedecin) $GLOBALS["NombreLineDejaExiste"]++;

                            if ($Line['Plan_Réalisé'] == "Réalisé" || $Line['Plan_Réalisé'] == "Réalisé hors Plan") {
                                array_push($GLOBALS["array"], $Line);
                            }
                        } else {
                            // dd($GLOBALS["array"] , $GLOBALS["Number Rows Uploaded"] , $GLOBALS["NombreLineRefusé"], $line);
                            array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]);
                            return null;
                        }
                    });

                    $GLOBALS["Number Rows Uploaded"] += count($GLOBALS["array"]);

                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                    if (count($GLOBALS["array"]) > 2000)
                        foreach (array_chunk($GLOBALS["array"], 2000) as $smallerArray) {
                            foreach ($smallerArray as $index => $value) $temp[$index] = $value;
                            try {
                                DB::table('rapport_meds')->insert($temp);
                            } catch (\Exception  $e) {
                                dd($e);
                            }
                        }
                    elseif (count($GLOBALS["array"]) > 0) DB::table('rapport_meds')->insert($GLOBALS["array"]);
                } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifier les Colonnes Rapport Médecin du Ficher : "' . $GLOBALS["file_name"] . '"');

                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                if ($GLOBALS["NombreLineDejaExiste"] != 0 || $GLOBALS["NombreLineRefusé"] != 0 || $GLOBALS["Number Rows Uploaded"] != 0)
                    array_push($GLOBALS["ListMessages"]['Seccess'], 'Temps : ' . strftime("%X ", microtime(true) - $start_time) . ', Fichier : "' . $GLOBALS["file_name"] . "\"");
                // array_push($ListMessages['Seccess'], $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", microtime(true) - $start_time) . ' de fichier : ' . $GLOBALS["file_name"]);
            }
        } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun fichier à importer');

        // $ListeNomFichier = "";
        // foreach ($GLOBALS["ListMessages"]['Erreurs'] as $key => $value) $ListeNomFichier .= ($key==0?'':' OR ') . (str_contains($value, '"') ? substr($value, strpos($value, '"')) : '');
        // if ($ListeNomFichier != "") array_push($GLOBALS["ListMessages"]['Erreurs'], "Filter dans le dossier : ".$ListeNomFichier);

        // dd($GLOBALS["ListMessages"]);
        return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with(['ListMessages' => $GLOBALS["ListMessages"]]);
        // else return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with('status', $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", $execution_time));
    }

}
