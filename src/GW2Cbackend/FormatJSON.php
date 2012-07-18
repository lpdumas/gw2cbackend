<?php

namespace GW2CBackend;

class FormatJSON {
    
    protected $json;
    
    public function __construct($json) {
        $this->json = json_decode($json, true);    
    }
    
    public function format() {
                
        $id = 1;

        foreach($this->json['markers']  as $idMarkerGroup => $markerGroups) {
            
            if($idMarkerGroup == "train") {
                $idMarkerGroup = "trainers";
            }

            foreach($markerGroups['marker_types'] as $idMarkerType => $markerType) {
                
                foreach($markerType['markers'] as $idMarker => $marker) {
                    $m = &$this->json['markers'][$idMarkerGroup]['marker_types'][$idMarkerType]['markers'][$idMarker];
                    $m['id'] = $id;
                    $id++;
                    
                    if(array_key_exists('data_translation', $marker)) {
                        foreach($marker['data_translation'] as $l => $lang) {
                            foreach($lang as $key => $v) {
                                if($key == "wikiLink") {
                                    $m['data_translation'][$l]['link_wiki'] = $v;
                                    unset($m['data_translation'][$l]['wikiLink']);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return array('max_id' => $id, 'json' => $this->json);
    }
}
