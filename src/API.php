<?php

namespace app\src;

use BadMethodCallException;
use CURLFile;

class API extends Base
{
    public function __call($name, $args)
    {
        if (method_exists($this, $name)) {
            $this->initialize();

            return call_user_func_array([$this, $name], $args);
        } else {
            throw new BadMethodCallException('Call to undefined method '.get_class($this)."::{$name}()", 1);
        }
    }

    protected function searchImage($service_type, $service_id, $image, $loc = [], $tags = [], $count = 20,
                                   $skip_dedupe=False, $threshold = 0.0)
    {
        $this->loadImage($image);

        if ($loc) {
            $this->body['loc'] = is_array($loc) ? implode('-', $loc) : $loc;
        }

        if ($tags) {
            if (is_array($tags)) {
                $this->body['tags'] = is_array(reset($tags)) ? json_encode($tags) : implode('|', $tags);
            } else {
                $this->body['tags'] = $tags;
            }
        }

        if ($count) {
            $this->body['count'] = intval($count);
        }

        $this->body['skip_dedupe'] = $skip_dedupe ? 1 : 0;

        if ($threshold && is_numeric($threshold)) {
            $this->body['threshold'] = $threshold;
        }

        return $this->curl($service_type, $service_id);
    }

    protected function classifyImage($service_type, $service_id, $image, $loc = [])
    {
        return $this->searchImage($service_type, $service_id, $image, $loc);
    }

    protected function detectImage($service_type, $service_id, $image, $loc = [])
    {
        return $this->searchImage($service_type, $service_id, $image, $loc);
    }

    /*
    protected function addImageSet($name, $description = '')
    {
        $this->body['name'] = $name;
        $this->body['description'] = $description;

        return $this->curl('image_sets', '_0000014');
    }

    protected function removeImageSet($set_id)
    {
        $this->method = 'DELETE';

        return $this->curl('image_sets', "_0000014/$set_id");
    }
    */

    protected function addImageToSet($set_id, $image_url, $meta = '', $tags = [])
    {
        $this->body['image_url'] = $image_url;

        if ($meta) {
            $this->body['meta'] = $meta;
        }

        if ($tags) {
            $this->body['tags'] = implode('|', $tags);
        }

        return $this->curl('image_sets', "_0000014/$set_id");
    }

    /*
    protected function removeImageFromSet($set_id, $image_url)
    {
        $this->method = 'DELETE';

        $this->body['image_url'] = $image_url;

        return $this->curl('image_sets', "_0000014/$set_id");
    }
    */

    protected function addImagesToSet($set_id, $images)
    {
        if (is_array($images)) {
            $images = $this->convertArrayToCSV($images);
        }

        $this->body['urls_to_add'] = new CURLFile($images);

        return $this->curl('image_sets', "_0000014/$set_id");
    }

    protected function removeImagesFromSet($set_id, $images)
    {
        if (is_array($images)) {
            $images = $this->convertArrayToCSV($images);
        }

        $this->body['urls_to_delete'] = new CURLFile($images);

        return $this->curl('image_sets', "_0000014/$set_id");
    }

    protected function imageColorAnalysis($image, $type, $granularity, $return_type, $loc = [])
    {
        $this->loadImage($image);

        switch ($type) {
            case 'everything':
                $service_type = 'image_analysis_everything';
                $service_id = '_0000072';

                break;

            case 'foreground':
                $service_type = 'image_analysis_foreground';
                $service_id = '_0000073';

                break;

            case 'person_outfit':
                $service_type = 'image_analysis_person_outfit';
                $service_id = '_0000074';

                break;

            default:
                throw new BadMethodCallException('Bad type.', 1);
        }

        if (!in_array($granularity, array('major', 'detailed', 'dominant'))) {
            throw new BadMethodCallException('Bad granularity.', 1);
        }
        $this->body['granularity'] = $granularity;

        if (!in_array($return_type, array('basic', 'w3c', 'ncs', 'cncs'))) {
            throw new BadMethodCallException('Bad return type.', 1);
        }
        $this->body['return_type'] = $return_type;

        if ($loc) {
            $this->body['loc'] = is_array($loc) ? implode('-', $loc) : $loc;
        }

        return $this->curl($service_type, $service_id);
    }

    protected function generalRequest($service_type, $service_id, $image = null, $args = [])
    {
        if ($image !== null) {
            $this->loadImage($image);
        }

        if ($args) {
            $this->body += $args;
        }

        return $this->curl($service_type, $service_id);
    }
}
