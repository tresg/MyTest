#!/bin/bash



source ~asset4/bin/phpScripts/.bash_profile
source ../globalVars.bash
export chkdate=`date '+%y%m%d'`
export sys=Prod

    
export changedatabase=${databasedir}/Changes4Upload
export archivedir=${changedatabase}/archive
export csvdir=${changedatabase}/csvoutput

if test -d ${csvdir}
then
  rm -rf ${csvdir}
  mkdir -p ${csvdir}
fi


for i in ${changedatabase} ${archivedir} ${csvdir}
do
  if test ! -d "${i}"
  then
    mkdir -p "${i}"
  fi
done


if test ! -d KeepState
then
  mkdir KeepState
fi

export from=


if test -r KeepState/uploadcheckLastRun.bash
then
  . KeepState/uploadcheckLastRun.bash
fi
  
export owhat='Manually collected Data Points only'
export to=Now

while test $# -gt 0
do
    case "$1" in
      -sys) shift; sys="$1"; shift;;
      -from) shift; from="$1"; shift;;
     
      -import) export doimport=true; shift;;
      -noimport) export doimport=false; shift;;
   
      -to) shift; to="$1"; shift;;           
    *) eargs="$eargs $1";shift;;
  esac
done

export stat_sn=`echo $0 | sed -e 's/run//' -e 's/.bash//'`
bash ../scriptStatus.bash CSV_${stat_sn} 1 $0 "`pwd`"  ValueExtraction start Retrieve ${stat_sn} changes


echo 'export from="'`date '+%Y-%m-%d %H:%m'`'"' > KeepState/uploadcheckLastRun.bash

bash ../scriptStatus.bash CSV_${stat_sn} 2 $0 "`pwd`"  FindChanges start Retrieve ${stat_sn} changes


echo php -f getchangesbySups.php \
    RunStandardUploadChecks:RunStandard output:${csvdir} sys:$sys \
    cydtype:published SaveUploadTimeStamp:SaveTimeStamp \
    cli:true vs:all "from:$from" "to:$to"  automation:true > ${csvdir}/changepage.html
    
php -f getchangesbySups.php \
    RunStandardUploadChecks:RunStandard output:${csvdir} sys:$sys \
    cydtype:published SaveUploadTimeStamp:SaveTimeStamp \
    cli:true vs:all "from:$from" "to:$to"  automation:true > ${csvdir}/changepage.html


bash ../scriptStatus.bash CSV_${stat_sn} 2 $0 "`pwd`"  FindChanges done Retrieve ${stat_sn} changes

bash ../scriptStatus.bash CSV_${stat_sn} 3 $0 "`pwd`"  CreateTask start Create Task for ${stat_sn}



pushd ../autoimport

if test ! -z "${changescsvimporter}"
then
  export csvimporter="${changescsvimporter}"
fi

if test ! -z "${changescsvcontacts}"
then
  export csvcontacts="${changescsvcontacts}"
fi


php -f csvImport.php sys:${sys} cli:true importer:"${csvimporter}" contacts:"${csvcontacts}" \
    "subject:$HOSTNAME: Non scoring changes since last Upload (PubSelected Task) #S #C" \
  input:${csvdir}/TaskPubSelected filetype:cyd,data \
  submit:upload actions:task
  
  
php -f csvImport.php sys:${sys} cli:true importer:"${csvimporter}" contacts:"${csvcontacts}" \
    "subject:$HOSTNAME: Scoring changes since last Upload (PubFull Task) #S #C" \
  input:${csvdir}/TaskPublishAll filetype:cyd \
  submit:upload actions:task

php -f csvImport.php sys:${sys} cli:true importer:"${csvimporter}" contacts:"${csvcontacts}" \
    "subject:$HOSTNAME: O&DImport Upload (Published Selected CYD/Task) #S #C" \
    input:${csvdir}/TaskPubSelectedODIMPORT filetype:csv,cyd \
    submit:upload actions:task importsubtype:"O&amp;D Data Points" cydtype:published
  
popd
bash ../scriptStatus.bash CSV_${stat_sn} 3 $0 "`pwd`"  CreateTask done Create Task for ${stat_sn}

pushd ${changedatabase}
tar cvf archive/${chkdate}_archive.tar csvoutput
popd

bash ../scriptStatus.bash CSV_${stat_sn} 1 $0 "`pwd`"  ValueExtraction done Retrieve ${stat_sn} changes


