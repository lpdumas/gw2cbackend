<?php

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

class Marker {
    
    protected $id;
    protected $lat;
    protected $lng;
    protected $area;
    protected $translatedData;
    protected $status;
    protected $reference;
    
    public function __construct($id, $lat, $lng, $area, TranslatedData $translatedData) {
        $this->id = $id;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->area = $area;
        $this->translatedData = $translatedData;
        $this->reference = null;
    }
    
    public function compare(Marker $marker) {
        
        return $this->getData()->compare($marker->getData()) && $this->getArea() == $marker->getArea();
    }
    
    public function setAsReference() {
        
        $this->reference = new Marker($this->id, $this->lat, $this->lng, $this->area, 
                                        new TranslatedData($this->translatedData->getAllData())
                                    );
    }
    
    public function setID($value) { $this->id = $value; }
    public function getID() { return $this->id; }
    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }
    public function getReference() { return $this->reference; }
    public function setReference(Marker $reference) { $this->reference = $reference; }
    public function getLat() { return $this->lat; }
    public function setLat($lat) { return $this->lat = $lat; }
    public function getLng() { return $this->lng; }
    public function setLng($lng) { return $this->lng = $lng; }
    public function getArea() { return $this->area; }
    public function getData() { return $this->translatedData; }
    public function setData(TranslatedData $tData) { $this->translatedData = $tData; }
}