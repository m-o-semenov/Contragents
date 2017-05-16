<?php
include 'OrgsByOKVED.php';
include 'OrgsDetailed.php';

set_time_limit(0);
    
if ('runOKVED' == $_GET['act']) {
    $orgList = new OrgsByOKVED(str_pad(str_replace('.','',$_GET['OKVED']),6,'0'), 'http://www.rusprofile.ru/codes');
    
   
    file_put_contents('lastStats.txt', "Max: " . max($orgList->getTimeStats()));
    file_put_contents('lastStats.txt', "Min: " . min($orgList->getTimeStats()), FILE_APPEND);
    file_put_contents('lastStats.txt', "Average: " . array_sum($orgList->getTimeStats())/count($orgList->getTimeStats()), FILE_APPEND);
    
    $workfilePath = 'results_OKVED.csv';
    $orgCSVFile = fopen($workfilePath, 'w+');
    
    if ($orgCSVFile) {
        $orgList->getOrgsCSV($orgCSVFile);
    }
    
    fclose($orgCSVFile);
    
    if (file_exists($workfilePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($workfilePath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($workfilePath));
        readfile($workfilePath);
    }
    else {
        echo "No result file: ". $workfilePath;
    }
} 
elseif ('runSBIS' == $_POST['runSBIS']) {
    echo $_POST['act'];
    
    if (UPLOAD_ERR_OK == $_FILES['innFile']['error']) {
        $orgDetail = new OrgsDetailed(array_map('trim', file('propList.txt')),file($_FILES['innFile']['tmp_name']),'https://sbis.ru/contragents/');
        
        $workfilePath = 'results_SBIS.csv';
        $orgCSVFile = fopen($workfilePath, 'w+');

        if ($orgCSVFile) {
            $orgDetail->getOrgsDetailedCSV($orgCSVFile);
        }

        fclose($orgCSVFile);

        if (file_exists($workfilePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($workfilePath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($workfilePath));
            readfile($workfilePath);
        }
        else {
            echo "No result file: ". $workfilePath;
        }
    }
}
else {
    echo $_GET['act'];
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <form action="index.php" method="get">
            <input type="hidden" name="runOKVED" value="runOKVED">
            <h2>Загрузка списка организаций по коду ОКВЭД</h2>
            Код ОКВЭД: <input type="text" name = "OKVED">
            <input type="submit" value="Загрузить">
        </form>
        
        <form enctype="multipart/form-data" action="index.php" method="post">
            <input type="hidden" name="runSBIS" value="runSBIS">
            <h2>Загрузка детальной информации по списку ИНН</h2>
            Файл со списком ИНН: <input type="file" name="innFile" />
            <input type="submit" value="Загрузить">
        </form>
    </body>
</html>
<?php    
}
?>