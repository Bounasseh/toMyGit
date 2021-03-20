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

class RapportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:SUPADMIN|ADMIN');
        $GLOBALS['villes'] = 0;
    }

}
