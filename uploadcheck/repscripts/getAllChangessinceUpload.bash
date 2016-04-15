#!/bin/bash

options needed
from
sys
SaveUploadTimeStamp=true
DetailedChanges=false

1) Keep time stamp locally - retrieve from KeepState/uploadcheckLastRun.serialized
a:1:{s:4:"Prod";s:16:"2011-08-22 13:00";}
2) 

RunStandardUploadChecks=RunStandard

will set all this ...
putenv("nonews=true");
    putenv("noatd=true");
    putenv("noco2estimates=true");
    putenv("exportable=true");
    putenv("DetailedChanges=false");
    putenv("PrefixAddInFormulas=true");
    putenv("DPstoCheck=");
    putenv("includeFI=true");
    putenv("onlyFI=false");


    
Publish Complete company:
_UPLOAD_Corrections_CYD_3__Std_Upload_Checks-110822-131605.csv
_UPLOAD_FI_Scoring_CYD_5__Std_Upload_Checks-110822-131605.csv

Publish Selected
_UPLOAD_FI_NonScoring_CYD_6__Std_Upload_Checks-110822-131605.csv
_UPLOAD_FI_NonScoring_DataPoints_CYD_6__Std_Upload_Checks-110822-131605.csv
   
    
Complete Results of Standard Pre Upload Check 
Comment CYD ID CYD Name 
Company Years with Corrections CYD ID 3 _UPLOAD_Corrections 
Company Years with FI Changes impacting Scores CYD ID 5 _UPLOAD_FI_Scoring 
Company Years with FI Changes NOT impacting Scores CYD ID 6 _UPLOAD_FI_NonScoring 
Comment DPs for CYD ID CYD SubType  Name 
Data Points List for CYD Subtype DP List for CYD ID 6 _UPLOAD_FI_NonScoring_DP_Subtype 

export odir=`date +%F`
export sys=ProdDaily
export inews=

. getDates.bash

export includeFI=false
export onlyFI=false
export onlyExportable=
export owhat='Manually collected Data Points only'

while test $# -gt 0
do
    case "$1" in
      -sys) shift; sys="$1"; shift;;
      -out*) shift; odir="$1"; shift;;
      -exportable)  onlyExportable="exportable:true"; shift;;
      -all)  onlyExportable="exportable:false"; shift;;
      -exp*) shift; onlyExportable="exportable:$1"; shift;;
      -from) shift; from="from:$1"; shift;;
      -news*) export inews="nonews:false"; shift;;
      -nonews*) export inews="nonews:true"; shift;;
      -recent*) export inews="nonews:false"; shift;;
      -norecent*) export inews="nonews:true"; shift;;
      -import) export doimport=true; shift;;
      -noimport) export doimport=false; shift;;
      -fi*) shift;includeFI=true;owhat='Manually collected and Thomson Data Points ';;
      -onlyfi) shift;onlyFI=true;owhat='Only Thomson Data Points ';shift;;
      -thom*) shift;includeFI=true;owhat='Manually collected and Thomson Data Points ';;
    
      -to) shift; to="to:$1"; shift;;           
    *) eargs="$eargs $1";shift;;
  esac
done

echo Changes for all users since $from up to $to - $owhat

if test ! -d "ChangesSinceLastUpload/${odir}"
then
  echo Creating directory "ChangesSinceLastUpload/${odir}"
  mkdir -p "ChangesSinceLastUpload/${odir}"
fi 

echo php -f retrieveIDEAS24historyanswers.php \
	$inews $onlyExportable output:ChangesSinceLastUpload/${odir} sys:$sys  \
    cli:true vs:all "$from" "$to" includeFI:$includeFI onlyFI:$onlyFI


php -f retrieveIDEAS24historyanswers.php \
    $inews $onlyExportable output:ChangesSinceLastUpload/${odir} sys:$sys  \
    cli:true vs:all "$from" "$to" includeFI:$includeFI onlyFI:$onlyFI automation$doimport


echo Output in ChangesSinceLastUpload/${odir}
pushd ChangesSinceLastUpload/${odir}

start.bash .

popd


