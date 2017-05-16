<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(E_ERROR | E_PARSE);

class OrgsByOKVED
{
    private $code;
    private $provider;
    private $orgs = array();
    
    private $timeStats = array();
    
    function __construct (string $code, string $provider) {
        $this->provider = $provider;
        $this->setOKVED($code);
    }
    
    public function setOKVED (string $code) {
        $this->code = $code;
        $this->orgs = array ();
        
        $pageNum = 1;
        while ($this->extractPage($pageNum)) {
            $pageNum++;
        }
    }
    
    public function getOrgs () {
        return $this->orgs;
    }
    
    public function getOrgsCSV ($orgCSVFile, $header = array('INN','Name','Region','Address','OGRN')) {
        fputcsv($orgCSVFile, $header, ';', '"');

        foreach ($this->orgs as $org) {
            $line = array();
            
            foreach ($header as $field) {
                $line[] = mb_convert_encoding($org[$field],'CP1251');
            }
            
            if ($line) {
                fputcsv($orgCSVFile, $line, ';', '"');
            }
        }
    }

    public function getTimeStats() {
        return $this->timeStats;
    }

    private function extractPage ($pageNum, $slowdown = true) {
        $startTime = microtime();
        
        $sourceURL = $this->provider . '/'. $this->code . ((1==$pageNum) ? '' : '/' . $pageNum);
        
        if ($page = file_get_contents($sourceURL)) {
            $dom = new DOMDocument('1.0','UTF-8');
            $dom->loadHTML('<?xml encoding="UTF-8">'.$page);

            $finder = new DomXPath($dom);

            $nodes = $finder->query("//ul[@class='unitlist']/li");
            $added = 0;

            if (!is_null($nodes)) {
                foreach ($nodes as $node) {
                    $subNodes = $node->childNodes;
                    $org = array();

                    foreach ($subNodes as $subNode) {
                        if (XML_ELEMENT_NODE === $subNode->nodeType) {
                            $subNodes = $subNode->childNodes;
                            foreach ($subNodes as $subNode) {
                                if (XML_ELEMENT_NODE === $subNode->nodeType) {
                                    $subNodes = $subNode->childNodes;
                                    foreach ($subNodes as $subNode) {
                                        if (XML_ELEMENT_NODE === $subNode->nodeType) {
                                            switch ($subNode->getAttribute('class')) {
                                                case 'u-name':
                                                    $org["Name"] = str_replace('"','',$subNode->textContent);
                                                    break;
                                                case 'u-address':
                                                    $org["Address"] = $subNode->textContent;
                                                    break;                       
                                                case 'u-reqline':
                                                    switch(mb_substr($subNode->textContent,0,3)) {
                                                        case 'ИНН':
                                                            $org["INN"] = mb_substr($subNode->textContent,4);
                                                            break;
                                                        case 'ОГР':
                                                            $org["OGRN"] = mb_substr($subNode->textContent,5);
                                                            break;                                                    
                                                    }
                                                    break;   
                                            }
                                        }
                                        else {
                                            if ('u-region' == $subNode->parentNode->getAttribute('class')) {
                                                $org["Region"] = $subNode->parentNode->textContent;
                                            }
                                        }
                                    }
                                }
                            }
                        }   
                    }
                    if ($org) {
                        $this->orgs[count($this->orgs)] = $org;
                        $added++;
                    }
                }
            }  
            
            $this->timeStats[] = microtime() - $startTime;
                       
            if ($slowdown) {
                sleep(random_int(5, 8));
            }
            return $added; 
        }
        else {
            return 0;
        }    

        
    }
}

?>