<?php

namespace ThemeHouse\PostComments\XF\SubContainer;

/**
 * Class Import
 * @package ThemeHouse\PostComments\XF\SubContainer
 */
class Import extends XFCP_Import
{
    /**
     *
     */
    public function initialize()
    {
        $initialize = parent::initialize();

        $importers = $this->container('importers');

        $this->container['importers'] = function () use ($importers) {
            $importers[] = 'ThemeHouse\PostComments:CMFThread';
            return $importers;
        };

        return $initialize;
    }
}
