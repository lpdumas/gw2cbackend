<?php

namespace GW2CBackend\Marker;

use GW2CBackend\TranslatedData;

class Marker {
    
    protected $id;
    protected $lat;
    protected $lng;
    protected $area;
    protected $translatedData;
    
    public function __construct($id, $lat, $lng, $area, TranslatedData $translatedData) {
        $this->id = $id;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->area = $area;
        $this->translatedData = $translatedData;
    }
    
    public function setID($value) { $this->id = $value; }
    public function getID() { return $this->id; }
    public function getLat() { return $this->lat; }
    public function getLng() { return $this->lng; }
    public function getArea() { return $this->area; }
    public function getData() { return $this->translatedData; }
}