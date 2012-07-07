<?php

namespace GW2CBackend;

class ConfigGenerator {
    
    protected $output;
    
    protected $reference;
    protected $changes;
    
    protected $markerGroups;
    protected $areas;
    protected $resourcesPath;
    
    protected $maxID;
    
    public function __construct($reference, $changes, $markerGroups, $resourcesPath, $areas) {
        
        $this->reference = $reference;
        $this->changes = $changes;
        $this->markerGroups = $markerGroups;
        $this->areas = $areas;
        $this->resourcesPath = $resourcesPath;
        $this->maxID = 0;
    }
    
    public function generate() {
        
        $outputString = "";

        $this->mergeChanges();
        
        $outputString.= $this->generateResourcesOutput();
        $outputString.= $this->generateAreasOutput();
        $outputString.= $this->generateMarkersOutput();

        $this->output = $outputString;
        
        return $this->output;
    }
    
    public function setIDToNewMarkers() {
        
        $this->maxID = self::getMaximumID($this->reference);
        
        foreach($this->changes as $markerType => $markerTypeCollection) {
            
            foreach($markerTypeCollection as $changeID => $change) {

                if($change["status"] == DiffProcessor::STATUS_ADDED && $change["marker"]["id"] == -1) {
                    $this->changes[$markerType][$changeID]["marker"]["id"] = $this->getNewID();
                }
            }
        }
    }
    
    protected function getNewID() {

        return ++$this->maxID;
    }
    
    public function getMaxMarkerID() { return $this->maxID; }
    
    static protected function getMaximumID($collection) {

        $maxID = 0;

        foreach($collection as $markerType => $markerTypeCollection) {
            
            foreach($markerTypeCollection as $markerID => $marker) {
                if($marker["id"] > $maxID) $maxID = $marker["id"];
            }   
        }

        return $maxID;
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
        
        foreach($this->markerGroups as $markerGroup) {

            $outputString.= 'Markers.'.$markerGroup['slug'].' = {'.PHP_EOL;
            $outputString.= "\t".'name : "'.$markerGroup['name'].'",'.PHP_EOL;
            $outputString.= "\t".'markerGroup : ['.PHP_EOL;

            foreach($markerGroup['markerTypes'] as $markerType) {

                $outputString.= "\t\t".'{'.PHP_EOL;
                $outputString.= "\t\t\t".'name : "'.$markerType['name'].'",'.PHP_EOL;
                $outputString.= "\t\t\t".'slug : "'.$markerType['id'].'",'.PHP_EOL;
                $outputString.= "\t\t\t".'markers : ['.PHP_EOL;

                if(array_key_exists($markerType['id'], $this->reference)) {
                    
                    $tab = "\t\t\t\t";
                    foreach($this->reference[$markerType['id']] as $marker) {
                        $outputString.= $tab.'{ "id" : '.$marker['id'].', "lat" : '.$marker['lat'].', "lng" : '.$marker['lng'].', ';
                        $outputString.='"area" : '.$marker["area"].', ';
                        $outputString.='"title" : "'.$marker['title'].'", "desc" : "'.$marker["desc"].'"},'.PHP_EOL;
                    }

                    // remove the last comma
                    $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
                }

                $outputString.= "\t\t\t".']'.PHP_EOL;
                $outputString.= "\t\t".'},'.PHP_EOL;                
            }

            // remove the last comma
            $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
            
            $outputString.= "\t".']'.PHP_EOL.'}'.PHP_EOL;
        }

        return $outputString;
    }
    
    protected function generateResourcesOutput() {

        $outputString = "Resources.Paths = {".PHP_EOL;
        $outputString.= "\t".'"icons": "'.$this->resourcesPath.'"'.PHP_EOL;
        $outputString.="}".PHP_EOL.PHP_EOL;

        $outputString.="Resources.Icons = {".PHP_EOL;

        foreach($this->markerGroups as $markerGroup) {
            foreach($markerGroup['markerTypes'] as $markerType) {
                $outputString.="\t".'"'.$markerType['id'].'" : { ';
                $outputString.='"url" : Resources.Paths.icons + "'.$markerType['filename'].'"},'.PHP_EOL;
            }
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

        if(!array_key_exists($markerType, $this->changes)) return null;

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
