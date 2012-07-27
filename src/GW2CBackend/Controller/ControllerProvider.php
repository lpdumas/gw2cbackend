<?php 

namespace GW2CBackend\Controller;

class ControllerProvider {
    
    protected $closures;
    
    public function __construct(array $closures) {
        
        $this->closures = $closures;
    }
    
    public function getClosure($id) {
        
        if(!array_key_exists($id, $this->closures) || !is_callable($this->closures[$id])) {
            throw new \BadFunctionCallException("No function defined for index ".$id);
        }
        
        return $this->closures[$id];
    }
}