<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

use \GW2CBackend\Marker\MapRevision;
use \GW2CBackend\Marker\Marker;

/**
 * This class detects the tags of a modification.
 */
class TagProcessor {

    /**
     * When a marker has been added.
     * @var string
     */
    const TAG_NEW_MARKER = "tag_new_marker";

    /**
     * When a marker has been deleted.
     * @var string
     */
    const TAG_REMOVED_MARKER = "tag_deleted_marker";

    /**
     * When a marker has been moved.
     * @var string
     */
    const TAG_MOVED_MARKER = "tag_moved_marker";

    /**
     * When a potential data loss has been detected.
     * @var string
     */
    const TAG_POTENTIAL_DATA_LOSS = "tag_potential_data_loss";

    /**
     * When the data has been modified. A language suffix will be added.
     * @var string
     */
    const TAG_MODIFIED_DATA = "tag_modified_data_";

    protected $tags;
    
    protected $changes;
    
    protected $tagsDisplayName;

    public function __construct(array $changes = array()) {

        $this->changes = $changes;
        $this->tags = array();

        $this->tagsDisplayName = array(
            self::TAG_NEW_MARKER => 'new marker',
            self::TAG_REMOVED_MARKER => 'removed marker',
            self::TAG_MOVED_MARKER => 'moved marker',
            self::TAG_POTENTIAL_DATA_LOSS => 'potential data loss',
        );
    }

    public function process() {

        foreach($this->changes as $mGroup) {
            foreach($mGroup as $mType) {
                foreach($mType as $change) {
                    $tag = $this->detectTag($change);
                    $this->addTag($tag);
                }
            }
        }

        return $this->tags;
    }

    protected function detectTag($change) {

        switch($change['status']) {

            case DiffProcessor::STATUS_ADDED:
                return self::TAG_NEW_MARKER;
            case DiffProcessor::STATUS_REMOVED:
                return self::TAG_REMOVED_MARKER;
            case DiffProcessor::STATUS_POTENTIAL_DATA_LOSS:
                return self::TAG_POTENTIAL_DATA_LOSS;
            case DiffProcessor::STATUS_MODIFIED_COORDINATES:
                return self::TAG_MOVED_MARKER;
            case DiffProcessor::STATUS_MODIFIED_ALL:
            case DiffProcessor::STATUS_MODIFIED_DATA:
                return $this->detectLanguageTag($change);
        }
    }

    protected function detectLanguageTag($change) {

        $marker = $change['marker'];
        $markerRef = $change['marker-reference'];
        $tmpTags = array();

        foreach($marker->getData()->getAllData() as $lSlug => $lang) {

            foreach($lang as $key => $value) {
                $valueRef = $markerRef->getData()->getData($lSlug, $key);
                if($value != $valueRef) {

                    array_push($tmpTags, self::TAG_MODIFIED_DATA.$lSlug);

                    if(!array_key_exists(self::TAG_MODIFIED_DATA.$lSlug, $this->tagsDisplayName)) {
                        $this->tagsDisplayName[self::TAG_MODIFIED_DATA.$lSlug] = $lSlug;
                    }
                }
            }
        }

        return $tmpTags;
    }

    public function getTagSlug($tag) {

        if(array_key_exists($tag, $this->tagsDisplayName)) {
            return $this->tagsDisplayName[$tag];
        }
        else {
            $slug = explode('_', $tag);
            return $slug[count($slug) - 1];
        }
    }

    /**
     * Adds a tag.
     *
     * The tag is not added if it has already been added.
     *
     * @param array|string $tag
     */
    protected function addTag($tag) {

        if(is_array($tag)) {
            foreach($tag as $t) {
                $this->addTag($t);
            }

            return;
        }

        if(!$this->hasTag($tag)) {
            array_push($this->tags, $tag);
        }
    }

    protected function hasTag($tag) { return in_array($tag, $this->tags); }

    protected function removeTag($tag) {

        if($this->hasTag($tag)) {
            unset($this->tags[array_keys($this->tags, $tag)]);
        }
    }

    /**
     * Gets all the tags.
     *
     * @return array
     */
    public function getTags() { return $this->tags; }

    public function getTagsDisplayName($tag) {

        if(!array_key_exists($tag, $this->tagsDisplayName)) {
            return null;
        }

        return $this->tagsDisplayName[$tag];
    }

    public function getAllTagsDisplayName() { return $this->tagsDisplayName; }
}