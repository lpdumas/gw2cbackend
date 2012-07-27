<?php

namespace GW2CBackend\Marker\tests\units;

use \mageekguy\atoum;

class Marker extends atoum\test {

    public function testGetters() {
        
        $id = -1;
        $lat = 50.3289472352;
        $lng = -30.239432;
        $area = 1;
        $translatedData = new \GW2CBackend\TranslatedData(array());

        $this
            ->if($marker = new \GW2CBackend\Marker\Marker($id, $lat, $lng, $area, $translatedData))
            ->then
                ->integer($marker->getID())->isEqualTo($id)
                ->integer($marker->getArea())->isEqualTo($area)
                ->float($marker->getLat())->isEqualTo($lat)
                ->float($marker->getLng())->isEqualTo($lng)
                ->variable($marker->getStatus())->isNull()
                ->variable($marker->getReference())->isNull()
                ->object($marker->getData())
                    ->isInstanceOf('\GW2CBackend\TranslatedData')
                    ->isIdenticalTo($translatedData)
        ;
    }
    
    public function testSetters() {
        
        $id = -1;
        $lat = 50.3289472352;
        $lng = -30.239432;
        $area = 1;
        $translatedData = new \GW2CBackend\TranslatedData(array());

        $newID = 100;
        $newLat = 30.342;
        $newLng = 41.5141;
        $newData = new \GW2CBackend\TranslatedData(array());
        $status = "random_status_string";
        $this
            ->if($marker = new \GW2CBackend\Marker\Marker($id, $lat, $lng, $area, $translatedData))
            ->and($marker->setID($newID))
            ->and($marker->setLat($newLat))
            ->and($marker->setLng($newLng))
            ->and($marker->setStatus($status))
            ->and($marker->setData($newData))
            ->and($marker->setReference($marker))
            ->then
                ->float($marker->getLat())->isEqualTo($newLat)
                ->float($marker->getLng())->isEqualTo($newLng)
                ->object($marker->getData())
                    ->isIdenticalTo($newData)
                    ->isNotIdenticalTo($translatedData)
                ->string($marker->getStatus())->isEqualTo($status)
                ->object($marker->getReference())
                    ->isInstanceOf('\GW2CBackend\Marker\Marker')
                    ->isIdenticalTo($marker)
        ;
    }
    
    public function testCompare() {
        
        $id = -1;
        $lat = 50.3289472352;
        $lng = -30.239432;
        $area = 1;
        $translatedData = new \GW2CBackend\TranslatedData(array());

        $id2 = 100;
        $lat2 = 30.342;
        $lng2 = 41.5141;
        $area2 = 3;
        $data2 = new \GW2CBackend\TranslatedData(array());
        $this
            ->if($marker = new \GW2CBackend\Marker\Marker($id, $lat, $lng, $area, $translatedData)) 
            ->and($marker2 = new \GW2CBackend\Marker\Marker($id2, $lat2, $lng2, $area2, $data2))
            ->then
                ->boolean($marker->compare($marker))->isTrue()
                ->boolean($marker->compare($marker2))->isFalse()
                ->boolean($marker2->compare($marker))->isFalse()
        ;
    }
    
    public function testSetAsReference() {

        $id = -1;
        $lat = 50.3289472352;
        $lng = -30.239432;
        $area = 1;
        $translatedData = new \GW2CBackend\TranslatedData(array());

        $this
            ->if($marker = new \GW2CBackend\Marker\Marker($id, $lat, $lng, $area, $translatedData)) 
            ->and($marker->setAsReference())
            ->and($reference = $marker->getReference())
            ->then
                ->object($reference)
                    ->isInstanceOf('\GW2CBackend\Marker\Marker')
                    ->boolean($marker->compare($reference))
                    
        ;
    }
}