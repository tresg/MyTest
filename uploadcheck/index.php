<?php

$ip = array("../common/basefiles", "../common"); 
$ipath = implode(PATH_SEPARATOR, $ip);
set_include_path(get_include_path().PATH_SEPARATOR.$ipath); 

require_once("session_include_top.php"); 
?>
<!--START HEADER  -->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<!-- <meta http-equiv="Content-Type" content="text/html; charset=windows-1252"> -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<meta http-equiv="Content-Language" content="en-us">
<meta name="GENERATOR" content="Microsoft FrontPage 6.0">
<title>
IDEAS24 - Changes by User/Period
</title>


<link rel="stylesheet" type="text/css" href="../common/a4send.css">
<script type="text/javascript" src="../common/GUI/datepicker/date-picker.js"></script>
<script type="text/javascript">
<!--

<?php
$nl = "\n";  
$ip = "../common"; 
set_include_path(get_include_path() . PATH_SEPARATOR . $ip);
require_once("UtilLib.php");  
$KeepStateLastUploadCheck = array();

$cnt = getInitFiles($MenuItems);
if (!is_dir("KeepState"))
{
    @mkdir("KeepState");
    @chmod("KeepState", 0777);
}
define("KEEPSTATETIMESTAMPS", "KeepState/uploadcheckLastRun.serialized");
$KeepStateLastUploadCheck = array();
if (file_exists(KEEPSTATETIMESTAMPS))
{
    $s = implode("", file(KEEPSTATETIMESTAMPS));
    $KeepStateLastUploadCheck = unserialize($s);
}
$ldesc = array();   
$vals = array();
$keys = array();
$ctr = 1;
foreach($MenuItems as $k => $mi)
{
    if (isset($KeepStateLastUploadCheck[$mi['Config']]))
    {
        $vals[] = '"'.$KeepStateLastUploadCheck[$mi['Config']].'"' ;      
    } else {
        $vals[] = '"'.date("Y-m-d", time()-($ctr+14)*24*2400).'"';    
    }
    $ctr++;
    $keys[] = '"'.$mi['Config'].'"' ;
    $ldesc[] = '"'.str_replace('"', "", $mi['Desc']).'"';      
}

foreach($keys as $k => $conf)
{
    if (!isset($KeepStateLastUploadCheck[trim($conf, '"')]) && isset($vals[$k]))
    {
        $KeepStateLastUploadCheck[trim($conf, '"')] =  trim($vals[$k], '"');   
    }
}
echo 'globalTimeStamp = new Array ('.implode(',', $vals).') ;';
echo 'globalI24Systems = new Array ('.implode(',', $keys).') ;';
echo 'globalI24SystemsDesc = new Array ('.implode(',', $ldesc).') ;'; 
/*
$s = serialize($KeepStateLastUploadCheck);
@unlink(KEEPSTATETIMESTAMPS);
$fps = fopen(KEEPSTATETIMESTAMPS, "w");
fwrite($fps, $s);
fclose($fps);
@chmod(KEEPSTATETIMESTAMPS, 0777);
*/

?>

function ChangeFromDate()
{                       
    var tsfrom = document.getElementById("from");
    var tsfromstd = document.getElementById("fromstd"); 
    
    tsfromstd.value = tsfrom.value;      
}

function ChangeSystem()
{
    var i, j;
    var tsfrom = document.getElementById("from");
    var tssysdesc = document.getElementById("sysname");  

   
    var selSystem = document.getElementById("sys");
    for(i=0;i < selSystem.options.length; i++)
    {
        if (selSystem.options[i].selected)
        {
            sys = selSystem.options[i].value;
            for(j = 0; j < globalI24Systems.length; j++)
            {
                if (globalI24Systems[j] == sys)
                {
                    if (j < globalTimeStamp.length)
                    {
                        tsfrom.value = globalTimeStamp[j];
                        tssysdesc.value = globalI24SystemsDesc[j];
                    }
                }
            }
        }    
    }   
    
}

function ChangeStdCheck()
{
	var i, j;
	var selUsr = document.getElementById("RunStandardUploadChecks");
    var tsfrom = document.getElementById("from");
    var tsfromstd = document.getElementById("fromstd"); 

	var doDeActivate = true;
	// MakeYourOwnChoice RunStandard RunSetStandard
	
	for(i=0;i < selUsr.options.length; i++)
	{
		if (selUsr.options[i].selected)
		{
			if (selUsr.options[i].value == 'MakeYourOwnChoice')
			{
				doDeActivate = false;
			}
		}	
	}
	
    if (doDeActivate == true)
    {
        var selSystem = document.getElementById("sys");
        for(i=0;i < selSystem.options.length; i++)
        {
            if (selSystem.options[i].selected)
            {
                sys = selSystem.options[i].value;
                for(j = 0; j < globalI24Systems.length; j++)
                {
                    if (globalI24Systems[j] == sys)
                    {
                        if (j < globalTimeStamp.length)
                        {
                            tsfrom.value = globalTimeStamp[j];
                            tsfromstd.value = globalTimeStamp[j];
                        }
                    }
                }
            }    
        }    
    }
    
    if (doDeActivate == true)
    {
        document.getElementById("includeFI").options[1].selected = true;
        document.getElementById("exportable").options[0].selected = true;  
        document.getElementById("nonews").options[0].selected = true;  
        document.getElementById("noatd").options[0].selected = true;
        document.getElementById("noco2estimates").options[0].selected = true;
        // document.getElementById("ExcludeDPs").value = "";
        document.getElementById("DPstoCheck").value = ""; 
        document.getElementById("DetailedChanges").checked = false;
        document.getElementById("PrefixAddInFormulas").checked = true;
        document.getElementById("to").value = "Now";
        document.getElementById("SaveUploadTimeStamp").checked = false;   
    }
	
	document.getElementById("i24usersArg").disabled=doDeActivate ;
	document.getElementById("i24userlist").disabled=doDeActivate ;
	// document.getElementById("from").disabled=doDeActivate ;
	document.getElementById("SelDate2").disabled=doDeActivate ;
	document.getElementById("to").disabled=doDeActivate ;
	document.getElementById("SelDate").disabled=doDeActivate ;
	document.getElementById("includeFI").disabled=doDeActivate ;
	document.getElementById("exportable").disabled=doDeActivate ;
	document.getElementById("nonews").disabled=doDeActivate ;
	document.getElementById("noatd").disabled=doDeActivate ;
	document.getElementById("noco2estimates").disabled=doDeActivate ;
	document.getElementById("DetailedChanges").disabled=doDeActivate ;
	document.getElementById("PrefixAddInFormulas").disabled=doDeActivate ;
	// document.getElementById("ExcludeDPs").disabled=doDeActivate ;
	document.getElementById("DPstoCheck").disabled=doDeActivate ;
	
	document.getElementById("SaveUploadTimeStamp").disabled= !doDeActivate ;
    
    document.getElementById("from").disabled=false ; 
	
}





function ChangeUser()
{
	var i;
	var selUsr = document.getElementById("i24usersArg");
	var usrList = document.getElementById("i24userlist");
	
	for(i=0;i < selUsr.options.length; i++)
	{
		if (selUsr.options[i].selected)
		{
			if (selUsr.options[i].value == 'Sups')
			{
				usrList.value = "ARMOOGUM,MALLIAH,MOTEE,OSTOOR,REDDY,BAHADOOR,MOHADEWO";	
			}
			if (selUsr.options[i].value == 'All')
			{
				usrList.value = "";	
			}
		}	
	}
}

</script>

</head>
<body topmargin="10" leftmargin="50" rightmargin="10" bottommargin="10" marginwidth="10" marginheight="10" >

<table border="0" id="TitleTable" cellspacing="0" cellpadding="0" style="width: 100%">
	<tr>
		<td width="553">
		<h1>Changes by Users and Date-Period</h1></td>
		<td align="right">
		<img border="0" src="../common/logo.gif" width="55" height="55" align="right" hspace="0"></td>
	</tr>
</table><br clear="all">
<form action="getchangesbySups.php" method="POST" name="settings_form">
	<table border="1"  id="DoIt" style="width: 100%" cellspacing="3" cellpadding="2">
		<thead>
			<tr>
<?php



echo '<th style="text-align: right; vertical-align:baseline" width="310">Generate Report for IDEAS24:</th><th><select size="1" name="sys" id="sys" onchange="ChangeSystem()" class="subtitle">'.$nl;
if ($cnt == 0)
{
    echo '<option value="Test">ideas24.asset4.com/ideas-test/</option> '. 
           '<option value="Test2">ideas24.asset4.com/test/</option> '. 
            '<option selected value="Prod">Production</option>';
    echo '</select>&nbsp;System</th>'.$nl; 
} else {
     foreach($MenuItems as $k => $mi)
    {
        echo '<option value="'.$mi["Config"].'">'.$mi['Desc'].'</option>'.$nl;
    }
    
    echo '</select>&nbsp;System</th>'.$nl;
    
    
    
}
			

?>
		</tr>	
		</thead>
		<tr><th style="text-align: right; vertical-align:baseline" width="310">
			<font face="Verdana" size="2">Standard Pre Upload Checks: </font>
			</th><th style="vertical-align: baseline"><font face="Verdana">
			<font size="1">
			<select size="1" name="RunStandardUploadChecks" id="RunStandardUploadChecks" onchange="ChangeStdCheck()" style="font-family: Verdana; font-size: 8pt">
			<option selected value="MakeYourOwnChoice">Define Customized 
			Settings Below
			</option>
			<option value="RunStandard">Run Standard Check since Last Upload
			</option>
			</select></font> <font size="2">Save timestamp
			<input type="checkbox" name="SaveUploadTimeStamp" id="SaveUploadTimeStamp" value="SaveTimeStamp"   disabled="true"  checked></font></font></th></tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310">
			<font size="2" face="Verdana">IDEAS24 user ids to check:
			</font>
			</th><th style="vertical-align: baseline"><font face="Verdana"><font size="2">
			<select size="1" name="i24usersArg" id="i24usersArg" onchange="ChangeUser()" style="font-family: Verdana; font-size: 8pt">
			<option value="Sups">SQEs</option>
			<option value="UserList">User List</option>
			<option selected value="All">All</option>
			
			</select></font>
			</font>
			<input type="text" name="i24userlist" id="i24userlist" size="97" value="" style="font-family: Verdana; font-size: 8pt"></th></tr>
		<tr>
			<th width="310" style="text-align: right; vertical-align:baseline"><font face="Verdana" size="2">
			Find changes between:&nbsp;&nbsp;
			</font>
			</th>
			<th width="1117" style="vertical-align: baseline">
			<input type="text" name="from" id="from" onchange="ChangeFromDate()" size="20" style="font-family: Verdana; font-size: 8pt"
<?php


    $m = intval(date("m"));
    $y = intval(date("Y"));
    /**
    if (--$m <= 0)
    {
        $m = 12;
        $y--;
    }
    **/
    echo 'value="'.sprintf("%04d-%02d-01", $y, $m).'"';    


?>
            
            
            
            >&nbsp;<input type="button" onClick="SelectDateUI('from')" value="Select From Date" name="SelDate2" id="SelDate2" style="font-family: Verdana; font-size: 8pt">&nbsp; 
			<font size="2" face="Verdana">and (including)&nbsp;</font>
			<input type="text" id="to" name="to" size="20" style="font-family: Verdana; font-size: 8pt" 
<?php

$m = intval(date("m"));
$y = intval(date("Y"));
$ts = strtotime(date("Y-M-01"))-(24*3600);
$ts = time();

echo 'value="'.date("Y-m-d",$ts).'"';
?>

 >&nbsp;<input type="button" onClick="SelectDateUI('to')" value="Select To Date" name="SelDate" id="SelDate" style="font-family: Verdana; font-size: 8pt">
            <input type="hidden" name="fromstd" id="fromstd"  size="40"><input type="hidden" name="sysname" id="sysname"  size="80"></th>
		</tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310"><font face="Verdana" size="2">
			Options: </font>
			</th><th style="vertical-align: baseline">
			<select size="1" name="includeFI" id="includeFI" style="font-family: Verdana; font-size: 8pt">
			<option selected value="false">Manually Gathered DataPoints only</option>
			<option value="true">Manually Gathered, FI and Thomson DataPoints</option>
            <option value="FIOnly">FI and Thomson DataPoints Only</option>
			<option value="rating">Data Points part of EWR</option>
			</select> &nbsp;<select size="1" id="exportable" name="exportable" style="font-family: Verdana; font-size: 8pt">
			<option selected value="true">Only DataPoints marked exportable</option>
			<option value="false">All DataPoints (exportable and no exportable)
			</option>
			</select>&nbsp;&nbsp;&nbsp;</th></tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310">&nbsp;</th><th style="vertical-align: baseline"><select size="1" name="nonews" id="nonews" style="font-family: Verdana; font-size: 8pt">
			<option selected value="true">Exclude Recent Controversy DataPoints</option>
			<option value="false">Include Recent Controversy DataPoints</option>
			</select><select size="1" name="noatd" id="noatd" style="font-family: Verdana; font-size: 8pt">
			<option selected value="true">Exclude ATD Copied DataPoints</option>
			<option value="false">Include ATD Copied DataPoints</option>
			</select><select size="1" name="noco2estimates" id="noco2estimates" style="font-family: Verdana; font-size: 8pt">
			<option selected value="true">Exclude CO2 Estimate DataPoints</option>
			<option value="false">Include CO2 Estimate DataPoints</option>
			</select></th></tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310"><font face="Verdana" size="2">
			Download detailed Value Changes: </font></th><th style="vertical-align: baseline"><font face="Verdana"><input type="checkbox" name="DetailedChanges" id="DetailedChanges" value="true"></font></th></tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310">
			<font face="Verdana" size="2">Prefix &#39;add-in&#39; Formulas: </font></th><th style="vertical-align: baseline"><font face="Verdana">
			<input type="checkbox" name="PrefixAddInFormulas" id="PrefixAddInFormulas" value="true"></font></th></tr>

		<tr><th style="text-align: right; vertical-align:baseline" width="310"><font face="Verdana" size="2">
			Exclude DP from check:</font></th><th style="vertical-align: baseline">
			<input type="text" name="ExcludeDPs" id="ExcludeDPs" size="110" value="" style="font-family: Verdana; font-size: 8pt"></th></tr>
		<tr><th style="text-align: right; vertical-align:baseline" width="310"><font face="Verdana" size="2">
			Check only these DPs: </font></th><th style="vertical-align: baseline">
			<input type="text" name="DPstoCheck" id="DPstoCheck" size="110" value="" style="font-family: Verdana; font-size: 8pt"></th></tr>
		

		<tr>
			<th colspan="2" >
			<p align="center">&nbsp;<input id="makereport" type="submit" value="Get changes" style="font-family: Verdana; font-size: 8pt; font-weight: bold"></th>
		</tr>
	</table><br clear="all">
</form><br clear="all">
<br clear="all"><p>&nbsp;</p>
<p>&nbsp;</p>


</body>
</html>
