<?php

namespace App\Http\Controllers;

// use App\Http\Controllers;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
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

class RapportMedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('role:SUPADMIN|ADMIN');
        $GLOBALS['villes'] = 0;
    }

    public function index()
    {
        return view('import.rapportMed.index');
    }

    public function list()
    {
        $liste_rapportmeds = DB::table('rapport_meds')
        ->select('rapport_med_id', 'Date_de_visite', 'Nom_Prenom', 'Specialité', 'Etablissement', 'Potentiel', 'DELEGUE')
        ->orderBy('Date_de_visite', 'DESC')
        ->paginate(50);

        return view('import.rapportMed.list', compact('liste_rapportmeds'));
    }

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

    public function IsExistsMedecin($NomPrenom, $DateDeVisite)
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
            if (isset($ColumnsListNamesValues[$i]))
                if (in_array('?', $ColumnsListNamesValues[$i])) $result = true;
                else
                    foreach ($ColumnsListNamesValues[$i] as $value) {
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

    private $RapportMedAll;

    //25 Columns => table 'rapport_meds' => Column File Excel Sheet 'Rapport Medecin'
    private $ColumnsListNamesRapportMed =
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
        'id_user' => ['id_user'],
        'id_region' => ['id_region'],
        'id_ville' => ['id_ville'],
        'id_secteur' => ['id_secteur'],
        'nom' => ['nom'],
        'prenom' => ['prenom'],
        'gamme' => ['gamme']
        // 'id_type' => ['id_type']
    ];

    private function GetValueInLineByColumn($line, $ColumnsListNames, $Column)
    {
        foreach ($ColumnsListNames as $Key => $Value)
            if ($Column == $Key)
                foreach ($Value as $key => $value)
                    if (isset($line[$value]))
                        return $line[$value];
        return null;
    }

    public function import(Request $request)
    {
        $ColumnsListNamesRapportMed =
        [
            'Date_de_visite' => [
                'List_Nom' => ['Date', 'Date de visite'],
                'Oblige' => true,
                'RegularExpression' => '^[0-9]$'
            ],
            'Nom_Prenom' => [
                'List_Nom' => ['Nom Prenom', 'Nom Prénom']
            ],
            'Specialité' => [
                'List_Nom' => ['Specialité ', 'Specialité']
            ],
            'Etablissement' => [
                'List_Nom' => ['Etablissement']
            ],
            'Potentiel' => [
                'List_Nom' => ['Potentiel']
            ],
            'Montant_Inv_Précédents' => [
                'List_Nom' => ['Investissements Précédents', 'Montant Inv Précédents']
            ],
            'Zone_Ville' => [
                'List_Nom' => ['Zone-Ville']
            ],
            'P1_présenté' => [
                'List_Nom' => ['P1 présenté']
            ],
            'P1_Feedback' => [
                'List_Nom' => ['P1 Feedback']
            ],
            'P1_Ech' => [
                'List_Nom' => ['P1 Ech']
            ],
            'P2_présenté' => [
                'List_Nom' => ['P2 présenté']
            ],
            'P2_Feedback' => [
                'List_Nom' => ['P2 Feedback']
            ],
            'P2_Ech' => [
                'List_Nom' => ['P2 Ech']
            ],
            'P3_présenté' => [
                'List_Nom' => ['P3 présenté']
            ],
            'P3_Feedback' => [
                'List_Nom' => ['P3 Feedback']
            ],
            'P3_Ech' => [
                'List_Nom' => ['P3 Ech']
            ],
            'P4_présenté' => [
                'List_Nom' => ['P4 présenté', '?']
            ],
            'P4_Feedback' => [
                'List_Nom' => ['P4 Feedback', '?']
            ],
            'P4_Ech' => [
                'List_Nom' => ['P4 Ech', '?']
            ],
            'P5_présenté' => [
                'List_Nom' => ['P5 présenté', '?']
            ],
            'P5_Feedback' => [
                'List_Nom' => ['P5 Feedback', '?']
            ],
            'P5_Ech' => [
                'List_Nom' => ['P5 Ech', '?']
            ],
            'Materiel_Promotion' => [
                'List_Nom' => ['Materiel Promotion', '?']
            ],
            'Invitation_promise' => [
                'List_Nom' => ['Invitation promise', '?']
            ],
            'Plan/Réalisé' => [
                'List_Nom' => ['Plan/Réalisé']
            ],
            'Visite Individuelle/Double' => [
                'List_Nom' => ['Visite Individuelle/Double', '?']
            ]
            // 'DELEGUE' => ['Date'],
            // 'DELEGUE_id' => ['Date'],
            // 'ville_id' => ['Date']
        ];

        if (false) return (new Controller)->ImportRapport($request, $ColumnsListNamesRapportMed);

        else {
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

                    $DMs = [
                        'ELOUADEH',
                        'IDDER',
                        'NABIL BISTFALEN',
                        // 'NABIL',
                        'LAAMRAOUI',
                        'AMINE EL MOUTAOUAKKIL ALAOUI',
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
                    for ($i = 1; $i <= 15; $i++) {
                        $SheetLine = (new FastExcel)->sheet($i)->import($file);
                        if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesRapportMed) ? $i : false) : false;
                        if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
                        if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
                    }

                    if ($GLOBALS["Sheet_Number"] == false) $GLOBALS["CNF"] = 0;
                    
                    $GLOBALS["array"] = [];
                    if ($GLOBALS["Sheet_Number"] > 0) {

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

                            $Nom_Prenom = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Nom_Prenom');
                            $Date_de_visite = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Date_de_visite');
                            // $GLOBALS["IsValideDateDeVisite"] = (gettype($Date_de_visite) == 'string');
                            if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                            if (gettype($Date_de_visite) == 'string')
                            array_push($GLOBALS["ListMessages"]['Erreurs'], 'La date ' . (empty($Date_de_visite) ? 'est vide  ' : '(' . $Date_de_visite . ') de format incorrect') . ' dans le fichier : "' . $GLOBALS["file_name"] . "\"");
                            $Plan_Réalisé = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Plan/Réalisé');
                            
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
                                    $Specialité = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Specialité');
                                    $Etablissement = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Etablissement');
                                    $Potentiel = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Potentiel');
                                    $Montant_Inv_Précédents = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Montant_Inv_Précédents');
                                    $Zone_Ville = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Zone_Ville');
                                    $P1_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_présenté');
                                    $P1_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_Feedback');
                                    $P1_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P1_Ech');
                                    $P2_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_présenté');
                                    $P2_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_Feedback');
                                    $P2_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P2_Ech');
                                    $P3_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_présenté');
                                    $P3_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_Feedback');
                                    $P3_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P3_Ech');
                                    $P4_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_présenté');
                                    $P4_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_Feedback');
                                    $P4_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P4_Ech');
                                    $P5_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_présenté');
                                    $P5_Feedback = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_Feedback');
                                    $P5_Ech = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'P5_Ech');
                                    // $Materiel_Promotion = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Materiel_Promotion');
                                    // $Invitation_promise = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportMed, 'Invitation_promise');

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
                    } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifier les Colonnes Rapport Médecin du Ficher : "' . $GLOBALS["file_name"] . '"');
                    // if ($GLOBALS["CNF"] === 4) array_push($GLOBALS["ListMessages"]['Erreurs'],  'Data already exits : ' . $GLOBALS["file_name"]);

                    if ($GLOBALS["CNF"] === 10) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]);
                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) {
                        if ($GLOBALS["Number Rows Uploaded"] == 0) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun visite à ajouté : ' . $GLOBALS["file_name"]);
                        else array_push($GLOBALS["ListMessages"]['Success'], 'Temps : ' . strftime("%X ", microtime(true) - $start_time) . ', Fichier : "' . $GLOBALS["file_name"] . "\"");
                    }
                    // if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                    // if ($GLOBALS["NombreLineDejaExiste"] != 0 || $GLOBALS["NombreLineRefusé"] != 0 || $GLOBALS["Number Rows Uploaded"] != 0)
                    //     array_push($GLOBALS["ListMessages"]['Success'], 'Temps : ' . strftime("%X ", microtime(true) - $start_time) . ', Fichier : "' . $GLOBALS["file_name"] . "\"");

                    // array_push($ListMessages['Seccess'], $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", microtime(true) - $start_time) . ' de fichier : ' . $GLOBALS["file_name"]);
                }
            } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun fichier à importer');
            // $ListeNomFichier = "";
            // foreach ($GLOBALS["ListMessages"]['Erreurs'] as $key => $value) $ListeNomFichier .= ($key==0?'':' OR ') . (str_contains($value, '"') ? substr($value, strpos($value, '"')) : '');
            // if ($ListeNomFichier != "") array_push($GLOBALS["ListMessages"]['Erreurs'], "Filter dans le dossier : ".$ListeNomFichier);

            return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with(['ListMessages' => $GLOBALS["ListMessages"]]);
            // else return redirect()->route($ActiveAffichageResultat ? 'show_rapport_med' : 'file_import_rapportMed')->with('status', $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", $execution_time));
        }
    }

    public function show()
    {
        // $RapportMedAll = RapportMed::all();
        // return view('import.rapportMed.show', compact('RapportMedAll'));

        // $rapportMed = (new RapportMed())->newCollection();
        // for ($i=2; $i < (int)(RapportMed::count() / 10000) + 1; $i++) {
        //     foreach (RapportMed::offset(10000 * $i)->limit(10000)->get() as $value) {
        //         $rapportMed->add($value);
        //     }
        // }

        $liste_rapportmeds = DB::table('rapport_meds')
                              ->select('rapport_med_id', 'Date_de_visite', 'Nom_Prenom', 'Specialité', 'Etablissement', 'Potentiel', 'DELEGUE')
                              ->orderBy('Date_de_visite', 'DESC')
                              ->paginate(50);
        
        //dd($liste_rapportmeds);
        return view('import.rapportMed.edit', compact('liste_rapportmeds'));
    }

     /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $rapportMed = RapportMed::findOrfail($id);
        //dd($rapportMed);

        return view('import.rapportMed.show', compact(['rapportMed']));
    }

    public function getRapportMed()
    {
        // $rapportMed = RapportMed::limit(15000)->get();
        // $rapportMed = (new RapportMed())->newCollection();
        // for ($i = 0; $i < (int)(RapportMed::count() / 10000) + 1; $i++) {
        //     foreach (RapportMed::offset(10000 * $i)->limit(10000)->get() as $value) {
        //         $rapportMed->add($value);
        //     }
        // }
        // dd($rapportMed);
        // dd(RapportMed::count());
        // $rapportMed = (new RapportMed())->newCollection();
        // for ($i = 3; $i < (int)(RapportMed::count() / 10000) + 1; $i++) {
        //     foreach (RapportMed::offset(10000 * $i)->limit(10000)->get() as $value) {
        //         $rapportMed->add($value);
        //     }
        // }
        $rapportMed = RapportMed::whereIn(\DB::raw('YEAR(Date_de_visite)'), ['2020', '2021'])->get();
        // $rapportMed = RapportMed::whereIn(\DB::raw('YEAR(Date_de_visite)'), ['2020', '2021'])->limit(10000)->get();
        return response()->json($rapportMed);
    }

    public function export(Request $request)
    {

        //TODO : change request later
        // $data_ph = RapportPh::whereIn(\DB::raw('MONTH(Date_de_visite)'), $request->mois)->get();
        $data_med = RapportMed::whereIn(\DB::raw('MONTH(Date_de_visite)'), $request->mois)->whereIn(\DB::raw('YEAR(Date_de_visite)'), ['2020', '2021'])->get();

        //var_dump($request->mois);
        //die();

        if (!empty($data_med->toArray())) {
            //Data exists

            //partie rapport ph
            // foreach ($data_ph as $data) {

            //     $list_ph[] =
            //     [   'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
            //         'PHARMACIE-ZONE' => $data['pharmacie_zone'],
            //         'Potentiel' => $data['Potentiel'],
            //         'P présenté' => $data['P1_présenté'],
            //         'P Nombre de boites' => $data['P1_nombre_boites'],
            //         'Plan/Réalisé' => $data['Plan/Réalisé'],
            //         'DELEGUE' => $data['DELEGUE'],
            //     ];

            //     $list_ph[] =
            //     [   'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
            //         'PHARMACIE-ZONE' => $data['pharmacie_zone'],
            //         'Potentiel' => $data['Potentiel'],
            //         'P présenté' => $data['P2_présenté'],
            //         'P Nombre de boites' => $data['P2_nombre_boites'],
            //         'Plan/Réalisé' => $data['Plan/Réalisé'],
            //         'DELEGUE' => $data['DELEGUE'],
            //     ];

            //     $list_ph[] =
            //     [   'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
            //         'PHARMACIE-ZONE' => $data['pharmacie_zone'],
            //         'Potentiel' => $data['Potentiel'],
            //         'P présenté' => $data['P3_présenté'],
            //         'P Nombre de boites' => $data['P3_nombre_boites'],
            //         'Plan/Réalisé' => $data['Plan/Réalisé'],
            //         'DELEGUE' => $data['DELEGUE'],
            //     ];

            //     $list_ph[] =
            //     [   'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
            //         'PHARMACIE-ZONE' => $data['pharmacie_zone'],
            //         'Potentiel' => $data['Potentiel'],
            //         'P présenté' => $data['P4_présenté'],
            //         'P Nombre de boites' => $data['P4_nombre_boites'],
            //         'Plan/Réalisé' => $data['Plan/Réalisé'],
            //         'DELEGUE' => $data['DELEGUE'],
            //     ];

            //     $list_ph[] =
            //     [   'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
            //         'PHARMACIE-ZONE' => $data['pharmacie_zone'],
            //         'Potentiel' => $data['Potentiel'],
            //         'P présenté' => $data['P5_présenté'],
            //         'P Nombre de boites' => $data['P5_nombre_boites'],
            //         'Plan/Réalisé' => $data['Plan/Réalisé'],
            //         'DELEGUE' => $data['DELEGUE'],
            //     ];

            // }

            //partie rapport med
            foreach ($data_med as $data) {
                // dd(date('d-m-yy', strtotime("2020/01/01")), $data['Date_de_visite'], Carbon::parse($data['Date_de_visite'])->format('d/m/Y'));

                $list_med[] =
                    [
                        // 'Date de visite' => $data['Date_de_visite'],
                        // 'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d-m-yy'),
                        // 'Date de visite' => date('d-m-yy', strtotime("2020/01/01 00:00:00")),
                        // 'Date de visite' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject("54321")->format('d/m/Y'),
                        // 'Date de visite' => date('d/m/Y', strtotime("2020/01/01 00:00:00")),
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'Nom Prenom' => $data['Nom_Prenom'],
                        'Specialité' => $data['Specialité'],
                        'Etablissement' => $data['Etablissement'],
                        'Potentiel' => $data['Potentiel'],
                        'Montant Inv Précédents' => $data['Montant_Inv_Précédents'],
                        'Zone' => $data['Zone_Ville'],
                        'P présenté' => $data['P1_présenté'],
                        'P Feedback' => $data['P1_Feedback'],
                        'P Ech' => $data['P1_Ech'],
                        'Materiel Promotion' => $data['Materiel_Promotion'],
                        'Invitation promise' => $data['Invitation_promise'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list_med[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'Nom Prenom' => $data['Nom_Prenom'],
                        'Specialité' => $data['Specialité'],
                        'Etablissement' => $data['Etablissement'],
                        'Potentiel' => $data['Potentiel'],
                        'Montant Inv Précédents' => $data['Montant_Inv_Précédents'],
                        'Zone' => $data['Zone_Ville'],
                        'P présenté' => $data['P2_présenté'],
                        'P Feedback' => $data['P2_Feedback'],
                        'P Ech' => $data['P2_Ech'],
                        'Materiel Promotion' => $data['Materiel_Promotion'],
                        'Invitation promise' => $data['Invitation_promise'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list_med[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'Nom Prenom' => $data['Nom_Prenom'],
                        'Specialité' => $data['Specialité'],
                        'Etablissement' => $data['Etablissement'],
                        'Potentiel' => $data['Potentiel'],
                        'Montant Inv Précédents' => $data['Montant_Inv_Précédents'],
                        'Zone' => $data['Zone_Ville'],
                        'P présenté' => $data['P3_présenté'],
                        'P Feedback' => $data['P3_Feedback'],
                        'P Ech' => $data['P3_Ech'],
                        'Materiel Promotion' => $data['Materiel_Promotion'],
                        'Invitation promise' => $data['Invitation_promise'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list_med[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'Nom Prenom' => $data['Nom_Prenom'],
                        'Specialité' => $data['Specialité'],
                        'Etablissement' => $data['Etablissement'],
                        'Potentiel' => $data['Potentiel'],
                        'Montant Inv Précédents' => $data['Montant_Inv_Précédents'],
                        'Zone' => $data['Zone_Ville'],
                        'P présenté' => $data['P4_présenté'],
                        'P Feedback' => $data['P4_Feedback'],
                        'P Ech' => $data['P4_Ech'],
                        'Materiel Promotion' => $data['Materiel_Promotion'],
                        'Invitation promise' => $data['Invitation_promise'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list_med[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'Nom Prenom' => $data['Nom_Prenom'],
                        'Specialité' => $data['Specialité'],
                        'Etablissement' => $data['Etablissement'],
                        'Potentiel' => $data['Potentiel'],
                        'Montant Inv Précédents' => $data['Montant_Inv_Précédents'],
                        'Zone' => $data['Zone_Ville'],
                        'P présenté' => $data['P5_présenté'],
                        'P Feedback' => $data['P5_Feedback'],
                        'P Ech' => $data['P5_Ech'],
                        'Materiel Promotion' => $data['Materiel_Promotion'],
                        'Invitation promise' => $data['Invitation_promise'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];
            }

            // $sheets = new SheetCollection([
            //     'Synt Hebdo DATA PH' => $list_ph,
            //     'Synt Hebdo DATA MED' => $list_med
            // ]);

            // dd($list_med);
            $sheets = new SheetCollection([
                'Synt Hebdo DATA MED' => $list_med
            ]);
            $Result = (new FastExcel($sheets))->download('Synthèse Hebdomadaire ' . (new DateTime())->format("Y-m-d H-i-s") . '.xlsx');
            // return redirect()->route('show_rapport_med')->withErrors(['Error' => 'Il n\'existe aucune ligne à exporté !']);
            return $Result;
            // return redirect()->route('show_rapport_med')->with('status', 'Good');

            // $list = array(
            //     ['Name', 'age', 'Gender'],
            //     ['Bob', 20, 'Male'],
            //     ['John', 25, 'Male'],
            //     ['Jessica', 30, 'Female']
            // );

            // // Open a file in write mode ('w')
            // $fp = fopen('persons.csv', 'w');

            // // Loop through file pointer and a line
            // foreach ($list as $fields) {
            //     fputcsv($fp, $fields);
            // }

            // fclose($fp);

            // Excel::create('Filename', function ($excel) {

            //     $excel->sheet('Sheetname', function ($sheet) {

            //         $sheet->fromArray($list_med);
            //     });
            // })->export('xls');

            // $RapportMedExport = new RapportMedExport;
            // return $RapportMedExport->download('invoices.xlsx');
            // return Excel::download($RapportMedExport, 'RapportMedExport.xlsx');

            // Excel::create('RapportMedExport', function ($excel) {

            //     $excel->sheet('Sheetname', function ($sheet) {

            //         dd(RapportMed::all());
            //         $sheet->fromArray(array(
            //         ));
            //     });
            // })->export('xlsx');

            // RapportMed::all()->download(new RapportMedExport, 'Synt_hebdo.xlsx');
            // (new RapportMedExport)->list()->download('invoices.xlsx');

            // (new RapportMedExport())->list()->download('invoices.xlsx');

            // Excel::create($this->getFilename(), function ($excel) {
            //     $excel->sheet('Sheet1', function ($sheet) {
            //         $sheet->setColumnFormat([
            //             'A' => 'dd/mm/yyyy', // Booked At
            //         ]);

            //         $sheet->fromArray([
            //             'Booked At' => PHPExcel_Shared_Date::PHPToExcel($booking->created_at),
            //         ]);
            //     });
            // });

        } else {
            //No Data Exists
            //dd('no data exist');
            return redirect()->route('show_rapport_med')->withErrors(['Error' => 'Il n\'existe aucune ligne à exporté !']);
        }
    }
}
