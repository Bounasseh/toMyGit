<?php

namespace App\Http\Controllers;

use App\Imports\RapportPh as ImportsRapportPh;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use App\RapportPh;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class RapportPhController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('role:SUPADMIN|ADMIN');
    }

    public function file(){
        return view('import.rapportPh.file');
    }

    public function index()
    {
        $RapportPhColumns = [
            // 'rapport_ph_id' => 'Rapport Med Identifient',
            'Date_de_visite' => 'Date de visite',
            'pharmacie_zone' => 'Pharmacie Zone',
            'Potentiel' => 'Potentiel',
            // 'P1_présenté' => 'P1 présenté',
            // 'P1_nombre_boites' => 'P1 nombre boites',
            // 'P2_présenté' => 'P2 présenté',
            // 'P2_nombre_boites' => 'P2 nombre boites',
            // 'P3_présenté' => 'P3 présenté',
            // 'P3_nombre_boites' => 'P3 nombre boites',
            // 'P4_présenté' => 'P4 présenté',
            // 'P4_nombre_boites' => 'P4 nombre boites',
            // 'P5_présenté' => 'P5 présenté',
            // 'P5_nombre_boites' => 'P5 nombre boites',
            'Plan/Réalisé' => 'Plan/Réalisé',
            'DELEGUE' => 'DELEGUE',
            // 'DELEGUE_id' => 'DELEGUE ID',
            // 'created_at' => 'Created',
            // 'updated_at' => 'Updated',
        ];
        $RapportPhListe = RapportPh::orderBy('date_de_visite')->paginate(20);
        // dd($RapportPhListe);
        return view('import.rapportPh.index', compact(['RapportPhColumns', 'RapportPhListe']));
    }

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

    private function GetValueInLineByColumn($line, $ColumnsListNames, $Column)
    {
        foreach ($ColumnsListNames as $Key => $Value)
            if ($Column == $Key)
                foreach ($Value as $value)
                    if (isset($line[$value])) return $line[$value];
        return null;
    }

    private function IsExistColumnInLine($Line, $ColumnsListNames)
    {
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

    private $ColumnsListNamesRapportPH =
    [
        'Date_de_visite' => ['Date de visite', 'Date', 'Date De Visite'],
        'pharmacie_zone' => ['PHARMACIE-ZONE'],
        'Potentiel' => ['Potentiel'],
        'P1_présenté' => ['P1 présenté'],
        'P1_nombre_boites' => ['P1 Nombre de boites'],
        'P2_présenté' => ['P2 présenté'],
        'P2_nombre_boites' => ['P2 Nombre de boites'],
        'P3_présenté' => ['P3 présenté'],
        'P3_nombre_boites' => ['P3 Nombre de boites'],
        'P4_présenté' => ['P4 présenté'],
        'P4_nombre_boites' => ['P4 Nombre de boites'],
        'P5_présenté' => ['P5 présenté'],
        'P5_nombre_boites' => ['P5 Nombre de boites'],
        'Plan/Réalisé' => ['Plan/Réalisé']
    ];


    // private $ColumnsListNamesRapportPH_v2 =
    // [
    //     'Date_de_visite' => ['Date'],
    //     'pharmacie_zone' => ['PHARMACIE / PARA'],

    //     'Potentiel' => ['Potentiel'],
    //     'P1_présenté' => ['P1 présenté'],
    //     'P1_nombre_boites' => ['P1 Nombre de boites'],
    //     'P2_présenté' => ['P2 présenté'],
    //     'P2_nombre_boites' => ['P2 Nombre de boites'],
    //     'P3_présenté' => ['P3 présenté'],
    //     'P3_nombre_boites' => ['P3 Nombre de boites'],
    //     'P4_présenté' => ['P4 présenté'],
    //     'P4_nombre_boites' => ['P4 Nombre de boites'],
    //     'P5_présenté' => ['P5 présenté'],
    //     'P5_nombre_boites' => ['P5 Nombre de boites'],

    //     'Plan/Réalisé' => ['Plan/Réalisé']
    // ];

    private $ColumnsListNamesDélégué =
    [
        'id_user' => ['id_user'],
        'id_region' => ['id_region'],
        'id_ville' => ['id_ville'],
        'id_secteur' => ['id_secteur'],
        'nom' => ['nom'],
        'prenom' => ['prenom'],
        'gamme' => ['gamme'],
        // 'id_type' => ['id_type']
    ];

    public function import(Request $request)
    {

        session(['ActiveAffichageResultat' => $ActiveAffichageResultat = $request->ActiveAffichageResultat != null]);

        $GLOBALS["CNF"] = '';
        $GLOBALS['date_last'] = Carbon::today()->subDays(5);
        $GLOBALS['date_from'] = Carbon::today()->subDays(11);

        $ListMessages['Erreurs'] = [];
        $ListMessages['Success'] = [];
        $GLOBALS["ListMessages"] = $ListMessages;

        if ($request->hasFile('import_file')) {
            $files = $request->file('import_file');

            foreach ($files as $file) {
                $GLOBALS["Number Rows Uploaded"] = 0;
                $GLOBALS["NombreLineDejaExiste"] = 0;
                $GLOBALS["NombreLineRefusé"] = 0;

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
                    'Redouane'
                ];
                // $GLOBALS["Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
                // if ($GLOBALS["Délégué"] == false) return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]]);

                $GLOBALS["Sheet_Number"] = false;
                $GLOBALS["Sheet_Index_Délégué"] = false;

                //Recherche index de feuille
                for ($i = 1; $i <= 15; $i++) {
                    $SheetLine = (new FastExcel)->sheet($i)->import($file);
                    if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesRapportPH) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
                }

                // dd($GLOBALS["Sheet_Number"], $GLOBALS["Sheet_Index_Délégué"]);

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

                            $pharmacie_zone = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'pharmacie_zone');
                            $Date_de_visite = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'Date_de_visite');
                            $Plan_Réalisé = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'Plan/Réalisé');

                            if ($pharmacie_zone == 'PHARMACIE-ZONE' || ($Plan_Réalisé != "Réalisé" && $Plan_Réalisé != "Réalisé hors Plan")) $GLOBALS["NombreLineRefusé"]++;
                            elseif (isset($pharmacie_zone) && isset($Date_de_visite)) {

                                // $row = Arr::where($GLOBALS["RapportPhAll"], function ($value) {
                                //     return Str::contains(Str::upper($value['pharmacie_zone']), Str::upper($GLOBALS['pharmacie_zone'])) && date('Y-m-d', strtotime($value['Date_de_visite'])) == $GLOBALS['Date_de_visite']->format('Y-m-d');
                                // });

                                $IsExistsPharmacie = RapportPh::select(['pharmacie_zone', 'Date_de_visite'])->where([
                                    // ['Nom_Prenom', '=', $line['Nom Prenom']],
                                    ['pharmacie_zone', '=', $pharmacie_zone],
                                    // ['Date_de_visite', '=', $Date_de_visite]
                                    ['Date_de_visite', '=', $Date_de_visite]
                                ])->exists();

                                if ($IsExistsPharmacie) $GLOBALS["NombreLineDejaExiste"]++;
                                elseif (!empty($pharmacie_zone) && $pharmacie_zone != 'PHARMACIE-ZONE' && ($Plan_Réalisé == "Réalisé" || $Plan_Réalisé == "Réalisé hors Plan")) // && $line["Date de visite"] > $GLOBALS['date_from'] && $line["Date de visite"] < $GLOBALS['date_last']
                                {
                                    $Potentiel = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'Potentiel');
                                    $P1_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P1_présenté');
                                    $P1_nombre_boites = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P1_nombre_boites');
                                    $P2_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P2_présenté');
                                    $P2_nombre_boites = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P2_nombre_boites');
                                    $P3_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P3_présenté');
                                    $P3_nombre_boites = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P3_nombre_boites');
                                    $P4_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P4_présenté');
                                    $P4_nombre_boites = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P4_nombre_boites');
                                    $P5_présenté = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P5_présenté');
                                    $P5_nombre_boites = $this->GetValueInLineByColumn($line, $this->ColumnsListNamesRapportPH, 'P5_nombre_boites');

                                    if (gettype($P1_nombre_boites) == 'string') $P1_nombre_boites = 0;
                                    if (gettype($P2_nombre_boites) == 'string') $P2_nombre_boites = 0;
                                    if (gettype($P3_nombre_boites) == 'string') $P3_nombre_boites = 0;
                                    if (gettype($P4_nombre_boites) == 'string') $P4_nombre_boites = 0;
                                    if (gettype($P5_nombre_boites) == 'string') $P5_nombre_boites = 0;

                                    $liste = [
                                        'Date_de_visite' => $Date_de_visite,
                                        'pharmacie_zone' => $pharmacie_zone,
                                        'Potentiel' => $Potentiel,
                                        'P1_présenté' => $P1_présenté,
                                        'P1_nombre_boites' => $P1_nombre_boites,
                                        'P2_présenté' => $P2_présenté,
                                        'P2_nombre_boites' => $P2_nombre_boites,
                                        'P3_présenté' => $P3_présenté,
                                        'P3_nombre_boites' => $P3_nombre_boites,
                                        'P4_présenté' => $P4_présenté,
                                        'P4_nombre_boites' => $P4_nombre_boites,
                                        'P5_présenté' => $P5_présenté,
                                        'P5_nombre_boites' => $P5_nombre_boites,
                                        'Plan/Réalisé' => $Plan_Réalisé,

                                        // 'DELEGUE' => $GLOBALS["Délégué"],
                                        'DELEGUE' => $GLOBALS["Delegue_PrenomNom"],
                                        'DELEGUE_id' => $GLOBALS["DELEGUE_id"],
                                        'created_at' => now()->format('Y-m-d H-i-s')

                                        // 'DELEGUE_id' => gettype($GLOBALS["DELEGUE_id"]) == 'string' ? 0 : $GLOBALS["DELEGUE_id"]
                                    ];
                                    array_push($GLOBALS["array"], $liste);
                                }
                            } else {
                                $GLOBALS["CNF"] = 10;
                                return null;
                            }
                        });

                    $GLOBALS["Number Rows Uploaded"] += count($GLOBALS["array"]);

                    //Ajouter les rapport_med separé a 2000 rapport vers la base de donne
                    if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs']))
                        if (count($GLOBALS["array"]) > 2000)
                            foreach (array_chunk($GLOBALS["array"], 2000) as $smallerArray) {
                                foreach ($smallerArray as $index => $value)
                                    $temp[$index] = $value;
                                DB::table('rapport_phs')->insert($temp);
                            }
                        elseif (count($GLOBALS["array"]) > 0) DB::table('rapport_phs')->insert($GLOBALS["array"]);
                } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifier les Colonnes Rapport Pharmacie du Ficher : "' . $GLOBALS["file_name"] . '"');
                if ($GLOBALS["CNF"] === 10) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]);
                if ($GLOBALS["CountListMessagesErreur"] == count($GLOBALS["ListMessages"]['Erreurs'])) {
                    if ($GLOBALS["Number Rows Uploaded"] == 0) array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun visite à ajouté : ' . $GLOBALS["file_name"]);
                    else array_push($GLOBALS["ListMessages"]['Success'], 'Temps : ' . strftime("%X ", microtime(true) - $start_time) . ', Fichier : "' . $GLOBALS["file_name"] . "\"");
                }
            }
        } else array_push($GLOBALS["ListMessages"]['Erreurs'], 'Aucun fichier à importer');

        return redirect()->route($ActiveAffichageResultat ? 'show_rapport_ph' : 'file_import_rapportPh')->with(['ListMessages' => $GLOBALS["ListMessages"]]);

        // $end_time = microtime(true);
        // $execution_time = ($end_time - $start_time);
        // if ($GLOBALS["NombreLineDejaExiste"] == 0 && $GLOBALS["NombreLineRefusé"] == 0 && $GLOBALS["Number Rows Uploaded"] == 0) return redirect()->route('file_import_rapportMed')->withErrors(['Error' => 'Aucun fichier à importer']);
        // else return redirect()->route('show_rapport_ph')->with('status', $GLOBALS["NombreLineDejaExiste"] . ' Rows already exsist, ' . $GLOBALS["NombreLineRefusé"] . ' Rows rejected, ' . $GLOBALS["Number Rows Uploaded"] . ' Rows uploaded, in ' . strftime("%X ", $execution_time));
    }

    public function import1(Request $request)
    {
        set_time_limit(500);
        $GLOBALS["CNF"] = '';
        $DMs = array(
            'ELOUADEH',
            'IDDER',
            'NABIL BISTFALEN',
            'MOHAMED LAAMRAOUI',
            'BADRE BENJELLOUN',
            'AMINE EL MOUTAOUAKKIL ALAOUI',
            'GHIZLANE EL OUADEH',
            'MOHAMMED BOUHNINA MARNISSI',
            'TARIK FAHSI',
            'FIRDAOUSSE BELARABI',
            'NAOUFEL BOURHIME',
            'KARIMA BENHLIMA',
            'KARIM BERRADY',
            'HASSAN BELAHCEN',
            'HICHAM EL HANAFI',
            'MOSTAFA GHOUNDAL',
            'NADA CHAFAI',
            'MUSTAPHA HASNAOUI',
            'MHAMED BOUHMADI',
            'MOHAMED EL OUADEH',
            'RACHID CHAMI',
            'IDDER HAMDANI',
            'FOUAD BOUZIYANE',
            'ADIL SENHAJI',
            'NAJIB SKALLI',
            'RAJA KABBAJ',
            'MOHAMED BOURRAGAT',
            'TAREK BAJJOU',
            'SALIM BOUHLAL',
            'IMANE BOUJEDDAYINE',
            'MOUNA CHARRADI',
            'HASSAN IAJIB',
            'HANANE DLIMI',
            'ABDERRAHMANE',
            'ZAKARIA TEMSAMANI',
            'HICHAM EL MOUSTAKHIB',
            'HOUDA HMIDAY'
        );

        $files = $request->file('import_file');

        if ($request->hasFile('import_file')) {
            foreach ($files as $file) {


                $GLOBALS["file_name"] = $file->getClientOriginalName();
                $GLOBALS["Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);

                if ($GLOBALS["Délégué"] != FALSE) {
                    (new FastExcel)->sheet(5)->import($file, function ($line) {

                        //dd($line);

                        if (!empty($line["PHARMACIE-ZONE"]) && ($line["Plan/Réalisé"] == "Réalisé" || $line["Plan/Réalisé"] == "Réalisé hors Plan")) {

                            if (empty($line["P1 Nombre de boites"])) {
                                $line["P1 Nombre de boites"] = 0;
                            }
                            if (empty($line["P2 Nombre de boites"])) {
                                $line["P2 Nombre de boites"] = 0;
                            }
                            if (empty($line["P3 Nombre de boites"])) {
                                $line["P3 Nombre de boites"] = 0;
                            }
                            if (empty($line["P4 Nombre de boites"])) {
                                $line["P4 Nombre de boites"] = 0;
                            }
                            if (empty($line["P5 Nombre de boites"])) {
                                $line["P5 Nombre de boites"] = 0;
                            }
                            try {
                                return RapportPh::create([

                                    //'Date_de_visite' => $line["Date de visite"]->format('Y-m-d H:i:s'),
                                    'Date_de_visite' => Carbon::parse($line['Date de visite'])->toDateTimeString(),
                                    'pharmacie_zone' => $line["PHARMACIE-ZONE"],
                                    'Potentiel' => $line["Potentiel"],
                                    //'Zone_Ville' => $line["Zone-Ville"],

                                    'P1_présenté' => $line["P1 présenté"],
                                    'P1_nombre_boites' => $line["P1 Nombre de boites"],

                                    'P2_présenté' => $line["P2 présenté"],
                                    'P2_nombre_boites' => $line["P2 Nombre de boites"],

                                    'P3_présenté' => $line["P3 présenté"],
                                    'P3_nombre_boites' => $line["P3 Nombre de boites"],

                                    'P4_présenté' => $line["P4 présenté"],
                                    'P4_nombre_boites' => $line["P4 Nombre de boites"],

                                    'P5_présenté' => $line["P5 présenté"],
                                    'P5_nombre_boites' => $line["P5 Nombre de boites"],


                                    'Plan/Réalisé' => $line["Plan/Réalisé"],
                                    //'Visite_Individuelle/Double' => $line['Name'],
                                    'DELEGUE' => $GLOBALS["Délégué"],
                                    //'DELEGUE_id' => 1

                                ]); //end return create
                            } catch (\Exception  $e) {

                                $GLOBALS["CNF"] = 1;
                                //echo $e->getMessage();
                            }
                        } //end if test Plan/Réalisé


                    }); //end FastExcel)->sheet(5)

                } else {
                    return redirect()->route('file_import_rapportMed')->withErrors(['Error' => 'Corrigez le nom  du fichier : ' . $GLOBALS["file_name"]]);
                } //$GLOBALS["Délégué"] != FALSE

            } //end foreach
            //dd($GLOBALS["CNF"]);
            if ($GLOBALS["CNF"] === 1) {
                //echo 'result : '.$GLOBALS["CNF"];
                return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'Vérifier les Colonnes rapport Ph du Ficher  : ' . $GLOBALS["file_name"]]);
            } else {
                return redirect()->route('show_rapport_ph')->with('status', '  File(s) uploaded successfully.');
            }
        } //end $request->hasFile

    } //end function

      /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Visite = RapportPh::find($id);
        return view('import.rapportPh.show', compact('Visite'));
        // return view('import.rapportPh.show', compact(['RapportPhColumns', 'RapportPhListe']));
    }

    public function getRapportPh()
    {
        $rapportPh = RapportPh::all();
        return response()->json($rapportPh);
    }

    public function export(Request $request)
    {

        // dd($request);
        $data_ph = RapportPh::where('rapport_ph_id', '<=', 2)->get();
        // $data_ph = RapportPh::whereIn(\DB::raw('MONTH(Date_de_visite)'), $request->mois)->get();

        if (!empty($data_ph->toArray())) {
            //Data exists
            foreach ($data_ph as $data) {
                $list[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'PHARMACIE-ZONE' => $data['pharmacie_zone'],
                        'Potentiel' => $data['Potentiel'],
                        'P présenté' => $data['P1_présenté'],
                        'P Nombre de boites' => $data['P1_nombre_boites'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'PHARMACIE-ZONE' => $data['pharmacie_zone'],
                        'Potentiel' => $data['Potentiel'],
                        'P présenté' => $data['P2_présenté'],
                        'P Nombre de boites' => $data['P2_nombre_boites'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'PHARMACIE-ZONE' => $data['pharmacie_zone'],
                        'Potentiel' => $data['Potentiel'],
                        'P présenté' => $data['P3_présenté'],
                        'P Nombre de boites' => $data['P3_nombre_boites'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'PHARMACIE-ZONE' => $data['pharmacie_zone'],
                        'Potentiel' => $data['Potentiel'],
                        'P présenté' => $data['P4_présenté'],
                        'P Nombre de boites' => $data['P4_nombre_boites'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];

                $list[] =
                    [
                        'Date de visite' => Carbon::parse($data['Date_de_visite'])->format('d/m/Y'),
                        'PHARMACIE-ZONE' => $data['pharmacie_zone'],
                        'Potentiel' => $data['Potentiel'],
                        'P présenté' => $data['P5_présenté'],
                        'P Nombre de boites' => $data['P5_nombre_boites'],
                        'Plan/Réalisé' => $data['Plan/Réalisé'],
                        'DELEGUE' => $data['DELEGUE'],
                    ];
            }

            //return (new FastExcel($list))->download('file.xlsx');

            $sheets = new SheetCollection([
                'Synt Hebdo DATA PH' => $list
            ]);

            return (new FastExcel($sheets))->download('Synt_hebdo.xlsx');
        } else {
            //No Data Exists
            //dd('no data exist');
            return redirect()->route('show_rapport_ph')->withErrors(['Error' => 'Il n\'existe aucune ligne à exporté !']);
        }
    }
}
