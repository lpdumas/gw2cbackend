<?php

namespace GW2CBackend;

class ConfigGenerator {
    
    protected $output;
    
    protected $reference;
    protected $changes;
    
    protected $resources;
    protected $areas;
    protected $resourcesPath;    
    
    public function __construct($reference, $changes, $resources, $resourcesPath, $areas) {
        
        $this->reference = $reference;
        $this->changes = $changes;
        $this->resources = $resources;
        $this->areas = $areas;
        $this->resourcesPath = $resourcesPath;
    }
    
    public function generate() {
        
        $outputString = "";

        $this->mergeChanges();
        
        $outputString.= $this->generateResourcesOutput();
        $outputString.= $this->generateAreasOutput();
        $outputString.= $this->generateMarkersOutput();
        
        

        $this->output = $outputString;
    }
    
    protected function mergeChanges() {

        foreach($this->reference as $markerType => $markerTypeCollection) {
            
            foreach($markerTypeCollection as $markerID => $marker) {

                $change = $this->getMarkerInChangesByID($marker["id"], $markerType);

                if($change != null) {

                    switch($change["status"]) {
                        case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                        case DiffProcessor::STATUS_MODIFIED_DATA:
                        case DiffProcessor::STATUS_MODIFIED_ALL:
                            $this->reference[$markerType][$markerID] = $change["marker"];
                            break;
                        case DiffProcessor::STATUS_REMOVED:
                            unset($this->reference[$markerType][$markerID]);
                            $this->reference[$markerType] = array_values($this->reference[$markerType]);
                            break;
                    }
                    
                    // we remove the items so there is only the remainings items with the "STATUS_ADDED" status
                    $id = array_search($change, $this->changes[$markerType]);
                    unset($this->changes[$markerType][$id]);
                    $this->changes[$markerType] = array_values($this->changes[$markerType]);
                }
            }
        }

        // the remainings items are the "STATUS_ADDED" one
        foreach($this->changes as $markerType => $markerTypeCollection) {
            foreach($markerTypeCollection as $change) {
                $this->reference[$markerType][] = $change["marker"];
            }            
        }
    }
    
    protected function generateMarkersOutput() {
        
        $outputString = "";
        
        foreach($this->reference as $markerType => $markerTypeCollection) {
            
            $outputString.="Markers.".$markerType." = [".PHP_EOL;
            
            foreach($markerTypeCollection as $marker) {
                
                $outputString.="\t".'{ "id" : '.$marker['id'].', "lat" : '.$marker['lat'].', "lng" : '.$marker['lng'].', ';
                $outputString.='"area" : '.$marker["area"].', ';
                $outputString.='"title" : "'.$marker['title'].'", "desc" : "'.$marker["desc"].'"},'.PHP_EOL;
            }
            
            // remove the last comma
            $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
            
            $outputString.="]".PHP_EOL;
        }
        
        return $outputString;
    }
    
    protected function generateResourcesOutput() {

        $outputString = "Resources.Paths = {".PHP_EOL;
        $outputString.= "\t".'"icons": "'.$this->resourcesPath.'"'.PHP_EOL;
        $outputString.="}".PHP_EOL.PHP_EOL;

        $outputString.="Resources.Icons = {".PHP_EOL;

        foreach($this->resources as $r) {
            $outputString.="\t".'"'.$r['id'].'" : { "label" : "'.$r['label'].'", ';
            $outputString.='"url" : Resources.Paths.icons + "'.$r['filename'].'"},'.PHP_EOL;
        }

        $outputString.="}".PHP_EOL.PHP_EOL;

        return $outputString;
    }
    
    protected function generateAreasOutput() {
        
        $outputString = "Areas = [".PHP_EOL;
        
        foreach($this->areas as $a) {
            $summary = $this->getAreaSummary($a["id"]);
            $outputString.= "\t".$this->generateOneAreaOutput($a["id"], $a["name"], $a["rangeLvl"], $summary,
                                                              $a["neLat"], $a["neLng"], $a["swLat"], $a["swLng"]).",".PHP_EOL;
        }

        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
        $outputString.= "]".PHP_EOL.PHP_EOL;
         
         return $outputString;
    }
    
    protected function getAreaSummary($idArea) {
        
        $summary = array("hearts" => 0, "waypoints" => 0, "skillpoints" => 0, "poi" => 0, "dungeons" => 0);
            
        foreach($this->reference as $markerType => $markerTypeCollection) {
            
            foreach($markerTypeCollection as $marker) {
                if($marker["area"] == $idArea) {
                    $summary[$markerType]++;
                }
            }
        }
        
        return $summary;
    }
    
    protected function generateOneAreaOutput($id, $name, $rangeLvl, $summary, $neLat, $neLng, $swLat, $swLng) {
        
        $outputString = '{ id : '.$id.', name : "'.$name.'", rangeLvl : "'.$rangeLvl.'",'.PHP_EOL;
        
        $outputString.= "\t\t".'summary : {'.PHP_EOL;
        foreach($summary as $markerType => $value) {
            $outputString.= "\t\t\t".'"'.$markerType.'" : '.$value.','.PHP_EOL;
        }
        
        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL."\t\t},".PHP_EOL;
        $outputString.= "\t\t".'neLat : '.$neLat.', neLng : '.$neLng.', swLat : '.$swLat.', swLng : '.$swLng.''.PHP_EOL;
        $outputString.= "\t".'}';
        
        return $outputString;
    }
    
    public function save($pathOutput, $minimized) {

        $file = fopen($pathOutput, "w+");
        
        if($minimized) {
            // we remove all the spaces
            $outputString = preg_replace("#[\t\n ]#", "",$this->output);
        }
        else {
            $outputString = $this->output;
        }
        
        fwrite($file, $outputString);
        fclose($file);
    }
    
    protected function getMarkerInChangesByID($markerID, $markerType) {

        foreach($this->changes[$markerType] as $change) {

            if((array_key_exists("marker", $change) && $change["marker"]["id"] == $markerID) || 
                (array_key_exists("marker-reference", $change) && $change["marker-reference"]["id"] == $markerID)) {

                return $change;
            }
        }

        return null;
    }
    
    public function getReference() {
        return $this->reference;
    }
}
