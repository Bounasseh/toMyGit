<?php

namespace App\Http\Controllers;

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
use Excel;

class ImportController extends Controller
{

    public function index(){
        //return view('visites.liste_visites');
    }

    public function indexph(){
        //return view('visites.liste_visitesph');
    }

    public function delegue_from_name_file($name_file, $DMs){
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
                foreach ($ColumnsListNamesValues[$i] as $value) {
                    $result = $result || isset($Line[$value]);
                    if ($result === true) break;
                // if(!$result) dd($value, $Line, $result);
            }
            $Result = $Result && $result;
            // dd($Result, $ColumnsListNames, $Line);
            // if ($Result === false) dd($Line, $Result, $ColumnsListNamesValues[$i]);
            if ($Result === false) break;
        }
        return $Result;
    }

    private $ColumnsListNamesRapportPH =
    [
        'Date_de_visite' => ['Date de visite', 'Date'],
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
        'id_type' => ['id_type']
    ];

    public function import(Request $request)
    {

        if ($request->hasFile('import_file')) {

            $files = $request->file('import_file');

            foreach ($files as $file) {

                $GLOBALS["file_name"] = $file->getClientOriginalName();

                $DMs = [
                    'ELOUADEH',
                    'IDDER',
                    'NABIL BISTFALEN',
                    // 'NABIL',
                    'LAAMRAOUI',
                    // 'AMINE EL MOUTAOUAKKIL ALAOUI',
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
                    'DALILA'
                ];

                
                $GLOBALS["Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
                if ($GLOBALS["Délégué"] == false) return redirect()->route('liste_visites')->withErrors(['Error' => 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]]);

                $GLOBALS["Sheet_Number"] = false;
                $GLOBALS["Sheet_Index_Délégué"] = false;

                for ($i = 1; $i <= 10; $i++) {
                    $SheetLine = (new FastExcel)->sheet($i)->import($file);
                    if (gettype($GLOBALS["Sheet_Number"]) != "integer") $GLOBALS["Sheet_Number"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesRapportPH) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Index_Délégué"]) != "integer") $GLOBALS["Sheet_Index_Délégué"] = count($SheetLine) > 0 ? ($this->IsExistColumnInLine($SheetLine[0], $this->ColumnsListNamesDélégué) ? $i : false) : false;
                    if (gettype($GLOBALS["Sheet_Number"]) == "integer" && gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") break;
                }
                dd($SheetLine);
                if (gettype($GLOBALS["Sheet_Index_Délégué"]) == "integer") {
                    $SheetLine = (new FastExcel)->sheet($GLOBALS["Sheet_Index_Délégué"])->import($file);
                    $GLOBALS["Nom_Prenom_Délégué"] = $SheetLine[0]['nom'] . ' ' . $SheetLine[0]['prenom'];
                    $GLOBALS["DELEGUE_id"] = $SheetLine[0]['id_user'];
                    // dd($GLOBALS["DELEGUE_id"]);
                } else {
                    $GLOBALS["Nom_Prenom_Délégué"] = $this->delegue_from_name_file($file->getClientOriginalName(), $DMs);
                    $GLOBALS["DELEGUE_id"] = 0;
                    if ($GLOBALS["Nom_Prenom_Délégué"] == false) return redirect()->route('file_import_rapportMed')->withErrors(['Error' => 'N\'existe pas le nom de délégué dans le fichier : ' . $GLOBALS["file_name"]]);
                }

                if ($GLOBALS["Sheet_Number"] == false) $GLOBALS["CNF"] = 0;

                $GLOBALS["array"] = [];

                if ($GLOBALS["Sheet_Number"] > 0)
                {
                    (new FastExcel)->sheet($GLOBALS["Sheet_Number"])->import($file, function ($line) {

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
                                    'DELEGUE' => $GLOBALS["Nom_Prenom_Délégué"],
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
                    if (count($GLOBALS["array"]) > 2000)
                        foreach (array_chunk($GLOBALS["array"], 2000) as $smallerArray) {
                            foreach ($smallerArray as $index => $value)
                            $temp[$index] = $value;
                            DB::table('rapport_phs')->insert($temp);
                            // dd(true);
                        }
                    elseif (count($GLOBALS["array"]) > 0) DB::table('rapport_phs')->insert($GLOBALS["array"]);

                } else $GLOBALS["CNF"] = 11;

                //add all Rapport Med
                // foreach ($GLOBALS["array"] as $value) RapportPH::create($value);
                // dd(true);
                // RapportPH::insert($GLOBALS["array"]);
                if ($GLOBALS["CNF"] === 0) return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'Vérifier les Colonnes Rapport Med du Ficher  : ou la Zone Ville ' . $GLOBALS["file_name"] . '']);
                elseif ($GLOBALS["CNF"] === 4) return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'Data already exits : ' . $GLOBALS["file_name"]]);
                elseif ($GLOBALS["CNF"] === 10) return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'Vérifer le classement des feuilles : ' . $GLOBALS["file_name"]]);
                elseif ($GLOBALS["CNF"] === 11) return redirect()->route('file_import_rapportPh')->withErrors(['Error' => 'Ce fichier ne contient pas la feuille de Rapport Pharmacie : ' . $GLOBALS["file_name"]]);
            }
        }   
    }
    
}