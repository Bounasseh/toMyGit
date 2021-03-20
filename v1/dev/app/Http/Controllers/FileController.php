<?php

namespace App\Http\Controllers;

use App\Imports\FileImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Imports;


class FileController extends Controller
{
    public function index(){
        return view('import.index');
    }

    public function import(Request $request){
        //$var = $request->file('import_file')." Worked !";
        //echo $var;
        //return view('import.index');


            $import = new FileImport();
            //,'Rapport Ph'
            //$import->sheets('Liste Med','Rapport Med');
            $import->onlySheets('Liste Med', 'Rapport Med');
            //$import->convert('xls');
        /*
            Excel::import($import, 'users.xlsx');
        */
            $files = $request->file('import_file');

            if($request->hasFile('import_file'))
            {
                foreach ($files as $file) {
                    Excel::import($import, $file);
                }
            }





            //dd($table);

            //############################################"


            /*$table = Excel::load($request->file('import_file'), function($reader) {

            })->get();

            dd($table);*/
    }
}
