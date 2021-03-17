<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;

$client = new MongoDB\Client;
$mydb = $client->mydb;
$myTable = $mydb->myTable;

$spreadsheet = IOFactory::load('rapport.xlsx');

$autoFilter = $spreadsheet->getActiveSheet()->getAutoFilter();
$columnFilter = $autoFilter->getColumn('W');
$columnFilter->setFilterType(
    \PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column::AUTOFILTER_FILTERTYPE_FILTER
);

$columnFilter->createRule()
    ->setRule(
        \PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
        'Réalisé'
    );

$columnFilter->createRule()
    ->setRule(
        \PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
        'Réalisé hors Plan'
    );

$autoFilter->showHideRows();

$tablo[][] = array(array());
$i = 0;
$j = 0;

foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
    if ($spreadsheet->getActiveSheet()->getRowDimension($row->getRowIndex())->getVisible())
    {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);
        foreach ($cellIterator as $cell)
        {
            $tablo[$i][$j] = $cell->getValue();
            $j++;
        }
        $j = 0;
        $i++;
    }
}

//echo "<pre>";
//echo print_r($tablo);
//echo "</pre>";

$myTablo = array(array());
$i = 0; $j = 0;
for ($i = 0; $i < sizeof($tablo) - 1; $i++)
{
    foreach (array("Date de visite", "Nom Prenom", "Specialité", "Etablissement", "Potentiel", "Montant Inv Précédents", "Zone-Ville", "P1 présenté", "P1 Feedback", "P1 Ech", "P2 présenté", "P2 Feedback", "P2 Ech", "P3 présenté", "P3 Feedback", "P3 Ech", "P4 présenté", "P4 Feedback", "P4 Ech", "P5 présenté", "P5 Feedback", "P5 Ech", "Plan/Réalisé")
    as $value)
    {
        $myTablo[$i][$value] = $tablo[$i + 1][$j];
        $j++;
    }
    $j = 0;
}

$dataInserted = $myTable->insertMany($myTablo);

echo "<pre>";
print_r($myTablo);
echo "</pre>";

?>