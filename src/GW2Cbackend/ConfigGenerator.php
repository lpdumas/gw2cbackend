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

        $this->setIDToNewMarkers();
        $this->mergeChanges();
        
        $outputString.= $this->generateResourcesOutput();
        $outputString.= $this->generateAreasOutput();
        $outputString.= $this->generateMarkersOutput();

        $this->output = $outputString;
        
        return $this->output;
    }
    
    public function setIDToNewMarkers() {
        
        $this->maxID = self::getMaximumID($this->reference);

        foreach($this->changes as $markerGroupID => $markerGroup) {
            
            foreach($markerGroup as $markerTypeID => $markerType) {
                
                foreach($markerType as $changeID => $change) {

                    if($change["status"] == DiffProcessor::STATUS_ADDED && $change["marker"]["id"] == -1) {
                        $this->changes[$markerGroupID][$markerTypeID][$changeID]["marker"]["id"] = $this->getNewID();
                    }
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

        foreach($collection as $markerGroup) {
            foreach($markerGroup['markerGroup'] as $markerType) {
            
                foreach($markerType['markers'] as $marker) {
                    if($marker["id"] > $maxID) $maxID = $marker["id"];
                }
            }
        }

        return $maxID;
    }    

    protected function mergeChanges() {
        
        foreach($this->reference as $markerGroupID => $markerGroup) {
            foreach($markerGroup['markerGroup'] as $markerTypeID => $markerType) {

                $markerCollection = &$this->reference[$markerGroupID]['markerGroup'][$markerTypeID]['markers'];

                foreach($markerType['markers'] as $markerID => $marker) {
                
                    $change = $this->getMarkerInChangesByID($marker["id"], $markerGroupID, $markerType);

                    if($change != null) {

                        switch($change["status"]) {
                            case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                            case DiffProcessor::STATUS_MODIFIED_DATA:
                            case DiffProcessor::STATUS_MODIFIED_ALL:
                                $markerCollection[$markerID] = $change["marker"];
                                break;
                            case DiffProcessor::STATUS_REMOVED:
                                unset($markerCollection[$markerID]);
                                $markerCollection = array_values($markerCollection);
                                break;
                        }

                        $changeCollection = &$this->changes[$markerGroupID][$markerType['slug']];

                        // we remove the items so there is only the remainings items with the "STATUS_ADDED" status
                        $id = array_search($change, $changeCollection);
                        unset($changeCollection[$id]);
                        $changeCollection = array_values($changeCollection);
                    }
                }
                
                foreach($this->changes[$markerGroupID][$markerType['slug']] as $change) {
                    
                    $markerCollection[] = $change['marker'];
                }
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
                //$outputString.= "\t\t\t".'name : "'.$markerType['name'].'",'.PHP_EOL;
                $outputString.= "\t\t\t".'slug : "'.$markerType['id'].'",'.PHP_EOL;
                $outputString.= "\t\t\t".'markers : ['.PHP_EOL;


                if(array_key_exists($markerGroup['slug'], $this->reference)) {

                    $tab = "\t\t\t\t";
                    $markers = $this->getMarkersByType($markerType['id']);
                    
                    foreach($markers as $marker) {
                        $outputString.= $tab.'{ "id" : '.$marker['id'].', "lat" : '.$marker['lat'].', "lng" : '.$marker['lng'].', ';
                        $outputString.='"area" : '.$marker["area"].', ';
                        $outputString.='"title" : "'.$marker['title'].'", "desc" : "'.$marker["desc"].'"},'.PHP_EOL;
                    }

                    // remove the last comma
                    if(!empty($markers)) {
                        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
                    }
                }

                $outputString.= "\t\t\t".']'.PHP_EOL;
                $outputString.= "\t\t".'},'.PHP_EOL;                
            }

            // remove the last comma
            $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
            
            $outputString.= "\t".']'.PHP_EOL.'};'.PHP_EOL;
        }

        return $outputString;
    }
    
    protected function generateResourcesOutput() {

        $outputString = "Resources.Paths = {".PHP_EOL;
        $outputString.= "\t".'"icons" : "'.$this->resourcesPath.'"'.PHP_EOL;
        $outputString.="};".PHP_EOL.PHP_EOL;

        $outputString.="Resources.Icons = {".PHP_EOL;

        foreach($this->markerGroups as $markerGroup) {

            $outputString.= "\t".'"'.$markerGroup['slug'].'" : {'.PHP_EOL;

            foreach($markerGroup['markerTypes'] as $markerType) {
                $outputString.= "\t\t".'"'.$markerType['id'].'" : { ';
                $outputString.= '"label" : "'.$markerType['name'].'",';
                $outputString.= '"url" : Resources.Paths.icons + "'.$markerType['filename'].'"},'.PHP_EOL;
            }
            
            // remove the last comma
            $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL."\t".'},'.PHP_EOL;
        }

        // remove the last comma
        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;

        $outputString.="};".PHP_EOL.PHP_EOL;

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
        $outputString.= "];".PHP_EOL.PHP_EOL;
         
         return $outputString;
    }
    
    protected function getAreaSummary($idArea) {
        
        $summary = array("hearts" => 0, "waypoints" => 0, "skillpoints" => 0, "poi" => 0, "dungeons" => 0);

        foreach($this->reference as $markerGroup) {

            foreach($markerGroup['markerGroup'] as $markerType => $markerTypeCollection) {

                foreach($markerTypeCollection['markers'] as $marker) {

                    if(array_key_exists("area", $marker) && $marker["area"] == $idArea) {
                        $summary[$markerType]++;
                    }
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
    
    public function minimize() {
        $this->output = preg_replace('#[\t\n ]*(=|:|,|;|{|}|\[|\]|")[\t\n ]*#', '$1',$this->output);
    }

    public function save($pathOutput, $minimized) {

        $file = fopen($pathOutput, "w+");

        if($minimized) {
            $this->minimize();
        }

        fwrite($file, $this->output);
        fclose($file);
    }
    
    public function getOutput() { return $this->output; }
    
    protected function getMarkersByType($type) {
        
        foreach($this->reference as $markerGroup) {
            
            foreach($markerGroup['markerGroup'] as $markerType) {
                if($markerType['slug'] == $type) {
                    return $markerType['markers'];
                }
            }
        }
        
        return array();
    }
    
    protected function getMarkerInChangesByID($markerID, $markerGroupID, $markerType) {

        if(!array_key_exists($markerType['slug'], $this->changes[$markerGroupID])) return null;

        foreach($this->changes[$markerGroupID][$markerType['slug']] as $change) {

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
