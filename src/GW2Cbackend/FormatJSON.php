<?php

namespace GW2CBackend;

class FormatJSON {
    
    protected $json;
    
    public function __construct($json) {
        $this->json = json_decode($json, true);
    }
    
    public function format() {
        
        foreach($this->json  as $idMarkerGroup => $markerGroups) {

            foreach($markerGroups['markerGroup'] as $idMarkerType => $markerType) {
                
                $id = 1;
                
                foreach($markerType['markers'] as $idMarker => $marker) {

                    $this->json[$idMarkerGroup]['markerGroup'][$idMarkerType]['markers'][$idMarker]['id'] = $id;
                    $this->json[$idMarkerGroup]['markerGroup'][$idMarkerType]['markers'][$idMarker]['area'] = 0;
                    $id++;
                }
            }
        }
        
        return $this->json;
    }
}
