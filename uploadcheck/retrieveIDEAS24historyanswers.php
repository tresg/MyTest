<?php

$dbg = false;
$ip = array("../common/", "../dynamicnews", "../atd_copy", "../common/initfiles");
set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $ip));

global $argv;
require_once("UtilLib.php");     
if (!isset($fromHTML))
{
    $fromHTML = false;
}
$runAutomation = getmyOption("automation");

$cli = getmyOption("cli");

$runStandardPreUpload = getmyOption("RunStandardUploadChecks");
if (is_bool($runStandardPreUpload) || $runStandardPreUpload != "RunStandard")
{
    $runStandardPreUpload = false;
    $saveTimeStamp = false;
} else {
    $runStandardPreUpload = true; 
    putenv("nonews=true");
    putenv("noatd=true");
    putenv("noco2estimates=true");
    putenv("exportable=true");
    putenv("DetailedChanges=false");
    putenv("PrefixAddInFormulas=true");
    // putenv("ExcludeDPs=");
    putenv("DPstoCheck=");
    putenv("includeFI=true");
    putenv("onlyFI=false");
    $ftmp = getmyOption("from");
    if (is_bool($ftmp) || trim($ftmp) == "")
    {
        $ftmp = getmyOption("fromstd");
        putenv("from=".getmyOption("fromstd"));    
    }
    if ($cli)
    {
        fprintf(STDERR, "Running standard checks for changes\n");
    }
    
    
       
}


/******
* If we run the StandardPreUploadChecks we generate
*   - the CYD for all changed non FI
*   - the CYD for all changed FI impacting scores
*   - the CYD for all changed FI not impacting scores
* excluding atd and excluding news
* 
* @var mixed
*/

$excludeRecentCont = getmyOption("nonews");
$excludeATDCopyDPs = getmyOption("noatd");
$excludeCO2DPs = getmyOption("noco2estimates");
$onlyDynamicUploadDPs = getmyOption("onlyDynamicUploadDPs");

$onlyExportable = true;
foreach(array("exportenabled", "exportable") as $opt)
{
    $tmp = "";
    if (isOptionSet($opt, $tmp))
    {
        $tmp = getmyOption($opt);
        if (is_bool($tmp))
            $onlyExportable = $tmp;
        elseif (is_numeric($tmp))
            $onlyExportable = intval($tmp);
        elseif (strcasecmp($tmp, 'no') == 0)
            $onlyExportable = false;
        elseif (strcasecmp($tmp, 'yes') == 0)
            $onlyExportable = false;        
    }
}


$downloadDetails = getmyOption("DetailedChanges");
$prefixAddInFormuals = getmyOption("PrefixAddInFormulas");
if ($prefixAddInFormuals)
{
    $AddInPrefix = "P";
} else {
    $AddInPrefix = "";
}

$sys =  getmyOption("sys");
if (is_bool($sys))
{
    $sys = getmyOption("system"); 
}
if (is_bool($sys))
{
    $sys = 'ProdDaily';
}
require_once("IDEAS24Init".$sys.".php");  

require_once("GetCYDSubTypes.php");

$ftmp = getmyOption("from");
if ((is_bool($ftmp) || trim($ftmp) == "") && $runAutomation)
{
    if (defined("KEEPSTATETIMESTAMPS") && file_exists(KEEPSTATETIMESTAMPS))
    {
        $s = implode("", file(KEEPSTATETIMESTAMPS));
        $tmp = unserialize($s);
        if (isset($tmp[$sys]))
        {
            putenv("from=".$tmp[$sys]);   
        }
    }
}


$DPsPartofEWR = array();

$q = "select distinct dp.dp_code ".
        "from indicators i, ".
            "( ".
                "select dpd.dp_code as dp_code ".
                " ".
                "from dp_definition dpd ".
                "join dp_content_definition dcd on dcd.dp_definition_id=dpd.dp_definition_id ".
             ") dp ".
        "where calculation_formula ilike '%@'||dp.dp_code||'%' ".
              "or calculation_formula ilike '@'||dp.dp_code||'%' ".
              "or calculation_formula ilike '%@'||dp.dp_code||'' ".
        "order by 1  ";
    
$result = $ideas24->IDEASRequest($q, $cnt, $err);

while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
{
    $tdp = $line["dp_code"];
    $dpc = trim($tdp);
    $DPsPartofEWR[$dpc] = $dpc;    
}
$ideas24->IDEASRequestFree($result);

//This code is added to create seperate CYD for O&D import
$ODImportDataPoint='O&D Data Points';
$DPsPartofODImportDataPoints = array();
    $q = ' SELECT  '.
            ' dp_definition.dp_code '.
        ' FROM  '.
            ' public.cyd_subtypes,  '.
            ' public.cyd_subtypes_dps,  '.
            ' public.dp_definition 
            '.
        ' WHERE  '.
            ' cyd_subtypes.id = cyd_subtypes_dps.cyd_subtype_id AND '.
            ' cyd_subtypes_dps.dp_definition_id = dp_definition.dp_definition_id AND '.
            ' cyd_subtypes.name = '."'".$ODImportDataPoint."' ".
        ' ORDER BY '.
            ' dp_definition.dp_code ASC ';
    
$result = $ideas24->IDEASRequest($q, $cnt, $err);

while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
{
    $tdp = $line["dp_code"];
    $dpc = trim($tdp);
    $DPsPartofODImportDataPoints[$dpc] = $dpc;    
}
$ideas24->IDEASRequestFree($result);  

$whatdps = 'All Data Points';
$inclFI = getmyOption("includeFI");
if (!is_bool($inclFI) && $inclFI == 'rating')
{
    $fiQuery = ""; // any info gathering 
    $whatdps = 'Data Points part of EWR';
    
    $theIncludeDPList = $DPsPartofEWR;
   
    $theDPs2Check = true;  
  
} elseif ($inclFI === 'FIOnly')
{
    $fiQuery = ' AND dp_definition.information_gathering in (1,2) ';    // thomson and company fi
    $whatdps = 'Only Thomson and FI Data Points';
} elseif ($inclFI)
{
    $fiQuery = ""; // any info gathering
} else {
    $fiQuery = ' AND dp_definition.information_gathering = 0 ';    // default only manual data collection 
    $whatdps = 'Manually collected Data Points';
}
$onlyFI = getmyOption("onlyFI");
if ($onlyFI)
{
    $fiQuery = ' AND dp_definition.information_gathering in (1,2) ';    // thomson and company fi
    $whatdps = 'Only Thomson and FI Data Points';
}
if ($onlyExportable)
{
    $expQuery = ' AND dp_definition.export_targets <> 0';
    $whatdps = $whatdps.', only exportable';
} else {
    $expQuery = "";
}

if ($runStandardPreUpload)
{
    $whatdps = "Standard PreUpload Check: ".$whatdps;
}



$odir = getmyOption("output");
if (is_string($odir))
{
    $OutputDir = $odir;
} else {
    $OutputDir = "HistoryValues/";
} 

if (substr($OutputDir, strlen($OutputDir)-1, 1) != '/')
{
    $OutputDir = $OutputDir.'/';
} 

if (!is_dir($OutputDir))
{
    mkdir($OutputDir);
}  



$huser = getmyOption("i24user");
if (is_bool($huser) || $huser == 'all')
    $huser = NULL;
    
    
$dfrom = getmyOption("from");
if (is_bool($dfrom) || $dfrom=='') 
{
    $dfrom = '2008-01-01';
    $dfromwhere = " dp_value_history.dis_timestamp >= '".$dfrom."' ";
    $dfromfrom = "";
} 
else if ($dfrom == 'lastupload')
{
    $dfromwhere = " dp_value_history.dis_timestamp >= COALESCE(value_scopes.last_published_date, '1/1/2000') ";
    $dfromfrom = " INNER JOIN public.value_scopes ON value_scopes.value_scope_id=dp_value_history.his_value_scope_id ";
}
else 
{
    if (!preg_match('/^\d\d\d\d\-\d\d\-\d\d.*/', $dfrom))
    {
        $q = "SELECT clock_timestamp() - interval '".$dfrom."' as sdate";
        $result = $ideas24->IDEASRequest($q, $cnt, $err);
         
        while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
        { 
            $dfrom =   $line['sdate'];
        }
        $ideas24->IDEASRequestFree($result); 
    }   
    
    $dfromwhere = " dp_value_history.dis_timestamp >= '".$dfrom."' ";
    $dfromfrom = "";
}
    



$dto = getmyOption("to");
if (is_bool($dto) || 0 == strncasecmp("now", $dto, 3))
{
    $dqto = "";
    $dto = "Now";
} else {
    $dqto = " AND dp_value_history.dis_timestamp <= '".$dto."'";
}

// include/exclude dps

$theExcludeDPs = getmyOption("ExcludeDPs");
$theExcludeDPList = array();
if (is_string($theExcludeDPs) && trim($theExcludeDPs) != "")
{
    $theExcludeDPs = trim($theExcludeDPs);
    
    
    $tdpl = explode(',',$theExcludeDPs);
    foreach($tdpl as $tdp)
    {
        $dpc = trim($tdp);
        if ($dpc != "")
        {
            $cyddplist = getCYDSubtypeDPs($ideas24, $dpc);
            if (count($cyddplist) > 0)
            {
                foreach($cyddplist as $dpc1)
                {
                    $theExcludeDPList[$dpc1] = $dpc1;    
                }
            } else {
                $theExcludeDPList[$dpc] = $dpc;    
            }  
        }
    }
    
}
$theDPs2Check = getmyOption("DPstoCheck");

$theIncludeDPList = array();
if (is_string($theDPs2Check) && trim($theDPs2Check) != "")
{
    $theDPs2Check = trim($theDPs2Check);
    
    $tdpl = explode(',',$theDPs2Check);
    foreach($tdpl as $tdp)
    {
        $dpc = trim($tdp);
        if ($dpc != "")
        {
            $cyddplist = getCYDSubtypeDPs($ideas24, $dpc);
            if (count($cyddplist) > 0)
            {
                foreach($cyddplist as $dpc1)
                {
                    $theIncludeDPList[$dpc1] = $dpc1;    
                }
            } else {
                $theIncludeDPList[$dpc] = $dpc;    
            }   
        }
    }
    $theDPs2Check = true;  
} else {
    $theDPs2Check = false;
}


$vs = getmyOption("vs");
if (is_bool($vs))
    $vs = 0;
    


if ($dbg)
{
    $yr = 2007;
    $co = 1693;
} else { 
    $yr = getmyOption("year");
    if (is_bool($yr))
        $yr = 0;
        
    $co = getmyOption("company");
    if (is_bool($co))
        $co = 0;
    if ($co == 0)
    {
        $co = getmyOption("c");
        if (is_bool($co))
            $co = 0;
    }
}

if ($vs != 'all')
{
    if (($co == 0 && $yr == 0) && $vs == 0 && $huser == NULL)
    {
        fprintf(STDERR, "Can not identify company: Usage %s [vs:value_scope | company:a4code year:fiscal_year | user:userName] [from:date] [to:date]\n", $argv[0]);
        exit(1);
    }
}


if ($co)
{
    if (strstr($co, ','))
    {
        $colist = explode(',', $co); 
    } else {
        $colist = array($co);
    }
}

$vsalist = array();
if ($vs)
{
    if ($vs != 'all')
    {
        if (strtok($vs, ','))
        {
            $vsalist = explode(',', $vs); 
        } else {
            $vsalist = array($vs);
        }
    }
}

if ($huser != NULL)
{
    if (($co == 0 && $yr == 0) && $vs == 0)
    {
        $vs = 'all';   
    }
    if (strstr($huser, ','))
    {
        $huserl = explode(',', $huser); 
        $qusers = array();
        foreach($huserl as $au)
        {
            $qusers[] = "'".trim($au)."'";
        }
        $huserq = " AND users.username in (".implode(',', $qusers).")";
    } else {
        $huserq = " AND users.username = '".$huser."'";
    }
    $huserjoin = 'INNER JOIN public.users users ON dp_value_history.dis_user_id = users.user_id';
} else {
    $huserq = '';
    $huserjoin = '';
}
$tt = new IDEASUser($ideas24, 'pp');
$t1 = $tt->getUserFormatted();


$invfn = array(' ', ',', "\n", "\r", "\t");
$rep = array_pad(array(" "), count($invfn), " ");


$partialVS = getmyOption("partialvs");
if (is_bool($partialVS))
{
    $partialVS = 'all';
}
    
$partialCondition = '';
if ($partialVS == 'only_partial')
{
    $partialCondition = " AND is_partial='t' ";
    $dfromfrom = " INNER JOIN public.value_scopes ON value_scopes.value_scope_id=dp_value_history.his_value_scope_id ";
} 
else if ($partialVS == 'only_complited')
{
    $partialCondition = " AND is_partial='f' ";
    $dfromfrom = " INNER JOIN public.value_scopes ON value_scopes.value_scope_id=dp_value_history.his_value_scope_id ";
} 

$cn = NULL;
   
$vsinfo = array();
$vslist = array();
$vsall = false;
if ($vs)
{
    if ($vs == 'all')
    {
        $vsall = true;
        $qvall = 
                ' SELECT '.
                ' value_scopes.value_scope_id AS value_scopes_value_scope_id, '.
                ' value_scopes.is_partial AS is_partial, '.
                ' extract(year from value_scopes.time_stamp) AS vsyear, '.
                ' value_scopes.last_published_date as pubdate, '.
                ' companies.asset4_code AS companies_asset4_code, '.
                ' companies.name AS companies_name '.
                ' FROM '.
                ' public.companies companies INNER JOIN public.value_scopes value_scopes ON companies.company_id = value_scopes.company_id ';
                
        $result = $ideas24->IDEASRequest($qvall, $cnt, $err);  
        $vspart = ""; 
    } else {
        $vsall = false;
        $qv = 
                ' SELECT '.
                ' value_scopes.value_scope_id AS value_scopes_value_scope_id, '.
                ' extract(year from value_scopes.time_stamp) AS vsyear, '.
                ' value_scopes.last_published_date as pubdate, '.
                ' value_scopes.is_partial AS is_partial, '.
                ' companies.asset4_code AS companies_asset4_code, '.
                ' companies.name AS companies_name '.
                ' FROM '.
                ' public.companies companies INNER JOIN public.value_scopes value_scopes ON companies.company_id = value_scopes.company_id '.
                ' WHERE '.
                ' value_scopes.value_scope_id in ('.implode(',', $vsalist).')'; 
        
        $result = $ideas24->IDEASRequest($qv, $cnt, $err);
    }
    
    $cn = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
    {
         $co = $line["companies_asset4_code"];
         $vs = $line["value_scopes_value_scope_id"];
          if (!isset($line["pubdate"]) || trim($line["pubdate"]) == "")
            $pdate = 'Never';
         else  
            $pdate = $line["pubdate"];     
         $yr = $line["vsyear"];
         $cn= $line["companies_name"];
         $isPartial = ($line["is_partial"] == 't') ? true : false;
         $tmp = array("A4Code" => $co, "Name" => $cn, "Year" => $yr, "VS" => $vs, "Pub" => $pdate, "IsPartial" => $isPartial); 
         $vsinfo[$vs] = $tmp;
         $vslist[$vs] = $vs;  
    }
    $ideas24->IDEASRequestFree($result);
} else {
    if ($yr == 0)
        $yp = '';
    else
        $yp = ' extract(year from value_scopes.time_stamp) = '.$yr." AND ";
    $qc = 
            ' SELECT '.
            ' value_scopes.value_scope_id AS value_scopes_value_scope_id, '.
            ' extract(year from value_scopes.time_stamp) AS vsyear, '.
            ' value_scopes.last_published_date as pubdate, '. 
            ' value_scopes.is_partial AS is_partial, '.
            ' companies.asset4_code AS companies_asset4_code, '.
            ' companies.name AS companies_name '.
            ' FROM '.
            ' public.companies companies INNER JOIN public.value_scopes value_scopes ON companies.company_id = value_scopes.company_id '.
            ' WHERE '.
            $yp.
            '  companies.asset4_code in ('.implode(',', $colist).')';
    
    $result = $ideas24->IDEASRequest($qc, $cnt, $err);
    $vslist = array();
    $cn = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
    {     
         $co = $line["companies_asset4_code"];
         $vs = $line["value_scopes_value_scope_id"];
         if (!isset($line["pubdate"]) || trim($line["pubdate"]) == "")
            $pdate = 'Never';
         else  
            $pdate = $line["pubdate"];   
         $yr = $line["vsyear"];
         $cn= $line["companies_name"];
         $isPartial = ($line["is_partial"] == 't') ? true : false;
         $tmp = array("A4Code" => $co, "Name" => $cn, "Year" => $yr, "VS" => $vs, "Pub" => $pdate, "IsPartial" => $isPartial); 
         $vsinfo[$vs] = $tmp;
         $vslist[$vs] = $vs;

    }
    $ideas24->IDEASRequestFree($result);
}

if ($vs == 0)
{
    fprintf(STDERR, "No valuescope for %s(%d) A4 code %d in IDEAS24\n", $cn, $yr, $co);
    exit(1);
}

if (count($vsinfo) == 1)
{
    $enames = $cn;
    $eyears = sprintf("(%d)", $yr);
} 
else 
{
    $enames = sprintf("%d Companies", count($vsinfo));
    $eyears = "";
}

$baseFileName = getmyOption("baseFileName");

if (is_string($baseFileName) && trim($baseFileName) != "")
{
    $cname = trim($baseFileName);
}
else if (count($vsinfo) == 1)
{
    $cname = str_replace($invfn, $rep, $cn."_".$yr);
} 
else 
{
    if ($runStandardPreUpload)
    {
        $cname = "_Std_Upload_Checks-".date("ymd-His", time());    
    }   else {
        $cname = "Multiple-VS-".date("ymd-His", time());    
    }
}

$fileNames = array();  

$fp = fopen($fileNames["Details"] = $OutputDir."I24_".$cname.".csv", "w");
$title = array("A4 Code", "Company Name",  "Fiscal Year",  "Value Scope", "DP Code", "Element", "Tag", "User", "Entry Date", "NA", "Numeric Value", "String Value"); 
fputcsv($fp, $title, ",", '"');
$dummyinfo = array("A4Code" => $co, "Name" => $cn, "Year" => $yr, "VS" => 0, "Pub" => "Never", "IsPartial" => true);





if (!$vsall)
{
    $vspart = ' AND dp_value_history.his_value_scope_id in ('.implode(',', $vslist).') ';
}

$excludeAllThese = array();

if ($excludeRecentCont)
{
    // from dynamicnews targets ...
    require_once("recentMappings.inc");    
    
    $RecentTargetDPs = array_unique(array_values($CompletedRecentMapping));
    
    $allRecentDPCodes = array();
    foreach($RecentTargetDPs as $dpc)
    {
        $allRecentDPCodes[] = "'".trim($dpc)."'";
    }
    $excludeAllThese = array_merge($allRecentDPCodes, $excludeAllThese);
    
    $nonewsdps = " AND (NOT (dp_definition.dp_code in ( ".implode(",", $allRecentDPCodes )."))) ";
    $whatdps = $whatdps." Excluding recent controversy DPs";   
} else {
    $whatdps = $whatdps." Including recent controversy DPs";
    $nonewsdps = "";    
}



if ($excludeATDCopyDPs)
{
define("ATDCOPYCYDNAME", "ATD-2-I24-CopyDPs");
    require_once("GetCYDSubTypes.php");    
    
    $RecentTargetDPs = getCYDSubtypeDPs($ideas24, ATDCOPYCYDNAME) ;
    
    $allATDCopyDPCodes = array();
    foreach($RecentTargetDPs as $dpc)
    {
        $allATDCopyDPCodes[] = "'".trim($dpc)."'";
    }
    
    $excludeAllThese = array_merge($allATDCopyDPCodes, $excludeAllThese);
    
    $noatdcopydps = " AND (NOT (dp_definition.dp_code in ( ".implode(",", $allATDCopyDPCodes )."))) ";
    $whatdps = $whatdps." Excluding ATD Copy DPs";   
} else {
    $whatdps = $whatdps." Including ATD Copy DPs";
    $noatdcopydps = "";    
}

if ($excludeCO2DPs)
{
define("CO2ESTIMATESCYDNAME", "CO2EstimateDPs");
    require_once("GetCYDSubTypes.php");    
     
    $CO2EstimateDPs = getCYDSubtypeDPs($ideas24, CO2ESTIMATESCYDNAME) ;
    
    $allCO2EstimateDPs = array();
    foreach($CO2EstimateDPs as $dpc)
    {
        $allCO2EstimateDPs[] = "'".trim($dpc)."'";
    }
    
    $excludeAllThese = array_merge($allCO2EstimateDPs, $excludeAllThese);
    
    $noco2esrimatedps = " AND (NOT (dp_definition.dp_code in ( ".implode(",", $allCO2EstimateDPs )."))) ";
    $whatdps = $whatdps." Excluding CO2 Estimate DPs";   
} else {
    $whatdps = $whatdps." Including CO2 Estimate DPs";
    $noco2esrimatedps = "";    
}

if (count($excludeAllThese) > 0)
{
    $excludeallthesedps = " AND  (dp_definition.dp_code not in ( ".implode(",", array_unique($excludeAllThese )).")) ";    
} else {
    $excludeallthesedps = "";   
}


if ($onlyDynamicUploadDPs)
{
define("ONLY_DYANIC_DPS", "Partial Upload");
    require_once("GetCYDSubTypes.php");    
    
    $RecentTargetDPs = getCYDSubtypeDPs($ideas24, ONLY_DYANIC_DPS) ;
    
    $dynamicUploadDPCodes = array();
    foreach($RecentTargetDPs as $dpc)
    {
        $dynamicUploadDPCodes[] = "'".trim($dpc)."'";
    }
    
    $dynamicuploaddps = " AND (dp_definition.dp_code in ( ".implode(",", $dynamicUploadDPCodes ).")) ";
    $whatdps = $whatdps." Only Dynamic Upload DPs";   
} else {
    $dynamicuploaddps = "";    
}

   


$q = ' SELECT '.
    ' dp_value_history.his_value_scope_id AS dp_value_history_his_value_scope_id, '.
    ' dp_definition.dp_code AS dp_definition_dp_code, '.
    // ' dp_content_definition.dp_order as dporder, '.   
    ' dp_value_history.his_item_order as dporder, '.
    ' dp_value_history.tag_id AS dp_value_history_tag, '.
    ' dp_value_history.dis_user_id AS dp_value_history_dis_user, '.
    ' dp_value_history.dis_timestamp AS dp_value_history_dis_timestamp, '.
    ' dp_value_history.his_no_answer AS dp_value_history_his_na, '.
    ' dp_value_history.his_numeric_value AS dp_value_history_his_numeric_value, '.
    ' dp_value_history.his_string_value AS dp_value_history_his_string_value, '.
    ' dp_definition.information_gathering AS collection '.
  
    ' FROM '.
    ' public.dp_value_history dp_value_history INNER JOIN public.dp_content_definition dp_content_definition '.
    ' ON dp_value_history.his_dp_content_definition_id = dp_content_definition.dp_content_definition_id '.
    ' INNER JOIN public.dp_definition dp_definition ON dp_content_definition.dp_definition_id = dp_definition.dp_definition_id '.
    $dfromfrom.
    $huserjoin.
    ' WHERE '.
        " dp_definition.dp_code <> 'SYS_VS_SCORE_DP_DATE' AND ".
        " dp_definition.dp_code <> 'SYS_VS_PUBLISH_DATE' AND ".
        " dp_definition.dp_code <> 'SYS_VS_IMPORT_DATE' AND ".
        " dp_definition.dp_code <> 'SYS_PARTIAL' AND ".
        $dfromwhere.
    $dqto.
    $fiQuery.' '.
    $vspart.
    $huserq.
    $expQuery.
    $excludeallthesedps.
    $dynamicuploaddps.
    $partialCondition.
    // $nonewsdps.
    // $noatdcopydps.
    // $noco2esrimatedps.
    ' order by dp_value_history.dis_timestamp asc';
    
    
/*
SELECT 
 dpvh.his_value_scope_id AS dp_value_history_his_value_scope_id, 
 dp_definition.dp_code AS dp_definition_dp_code, 
 dpvh.his_item_order as dporder, 
 dpvh.tag_id AS dp_value_history_tag, 
 dpvh.dis_user_id AS dp_value_history_dis_user, 
 dpvh.dis_timestamp AS dp_value_history_dis_timestamp, 
 dpvh.his_no_answer AS dp_value_history_his_na, 
 dpvh.his_numeric_value AS dp_value_history_his_numeric_value, 
 dpvh.his_string_value AS dp_value_history_his_string_value, 
 dp_definition.information_gathering AS collection 
FROM 
 public.dp_value_history dpvh
 INNER JOIN public.dp_content_definition dp_content_definition ON dpvh.his_dp_content_definition_id = dp_content_definition.dp_content_definition_id 
 INNER JOIN public.dp_definition dp_definition ON dp_content_definition.dp_definition_id = dp_definition.dp_definition_id 
 INNER JOIN public.value_scopes ON value_scopes.value_scope_id=dpvh.his_value_scope_id
WHERE 
 dp_definition.dp_code <> 'SYS_VS_SCORE_DP_DATE' AND 
 dp_definition.dp_code <> 'SYS_VS_PUBLISH_DATE' AND 
 dp_definition.dp_code <> 'SYS_VS_IMPORT_DATE' AND 
 dpvh.dis_timestamp >= value_scopes.last_published_date
*/    

if ($cli >= 2)
{
    fprintf(STDERR, "Value query is\n%s\n", $q);
}   


$opTags = array();
$result = $ideas24->IDEASRequest("select * from transaction_tags", $cnt, $err); 
while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
{
    $id = $line['tag_id'];
    $opTags[$id] = $line['tag_description'];
}
$ideas24->IDEASRequestFree($result);


$InIDEAS = array();
$result = $ideas24->IDEASRequest($q, $cnt, $err);
if ($cli)
{
    fprintf(STDERR, "Found %d values for %s in IDEAS24 for %s%s entered between %s and %s\n", $cnt, $whatdps, $enames, $eyears, $dfrom, $dto);
    
    fprintf(STDERR, "Values in %s\n", $OutputDir."I24_".$cname.".csv");
}

$lu = NULL;
$lun = NULL;
$un =NULL;

$csvclist = array();
$cydlist = array();
$cydlistewr = array();
$cydlistnonewr = array();
$changesPerUser = array();
$cydDPs = array();

$standardCYDCorrections = $standardCYDFIRatings = $standardCYDFINoImpact = $ODimportDCYD = array();
$standardCYDSubTypeFINoImpact  = array();


while($line = pg_fetch_array($result, null, PGSQL_ASSOC))
{
    $dpc = $line['dp_definition_dp_code'];
    if (isset($theExcludeDPList[$dpc]))
    {
        continue;
    }
    if ($theDPs2Check && !isset($theIncludeDPList[$dpc]))
    {
        continue;
    }
    $collMode = intval($line['collection']);
    
    
    if (isset($line['dp_value_history_dis_timestamp']))
        $line['dp_value_history_dis_timestamp'] = "'".$line['dp_value_history_dis_timestamp'];
    if (isset($line['dp_value_history_tag']) && isset($opTags[$line['dp_value_history_tag']]))
        $line['dp_value_history_tag'] = $opTags[$line['dp_value_history_tag']];
    if (isset($line['dp_value_history_dis_user']))
    { 
        $un = $line['dp_value_history_dis_user'];
        if ($un != $lun)
        {
            $us = new IDEASUser($ideas24, $un, 'NoUser');
            $lun = $un;
        }
        if ($us->Filled())
        {  
            $line['dp_value_history_dis_user'] = $us->getUserFormatted("u(n/e/l)");
        }
    }
    if (isset($vsinfo[$line['dp_value_history_his_value_scope_id']]))
        $cinfo = $vsinfo[$line['dp_value_history_his_value_scope_id']];
    else
        $cinfo = $dummyinfo;
    $csvclist[$cinfo['A4Code']] = $cinfo['A4Code'];
    $ind = $cinfo['A4Code'].'-'.$cinfo['Year'];
    $cydlist[$ind] = array("Name" => $cinfo['Name'], "A4Code" => $cinfo['A4Code'], "Year" => $cinfo['Year'], "Pub" => $cinfo['Pub'], "IsPartial" => $cinfo['IsPartial']); 
    
    //Modified to add data points that is part of O&D Import to a seperate file .

    if (isset($DPsPartofODImportDataPoints[$dpc]))
    {
      $ODimportDCYD[$ind] = $cydlist[$ind];
    }  else {
            if (isset($DPsPartofEWR[$dpc]))
                {
                    $cydlistewr[$ind] = $cydlist[$ind];
                    if ($collMode == 0)  // manual
                    {
                        $standardCYDCorrections[$ind] = $cydlist[$ind];     
                    } else {
                        $standardCYDFIRatings[$ind] = $cydlist[$ind];          
                    } 
                } else {
                    $cydlistnonewr[$ind] = $cydlist[$ind];
                    if ($collMode == 0) // manual
                    {
                        $standardCYDCorrections[$ind] = $cydlist[$ind];     
                    } else {
                        $standardCYDFINoImpact[$ind] = $cydlist[$ind];
                        $standardCYDSubTypeFINoImpact[$dpc] = $dpc;         
                    }      
                }      
        }
    $ind = $ind.'-'.$un;
    if (isset($changesPerUser[$ind]))
    {
        $crec = $changesPerUser[$ind];
    } else {
        $crec = array("A4Code" => $cinfo['A4Code'], "Name" => $cinfo['Name'], "Year" => $cinfo['Year'], 
                                "User" => $line['dp_value_history_dis_user'], "Changes" => 0); 
    }
    if (isset($cinfo['Pub']) && !is_null($cinfo['Pub']) && trim($cinfo['Pub']) != "" && strcasecmp($cinfo['Pub'], 'Never'))
    {
        if (!isset($cydDPs[$dpc]))
        {
            $cydDPs[$dpc] = 0;
        }
        $cydDPs[$dpc] = $cydDPs[$dpc]+1;
    }
    $crec['Changes'] = $crec['Changes'] + 1 ;
    $changesPerUser[$ind] = $crec;
    $ci2 = array("A4Code" => $cinfo['A4Code'], "Name" => $cinfo['Name'], "Year" => $cinfo['Year']);
    $valarr = $line;
    if (isset($valarr['dp_value_history_his_na']) && $valarr['dp_value_history_his_na'] == 't')
    {
        $valarr['dp_value_history_his_na'] = 'NA';
    } else {
         $valarr['dp_value_history_his_na'] = '';    
    }    
    $v = array_merge($ci2, $valarr);
    fputcsv($fp, $v, ",", '"');
} 
$ideas24->IDEASRequestFree($result);
fclose($fp);

if (!$downloadDetails)
{
    @unlink($fileNames["Details"]);
    $fileNames["Details"] = "No Details to Download";    
}

$runAutomation = getmyOption("automation");

$taskAllDir = $taskSelectedDir = $taskSelectedDirODIMPORT = $OutputDir;
if ($runAutomation)
{
    @mkdir($taskAllDir = $OutputDir."/TaskPublishAll");
    @mkdir($taskSelectedDir = $OutputDir."/TaskPubSelected");
    @mkdir($taskSelectedDirODIMPORT = $OutputDir."/TaskPubSelectedODIMPORT");
}

if ($runStandardPreUpload)
{
    $cydNames2Files = array( 
        "CYDStandardCorrections" => array("file" => $OutputDir."_UPLOAD_Corrections_CYD_3_".$cname.".csv", "cyd" => &$standardCYDCorrections, "auto" => $taskAllDir.DIRECTORY_SEPARATOR."stdCYD.csv", "FullExport" => true),
        "CYDFIScoring" => array("file" => $OutputDir."_UPLOAD_FI_Scoring_CYD_5_".$cname.".csv", "cyd" => &$standardCYDFIRatings, "auto" => $taskAllDir.DIRECTORY_SEPARATOR."fiCYD.csv", "FullExport" => true), 
        "CYDFINonScoring" => array("file" => $OutputDir."_UPLOAD_FI_NonScoring_CYD_6_".$cname.".csv", "cyd" => &$standardCYDFINoImpact, "auto" => $taskSelectedDir.DIRECTORY_SEPARATOR."finoscoreCYD.csv", "FullExport" => true), 
        "CYDODIMPORT" => array("file" => $OutputDir."_UPLOAD_ODIMPORT_".$cname.".csv", "cyd" => &$ODimportDCYD, "auto" => $taskSelectedDirODIMPORT.DIRECTORY_SEPARATOR."odImportCYD.csv", "FullExport" => false)
    );
    
    $title = array("Company name", "Asset4 Code", "Year", "AMP Score", "Is Partial");
    $titleauto = array("Company name", "Asset4 Code", "Year");
    
    $fullExportDone = array();
    
    foreach($cydNames2Files as $k => $vals)
    {
        $fp = fopen($fileNames[$k] = $vals['file'], "w");
        $isFullExport = $vals['FullExport'];   
    
        fputcsv($fp, $title, ",", '"');
        foreach($vals["cyd"] as $aCompYear)
        {
            $t = $aCompYear;  
            
            if (!isset($aCompYear['Pub']) || 0 == strcasecmp($aCompYear['Pub'], 'Never') || 
                $aCompYear['IsPartial'])
            {
                continue;    
            }
            
            
            
            // $t['Pub'] = "D".$t['Pub'];
            $t['Pub'] = $AddInPrefix.'=A4getValue("IR","A4_'.$aCompYear['A4Code'].'",'.$aCompYear['Year'].',,"score")';
            fputcsv($fp, $t, ",", '"');      
        }
        fclose($fp);
        
        if ($runAutomation && count($vals["cyd"]) > 0)
        {
            $fp = fopen($vals['auto'], "w");    
    
            fputcsv($fp, $titleauto, ",", '"');
            foreach($vals["cyd"] as $aCompYear)
            {
                if (!isset($aCompYear['Pub']) || 0 == strcasecmp($aCompYear['Pub'], 'Never')|| 
                    $aCompYear['IsPartial'])
                {
                    continue;    
                }
                $fexk = "A4_".$aCompYear['A4Code'].'_'.$aCompYear['Year'];
                if (array_key_exists($fexk, $fullExportDone))
                {
                    continue;
                }
                $t = array($aCompYear["Name"], $aCompYear["A4Code"], $aCompYear["Year"]);
                if ($isFullExport)
                {
                    $fullExportDone[$fexk] = $t;
                }
                fputcsv($fp, $t, ",", '"');      
            }
            fclose($fp);
        }
    }
    $fp = fopen($fileNames["CYDSubtypeFINonScoring"] = $OutputDir."_UPLOAD_FI_NonScoring_DataPoints_CYD_6_".$cname.".csv", "w");
    
    $title = array("Datapoint Code");
    fputcsv($fp, $title, ",", '"');
    foreach($standardCYDSubTypeFINoImpact as $aDPC => $howm)
    {
        fputcsv($fp, array($aDPC), ",", '"');      
    }
    fclose($fp);
    
    if ($runAutomation)
    {
        $fp = fopen($taskSelectedDir.DIRECTORY_SEPARATOR."DP_Codes.csv", "w");
    
        $title = array("Datapoint Code");
        fputcsv($fp, $title, ",", '"');
        foreach($standardCYDSubTypeFINoImpact as $aDPC => $howm)
        {
            fputcsv($fp, array($aDPC), ",", '"');      
        }
        fclose($fp);
        
       
    }
    
}






$fp = fopen($fileNames["clist"] = $OutputDir."I24_".$cname.".txt", "w");
fprintf($fp, "%s\n", implode(',',$csvclist));

foreach($csvclist as $a4code)
{
    fprintf($fp, "%d\n", $a4code);
}
fclose($fp);

$fp = fopen($fileNames["allCYD"] = $OutputDir."CYD_I24_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "CYD in %s\n", $OutputDir."CYD_I24_".$cname.".txt");
} 
$title = array("Company name", "Asset4 Code", "Year", "Previous Upload");
fputcsv($fp, $title, ",", '"');
foreach($cydlist as $aCompYear)
{
    $t = $aCompYear;  
    if (isset($aCompYear['Pub']) && strcasecmp($aCompYear['Pub'], 'Never'))
    {
        $t['Pub'] = "'".$t['Pub'];
    }
    fputcsv($fp, $t, ",", '"');      
}
fclose($fp);


// cyd for all data points previously uploaded

$fp = fopen($fileNames["prevUpload"] = $OutputDir."All_CYD_CompPrevUploaded_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "CYD of completed and previously uploaded in %s\n", $OutputDir."CYD_I24_PrevUploaded_".$cname.".txt");
} 
$title = array("Company name", "Asset4 Code", "Year", "Upload Date", "A4 Score");
fputcsv($fp, $title, ",", '"');
foreach($cydlist as $aCompYear)
{
    if (isset($aCompYear['Pub']) && strcasecmp($aCompYear['Pub'], 'Never') &&
        !$aCompYear['IsPartial'])
    {
        $t = $aCompYear;
        $t['Pub'] = "'".$t['Pub'];
        $t['A4Formula'] = $AddInPrefix.'=A4getValue("IR","A4_'.$aCompYear['A4Code'].'",'.$aCompYear['Year'].',,"score")'; 
        fputcsv($fp, $t, ",", '"');      
    }
    
}
fclose($fp);

// cyd for EWR changed data points

$fp = fopen($fileNames["prevUploadEWR"] = $OutputDir."EWR_DPs_CYD_CompPrevUploaded_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "CYD of previously uploaded impacting EWR in %s\n", $OutputDir."CYD_I24_EWR_PrevUploaded_".$cname.".txt");
} 
$title = array("Company name", "Asset4 Code", "Year", "Upload Date", "A4 Score");
fputcsv($fp, $title, ",", '"');
foreach($cydlistewr as $aCompYear)
{
    if (isset($aCompYear['Pub']) && strcasecmp($aCompYear['Pub'], 'Never') &&
        !$aCompYear['IsPartial'])
    {
        $t = $aCompYear;
        $t['Pub'] = "'".$t['Pub'];
        $t['A4Formula'] = $AddInPrefix.'=A4getValue("IR","A4_'.$aCompYear['A4Code'].'",'.$aCompYear['Year'].',,"score")'; 
        fputcsv($fp, $t, ",", '"');      
    }
    
}
fclose($fp);


// cyd for data points not in EWR

$fp = fopen($fileNames["prevUploadNonEWR"] = $OutputDir."NO_EWR_DPs_CYD_CompPrevUploaded_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "CYD of previously uploaded not impacting EWR in %s\n", $OutputDir."CYD_I24_DPNotInEWR_PrevUploaded_".$cname.".txt");
} 
$title = array("Company name", "Asset4 Code", "Year", "Upload Date", "A4 Score");
fputcsv($fp, $title, ",", '"');
foreach($cydlistnonewr as $aCompYear)
{
    if (isset($aCompYear['Pub']) && strcasecmp($aCompYear['Pub'], 'Never') &&
        !$aCompYear['IsPartial'])
    {
        $t = $aCompYear;
        $t['Pub'] = "'".$t['Pub'];
        $t['A4Formula'] = $AddInPrefix.'=A4getValue("IR","A4_'.$aCompYear['A4Code'].'",'.$aCompYear['Year'].',,"score")'; 
        fputcsv($fp, $t, ",", '"');      
    }
    
}
fclose($fp);

// $cydDPs        -- only for prev uploaded companies

$fp = fopen($fileNames["CYDDPs"] = $OutputDir."All_DPs_CYDSubtype_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "DPs used in CYD %s\n", $fileNames["CYDDPs"]);
} 
$title = array("Datapoint Code", "Usage Count(Delete column before importing as CYD sub type)");

fputcsv($fp, $title, ",", '"');
foreach($cydDPs as $aDPC => $howm)
{
    fputcsv($fp, array($aDPC, $howm), ",", '"');      
}
fclose($fp);


// $cydDPsEWR        -- DPS used in EWR previously uploaded - only for prev uploaded companies

$fpewr = fopen($fileNames["CYDEWRDPs"] = $OutputDir."EWR_DPs_CYDSubtype_".$cname.".csv", "w");
$fpnoewr = fopen($fileNames["CYD_NoEWR_DPs"] = $OutputDir."NO_EWR_DPs_CYDSubtype_".$cname.".csv", "w");
if ($cli)
{
    fprintf(STDERR, "DPs used in CYD part of EWR %s\n", $fileNames["CYDEWRDPs"]);
    fprintf(STDERR, "DPs used in CYD not part of EWR %s\n", $fileNames["CYD_NoEWR_DPs"]);
} 
$title = array("Datapoint Code", "Usage Count(Delete column before importing as CYD sub type)");

fputcsv($fpewr, $title, ",", '"');
fputcsv($fpnoewr, $title, ",", '"');
foreach($cydDPs as $aDPC => $howm)
{
    if (isset($DPsPartofEWR[$aDPC]))
    {
        fputcsv($fpewr, array($aDPC, $howm), ",", '"');    
    } else {
        fputcsv($fpnoewr, array($aDPC, $howm), ",", '"');
    }
          
}
fclose($fpewr); 
fclose($fpnoewr); 

// user summary


$fp = fopen($fileNames["userSummary"] = $OutputDir."I24_".$cname."_perUserSummary.csv", "w");
if ($cli)
{
    fprintf(STDERR, "Per user summary in %s\n", $OutputDir."I24_".$cname."_perUserSummary.csv");
} 
$title = array("Asset4 Code", "Company name", "Year", "User", "Changes");
fputcsv($fp, $title, ",", '"');
foreach($changesPerUser as $aChange)
{
    fputcsv($fp, $aChange, ",", '"'); 
}
fclose($fp);
 
$ideas24->IDEASRequestClose(); 

?>
