<?php

namespace GW2CBackend;

use GW2CBackend\Marker\MapRevision;
use GW2CBackend\Marker\Marker;

/**
 * Generator of the configuration file
 */
class ConfigGenerator {
    
    /**
     * Contains the generator's output
     * @var string
     */
    protected $output;
    
    /**
     * Contains the source of the generation
     * @var \GW2CBackend\Marker\MapRevision
     */
    protected $revision;
    
    /**
     * Contains all the area information
     * @var array
     */
    protected $areas;
    
    /**
     * The path of the marker types's sicons
     * @var string
     */
    protected $resourcesPath;
    
    /**
     * Constructor
     * @param \GW2CBackend\Marker\MapRevision $revision the source of the generation
     * @param string $resourcesPath the path to the marker's icons
     * @param array $areas contains arrays that describe areas
     */
    public function __construct(MapRevision $revision, $resourcesPath, array $areas) {
        
        $this->revision = $revision;
        $this->areas = $areas;
        $this->resourcesPath = $resourcesPath;
    }
    
    /**
     * Trigger the generation process
     * @return string the generated output
     */
    public function generate() {
        
        $outputString = "";
        
        $outputString.= $this->generateMetadataOutput();
        $outputString.= $this->generateMarkersOutput();
        $outputString.= $this->generateAreasOutput();

        $this->output = $outputString;
        
        return $this->output;
    }

    /**
     * Generator Subprocess. Generates the output of the markers
     * @return string the generated output
     */
    protected function generateMarkersOutput() {
        
        $outputString = "";
        
        foreach($this->revision->getAllMarkerGroups() as $markerGroup) {

            $outputString.= 'Markers.'.$markerGroup->getSlug().' = {'.PHP_EOL;
            $outputString.= $this->generateTranslatedDataOutput($markerGroup->getData(), 1);
            $outputString.= self::tabs().'marker_types : {'.PHP_EOL;

            $markerTypes = $markerGroup->getAllMarkerTypes();
            foreach($markerTypes as $markerType) {

                $outputString.= self::tabs(2).'"'.$markerType->getSlug().'" : {'.PHP_EOL;
                $outputString.= self::tabs(3).'slug : "'.$markerType->getSlug().'",'.PHP_EOL;
                $outputString.= self::tabs(3).'icon : "'.$markerType->getIcon().'",'.PHP_EOL;
                $outputString.= $this->generateTranslatedDataOutput($markerType->getData(), 3);
                $outputString.= self::tabs(3).'markers : ['.PHP_EOL;

                $markers = $markerType->getAllMarkers();
                foreach($markers as $marker) {
                    $outputString.= $this->generateMarkerOutput($marker, 4);
                }
                
                if(!empty($markers)) {
                    $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
                }

                $outputString.= self::tabs(3).']'.PHP_EOL;
                $outputString.= self::tabs(2).'},'.PHP_EOL;                
            }

            // remove the last comma
            if(!empty($markerTypes)) {
                $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL;
            }
            
            $outputString.= self::tabs().'}'.PHP_EOL.'};'.PHP_EOL;
        }

        return $outputString;
    }
    
    /**
     * Generator subprocess. Generates the output of ONE marker
     * @param \GW2CBackend\Marker\Marker $marker the marker source of the generation
     * @param integer $numTabs the number of tabs to put in front of the output
     * @return string the generated output
     */
    protected function generateMarkerOutput(Marker $marker, $numTabs) {
        $outputString = self::tabs($numTabs).'{'.PHP_EOL;
        $outputString.= self::tabs($numTabs + 1).'id : '.$marker->getID().', ';
        $outputString.= 'lat : '.$marker->getLat().', lng : '.$marker->getLng().', ';
        $outputString.= 'area : '.$marker->getArea();

        $tDataOutput = $this->generateTranslatedDataOutput($marker->getData(), $numTabs + 1);

        if(!empty($tDataOutput)) {
            $outputString.= ', '.PHP_EOL.$tDataOutput;
            $outputString = substr($outputString, 0, strlen($outputString) - 2); // remove the last comma
        }

        $outputString.= PHP_EOL.self::tabs($numTabs)."},".PHP_EOL;
        
        return $outputString;
    }
    
    /**
     * Generator subprocess. Generates the output of ONE translated data field
     * @param \GW2CBackend\TranslatedData $tData the data source of the generation
     * @param integer $numTabs the number of tabs to put in front of the output
     * @return string the generated output
     */
    protected function generateTranslatedDataOutput(TranslatedData $tData, $numTabs) {

        $dataCollection = $tData->getAllData();

        if(empty($dataCollection)) return "";

        $outputString = self::tabs($numTabs).'data_translation : {'.PHP_EOL;

        foreach($dataCollection as $lang => $content) {
            $outputString.= self::tabs($numTabs + 1).''.$lang.' : {'.PHP_EOL;
            foreach($content as $key => $value) {
                $outputString.= self::tabs($numTabs + 2).''.$key.' : "'.$value.'",'.PHP_EOL;
            }
            
            if(!empty($content)) {
                $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL; // remove the last comma
            }
            
            $outputString.= self::tabs($numTabs + 1).'},'.PHP_EOL;
        }
        
        if(!empty($dataCollection)) {
                $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL; // remove the last comma
        }

        $outputString.= self::tabs($numTabs).'},'.PHP_EOL;

        return $outputString;
    }
    
    /**
     * Generator subprocess. Generates the output of file's metadata
     * @return string the generated output
     */
    protected function generateMetadataOutput() {
        
        $mapVersion = $this->revision->getID();
        $date = date('Y-m-d H:i:s');

        $outputString = 'Metadata = {'.PHP_EOL;
        $outputString.= self::tabs().'"version" : '.$mapVersion.','.PHP_EOL;
        $outputString.= self::tabs().'"date_generation" : "'.$date.'"'.PHP_EOL;
        $outputString.= self::tabs().'"icons_path" : "'.$this->resourcesPath.'",'.PHP_EOL;
        $outputString.= "};".PHP_EOL.PHP_EOL;
        
        return $outputString;
    }
    
    
    /**
     * Generator subprocess. Generates the output of all areas
     * @return string the generated output
     */
    protected function generateAreasOutput() {
        
        $outputString = "Areas = [".PHP_EOL;
        
        foreach($this->areas as $a) {
            $summary = $this->getAreaSummary($a["id"]);
            $outputString.= self::tabs().$this->generateOneAreaOutput($a, $summary).",".PHP_EOL;
        }

        $outputString = substr($outputString, 0, strlen($outputString) - 2).PHP_EOL; // remove the last comma
        $outputString.= "];".PHP_EOL.PHP_EOL;
         
         return $outputString;
    }
    
    /**
     * Calculates the area summary (number of markers) for each relevant MarkerType
     * @param integer $areaID the area's ID
     * @return array of values where the key is the marker type's slug and the value the number of associated markers.
     */
    protected function getAreaSummary($areaID) {
        
        $summary = array();

        foreach($this->revision->getAllMarkerGroups() as $markerGroup) {

            foreach($markerGroup->getAllMarkerTypes() as $markerType) {
                
                if($markerType->isDisplayInAreaSummary()) {
                
                    if(!array_key_exists($markerType->getSlug(), $summary)) {
                        $summary[$markerType->getSlug()] = 0;
                    }

                    foreach($markerType->getAllMarkers() as $marker) {

                        if(array_key_exists("area", $marker) && $marker["area"] == $areaID) {
                            $summary[$markerType]++;
                        }
                    }
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Generator subprocess. Generates the output of ONE area
     * @param array $area describes an area
     * @param array $summary where the key is marker type's slug and the value the number of associated markers
     * @return string the generated output
     */
    protected function generateOneAreaOutput($area, $summary) {
        
        $outputString = '{ id : '.$area['id'].', name : "'.$area['name'].'", rangeLvl : "'.$area['rangeLvl'].'",'.PHP_EOL;
        
        $outputString.= self::tabs(2).'summary : {'.PHP_EOL;
        foreach($summary as $markerType => $value) {
            $outputString.= self::tabs(3).'"'.$markerType.'" : '.$value.','.PHP_EOL;
        }
        
        if(!empty($summary)) {
            $outputString = substr($outputString, 0, strlen($outputString) - 2); // remove the last comma
        }
        
        $outputString.= PHP_EOL."\t\t},".PHP_EOL;
        $outputString.= self::tabs(2).'neLat : '.$area['neLat'].', neLng : '.$area['neLng'].', ';
        $outputString.= 'swLat : '.$area['swLat'].', swLng : '.$area['swLng'].''.PHP_EOL;
        $outputString.= self::tabs().'}';
        
        return $outputString;
    }
    
    /**
     * Minimizes the output
     * @return string the minimized output
     */
    public function minimize() {
        $this->output = preg_replace('#[\t\n ]*(=|:|,|;|{|}|\[|\]|")[\t\n ]*#', '$1', $this->output);
        
        return $this->output;
    }

    /**
     * Saves the output to a file
     * @param string $path the path where the file will be saved
     */
    public function save($path) {

        $file = fopen($path, "w+");
        fwrite($file, $this->output);
        fclose($file);
    }
    
    /**
     * Returns the output
     * @return string the output
     */
    public function getOutput() { return $this->output; }
    
    /**
     * Creates a string of tabs
     * @param integer $numTabs the number of tabs. Default is 1.
     * @return string with $numTabs tabs
     */
    protected static function tabs($numTabs = 1) {
        
        $output = "";
        for($i = 0; $i < $numTabs; $i++) { $output.= "\t"; }
        
        return $output;
    }
}
