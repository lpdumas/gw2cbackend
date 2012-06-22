<?php

namespace GW2CBackend;

class ConfigGenerator {
    
    protected $output;
    
    protected $reference;
    protected $changes;
    
    public function __construct($reference, $changes) {
        
        $this->reference = $reference;
        $this->changes = $changes;
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

                $change = $this->getMarkerInChangesByID($marker["id"]);

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
                }
            }
        }        
    }
    
    protected function generateMarkersOutput() {
        
        $outputString = "";
        
        foreach($this->reference as $markerType => $markerTypeCollection) {
            
            $outputString.="Markers.".$markerType." = [".PHP_EOL;
            
            foreach($markerTypeCollection as $marker) {
                
                $outputString.="\t".'{ "id" : '.$marker['id'].', "lat" : '.$marker['lat'].', "lng" : '.$marker['lng'].',';
                $outputString.=' "title" : "'.$marker['title'].'", "desc" : "'.$marker["desc"].'"},'.PHP_EOL;
            }
            
            // remove the last comma
            $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
            
            $outputString.="]".PHP_EOL;
        }
        
        return $outputString;
    }
    
    protected function generateResourcesOutput() {
        
        // TODO : generate this from a config place (file, database, whatever)
        
        $outputString = "Resources.Paths = {".PHP_EOL;
        $outputString.= "\t".'"icons": "assets/images/icons/32x32/"'.PHP_EOL;
        $outputString.="}".PHP_EOL.PHP_EOL;
        
        $outputString.="Resources.Icons = {".PHP_EOL;
        $outputString.="\t".'"hearts" : { "label" : "Hearts", "url" : Resources.Paths.icons + "hearts.png"},'.PHP_EOL;
        $outputString.="\t".'"waypoints" : { "label" : "Waypoints", "url" : Resources.Paths.icons + "waypoints.png"},'.PHP_EOL;
        $outputString.="\t".'"skillpoints" : { "label" : "Skill points", "url" : Resources.Paths.icons + "skillpoints.png"},'.PHP_EOL;
        $outputString.="\t".'"poi" : { "label" : "Points of intereset", "url" : Resources.Paths.icons + "poi.png"},'.PHP_EOL;
        $outputString.="\t".'"dungeons" : { "label" : "Dungeons", "url" : Resources.Paths.icons + "dungeon.png"},'.PHP_EOL;
        $outputString.="\t".'"asurasgates" : { "label" : "Asuras\' gates", "url" : Resources.Paths.icons + "asuraGate.png"},'.PHP_EOL;
        
        $outputString.="}".PHP_EOL.PHP_EOL;

        return $outputString;
    }
    
    protected function generateAreasOutput() {
        
        // TODO : generate this from a config place (file, database, whatever)
        
        $outputString = "Areas = [".PHP_EOL;
        
        $summary = array("hearts" => 0, "waypoints" => 13, "skillpoints" => 0, "poi" => 20, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Divinity's Reach", "", $summary, 43.19591001164034, -31.519775390625, 33.44977658311853, -45.955810546875).",".PHP_EOL;
        
        $summary = array("hearts" => 17, "waypoints" => 16, "skillpoints" => 7, "poi" => 21, "dungeons" => 1);
        $outputString.= "\t".$this->generateOneAreaOutput("Queensdale", "1-17", $summary, 33.38558626887102, -23.8623046875, 18.406654713919085, -48.27392578125).",".PHP_EOL;
        
        $summary = array("hearts" => 14, "waypoints" => 18, "skillpoints" => 5, "poi" => 16, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Kessex Hills", "15-25", $summary, 8.36495262653919, -23.5546875, 4.598327203100929, -51.17431640625).",".PHP_EOL;
        
        $summary = array("hearts" => 11, "waypoints" => 2, "skillpoints" => 7, "poi" => 15, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Gendarran Fields", "25-35", $summary, 29.765834552626156, 5.685546875, 17.576565709783402, -22.88427734375).",".PHP_EOL;

        $summary = array("hearts" => 0, "waypoints" => 12, "skillpoints" => 0, "poi" => 18, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Black Citadel", "", $summary, 20.786930592570368, 57.94189453125, 11.081384602413175, 47.900390625).",".PHP_EOL;

        $summary = array("hearts" => 16, "waypoints" => 18, "skillpoints" => 5, "poi" => 26, "dungeons" => 1);
        $outputString.= "\t".$this->generateOneAreaOutput("Plains of Ashford", "1-15", $summary, 21.764601405744017, 85.682373046875, 7.983077720238533, 58.73291015625).",".PHP_EOL;

        $summary = array("hearts" => 15, "waypoints" => 19, "skillpoints" => 8, "poi" => 21, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Diessa Plateau", "15-25", $summary, 35.54116627999818, 71.47705078125, 21.463293441899314, 47.373046875).",".PHP_EOL;

        $summary = array("hearts" => 0, "waypoints" => 14, "skillpoints" => 0, "poi" => 24, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Hoelbrak", "", $summary, 22.907803451058495, 34.4970703125, 12.747516274952828, 21.280517578125).",".PHP_EOL;

        $summary = array("hearts" => 16, "waypoints" => 17, "skillpoints" => 8, "poi" => 18, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Wayfarer Foothills", "1-15", $summary, 34.768691457552755, 46.5380859375, 8.265855052877273, 35.74951171875).",".PHP_EOL;

        $summary = array("hearts" => 13, "waypoints" => 18, "skillpoints" => 6, "poi" => 20, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Snowden Drifts", "15-25", $summary, 35.89795019335764, 34.4970703125, 23.95613633396941, 6.61376953125).",".PHP_EOL;

        $summary = array("hearts" => 0, "waypoints" => 13, "skillpoints" => 0, "poi" => 20, "dungeons" => 0);
        $outputString.= "\t".$this->generateOneAreaOutput("Lion's Arch", "", $summary, 17.025272685376905, 5.526123046875, 6.263804863758637, -10.099023437500023).",".PHP_EOL;

        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
        $outputString.= "]".PHP_EOL.PHP_EOL;
         
         return $outputString;
    }
    
    public function generateOneAreaOutput($name, $rangeLvl, $summary, $neLat, $neLng, $swLat, $swLng) {
        
        $outputString = '{ name : "'.$name.'", rangeLvl : "'.$rangeLvl.'",'.PHP_EOL;
        
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
            $outputString = preg_replace("#[\t\n ]#", "",$this->output);
        }
        else {
            $outputString = $this->output;
        }
        
        fwrite($file, $outputString);
        fclose($file);
    }
    
    protected function getMarkerInChangesByID($markerID) {
        
        foreach($this->changes as $markerTypeCollection) {
            
            foreach($markerTypeCollection as $change) {

                if((array_key_exists("marker", $change) && $change["marker"]["id"] == $markerID) || 
                    (array_key_exists("marker-reference", $change) && $change["marker-reference"]["id"] == $markerID)) {

                    return $change;
                }
            }
        }
        
        return null;
    }
}