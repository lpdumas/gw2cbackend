<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Controller;

/**
 * Super-class for Controller Providers.
 */
class ControllerProvider {

    /**
     * A collection of closures.
     * @var array
     */
    protected $closures;

    /**
     * Constructor.
     *
     * @param array $closures a collection of closures.
     */
    public function __construct(array $closures) {
        
        $this->closures = $closures;
    }

    /**
     * Gets a closure.
     *
     * @param string $id the closure identifier.
     * @return callback
     * @throws BadFunctionCallException if the identifier is not in the closures collection.
     */
    public function getClosure($id) {

        if(!array_key_exists($id, $this->closures) || !is_callable($this->closures[$id])) {
            throw new \BadFunctionCallException("No function defined for index ".$id);
        }

        return $this->closures[$id];
    }
}