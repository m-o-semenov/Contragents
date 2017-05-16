<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class OrgsDetailed {
    private $provider = 'https://sbis.ru/contragents/';
    private $orgsDetailed = array();
    private $propList = array();
    
    public function __construct($propList, $innList, $provider) {
        $this->propList = $propList;
        $this->provider = $provider;
        
        $this->loadOrgs($innList);
    }

    public function loadOrgs ($innList, $slowdown = true) {
        $orgsAdded = 0;
        $failedList = array();
        
        foreach ($innList as $inn) {
            $orgURL = trim($this->provider . $inn);
            
            if ($page = file_get_contents($orgURL)) {
                $dom = new DOMDocument('1.0','UTF-8');
                $dom->loadHTML('<?xml encoding="UTF-8">'.$page);
                $finder = new DomXPath($dom);
                $nodes = $finder->query("//script");

                if (!is_null($nodes)) {
                    foreach ($nodes as $node) {
                        $nodes = $node->childNodes;

                        foreach ($nodes as $node) {
                            $startText='"rec":{';

                            if ($pos = strpos($node->textContent,$startText))
                            {
                                if ($org = $this->parseRecord(substr(str_replace('\\\\"', "'", $node->textContent),$pos+strlen($startText)))) {
                                    $this->addOrg($inn,$org);
                                    $orgsAdded++;                                          
                                }
                            }
                        }
                    }
                }  
                else {
                    $failedList[$inn] = E_PARSE;
                }
            }
            else {
                $failedList[$inn] = E_ERROR;
            }
            
            if ($slowdown) {
                sleep(random_int(5, 8));
            }
        }
    }
    
    public function getOrgs() {
        return $this->$orgsDetailed;
    }
    
    public function getOrgsDetailedCSV ($orgCSVFile, $header = array()) {
        if (!$header) {
            $header = $this->propList;
        }
               
        $line = array();

        foreach ($header as $field) {
            $line[] = mb_convert_encoding($field,'CP1251');
        }

        if ($line) {
            fputcsv($orgCSVFile, $line, ';', '"');
        }

        foreach ($this->orgsDetailed as $org) {
            $line = array();

            foreach ($header as $field) {
                $line[] = mb_convert_encoding($org[$field],'CP1251');
            }

            if ($line) {
                fputcsv($orgCSVFile, $line, ';', '"');
            }
        }
    }
    
    private function addOrg($inn, $org) {
        $innKey = $inn;
        $i = 1;

        while(key_exists($innKey,$this->orgsDetailed)){
            $innKey = $inn . "($i)";
            $i++;
        }

        $this->orgsDetailed[$innKey] = $org;
    }
    
    private function parseRecord ($record) {
        $propReaded = 0;

        foreach (explode(',"',$record) as $propDeclaration) {
            $propDeclaration = str_replace(array('"',';'), '', $propDeclaration);

            $propValue = explode(':',$propDeclaration);

            if (in_array($propValue[0],$this->propList) && !key_exists($propValue[0], $org)) {
                $org[$propValue[0]] = $propValue[1];
                $propReaded++;
            }
        }
        
        if ($propReaded) {
            return $org;
        }
        else {
            return array();
        }
    }
}