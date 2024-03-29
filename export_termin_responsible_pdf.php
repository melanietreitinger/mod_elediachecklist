<?php

//include_once("connection.php");
include_once('libs/fpdf.php');
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
global $CFG, $DB;

class PDF extends FPDF
{

// Page header
    function Header()
    {
        // Logo
        $this->Image('pix/logo.png',10,-1,70);
        $this->SetFont('Arial','B',11);
        // Move to the right
        $this->Cell(80);
        // Title
        $type = optional_param('type', '', PARAM_TEXT);
        $this->Cell(80, 10, iconv('UTF-8', 'windows-1252', 'Termincheckliste'), 1, 0, 'C');
        // Line break
        $this->Ln(20);
    }

// Page footer
    function Footer()
    {
        // Arial italic 8
        $this->SetFont('Arial','I',8);

        if ($this->PageNo() >= 1) {
            $this->SetY(-30);
            $this->Cell(0, 10, '______________________________________________________________________', 0, 0, 'C');
            $this->SetY(-25);
            $this->Cell(0, 10, iconv('UTF-8', 'windows-1252', get_string('unterschrift_eklausurteam', "elediachecklist")), 0, 0, 'C');

            $this->SetY(-20);
            $this->Cell(0, 10, iconv('UTF-8', 'windows-1252', get_string('Place_pdf', "elediachecklist") . " " . date('d.m.Y', time())) , 0, 0, 'C');

            $this->SetY(-15);
            $this->Cell(0, 10, iconv('UTF-8', 'windows-1252',get_string('Es_wird_vom_Fachgebiet', "elediachecklist")), 0, 0, 'C');

        }
        // Position at 1.5 cm from bottom
        $this->SetY(-10);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    //Cell with horizontal scaling if text is too wide
    function CellFit($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $scale=false, $force=true)
    {
        //Get string width
        $str_width=$this->GetStringWidth($txt);

        //Calculate ratio to fit cell
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $ratio = ($w-$this->cMargin*2)/$str_width;

        $fit = ($ratio < 1 || ($ratio > 1 && $force));
        if ($fit)
        {
            if ($scale)
            {
                //Calculate horizontal scaling
                $horiz_scale=$ratio*100.0;
                //Set horizontal scaling
                $this->_out(sprintf('BT %.2F Tz ET',$horiz_scale));
            }
            else
            {
                //Calculate character spacing in points
                $char_space=($w-$this->cMargin*2-$str_width)/max(strlen($txt)-1,1)*$this->k;
                //Set character spacing
                $this->_out(sprintf('BT %.2F Tc ET',$char_space));
            }
            //Override user alignment (since text will fill up cell)
            $align='';
        }

        //Pass on to Cell method
        $this->Cell($w,$h,$txt,$border,$ln,$align,$fill,$link);

        //Reset character spacing/horizontal scaling
        if ($fit)
            $this->_out('BT '.($scale ? '100 Tz' : '0 Tc').' ET');
    }

    //Cell with horizontal scaling only if necessary
    function CellFitScale($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,false);
    }

    //Cell with horizontal scaling always
    function CellFitScaleForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,true);
    }

    //Cell with character spacing only if necessary
    function CellFitSpace($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,false);
    }

    //Cell with character spacing always
    function CellFitSpaceForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        //Same as calling CellFit directly
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,true);
    }
}

//$mysqli = new mysqli($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);

$examid = optional_param('examid', 0, PARAM_INT);

$tab = elediachecklist_tab('eledia_adminexamdates_itm'); // elediachecklist__item
$examTopics = $DB->get_records($tab);

$tab = elediachecklist_tab('eledia_adminexamdates_chk'); // elediachecklist__check
$checkedTopics = $DB->get_records($tab, ['teacherid' => $examid]);

$sql = "SELECT * from {eledia_adminexamdates} exam where id =" . $examid;
$result = $DB->get_records_sql($sql);
$examStart = 0;
foreach($result as $one) {
    $examStart = $one->examtimestart;
}


$pdf = new PDF('L');

//header
$pdf->AddPage();
//foter page
$pdf->AliasNbPages();
$pdf->SetFont('Arial','B',11);

//Column headers
$pdf->Ln();
$pdf->Cell(10, 12, iconv('UTF-8', 'windows-1252', ""), 1, 0, 'C', false, '', 2);
$pdf->Cell(160, 12, iconv('UTF-8', 'windows-1252', "Bezeichnung"), 1, 0, '', false, '', 2);
$pdf->Cell(60, 12, iconv('UTF-8', 'windows-1252', "Days related to exam date"), 1, 0, 'C', false, '', 2);
$pdf->Cell(40, 12, iconv('UTF-8', 'windows-1252',  "Datum"), 1, 0, 'C', false, '', 2);

$pdf->SetFont('Arial','',10);

foreach ($examTopics as &$topic) {

    $topicDate = date('r', $examStart);

    $myDate = strtotime($topic->duetime . ' day', strtotime($topicDate ));

    // Check topic dates to include are before today
    if ($myDate >= strtotime('today') )
        continue;

    $isChecked = "-";
    foreach ($checkedTopics as &$checked) {
        if ($checked->item == $topic->id)
            $isChecked = "X";
    }

    //Ignore checked elements
    if ($isChecked == "X")
        continue;

    $pdf->Ln();

    $pdf->Cell(10, 12, iconv('UTF-8', 'windows-1252', $isChecked), 1, 0, 'C', false, '', 2);
    $pdf->Cell(160, 12, iconv('UTF-8', 'windows-1252', $topic->displaytext), 1, 0, '', false, '', 2);
    $pdf->Cell(60, 12, iconv('UTF-8', 'windows-1252', $topic->duetime), 1, 0, 'C', false, '', 2);
    $pdf->Cell(40, 12, iconv('UTF-8', 'windows-1252',  date('d.m.Y', strtotime($topic->duetime . ' day', strtotime($topicDate )))), 1, 0, 'C', false, '', 2);
}

$pdf->Output();
?>
