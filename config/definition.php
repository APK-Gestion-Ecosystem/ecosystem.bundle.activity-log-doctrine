<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition) {
    $definition->rootNode()
        ->children()
            ->scalarNode('id')->end()
            ->scalarNode('screen_name')->end()
        ->end()
    ;
};
