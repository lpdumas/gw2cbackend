<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

/**
 * This class computes statistics on submitted modifications.
 */
class StatsProcessor {

    public function __construct(array $changes) {

        $this->changes = $changes;

        $this->stats = array(
            \GW2CBackend\DiffProcessor::STATUS_ADDED => 0,
            \GW2CBackend\DiffProcessor::STATUS_REMOVED => 0,
            \GW2CBackend\DiffProcessor::STATUS_MODIFIED_COORDINATES => 0,
            \GW2CBackend\DiffProcessor::STATUS_MODIFIED_DATA => 0,
            \GW2CBackend\DiffProcessor::STATUS_POTENTIAL_DATA_LOSS => 0
        );
    }

    public function process() {

        foreach($this->changes as $group) {

            foreach($group as $type) {

                foreach($type as $change) {
                    if($change['status'] == \GW2CBackend\DiffProcessor::STATUS_MODIFIED_DATA
                        || $change['status'] == \GW2CBackend\DiffProcessor::STATUS_MODIFIED_ALL) {

                        $this->stats[\GW2CBackend\DiffProcessor::STATUS_MODIFIED_DATA]++;
                    }
                    else {
                        $this->stats[$change['status']]++;
                    }
                }
            }
        }

        return $this->stats;
    }
}