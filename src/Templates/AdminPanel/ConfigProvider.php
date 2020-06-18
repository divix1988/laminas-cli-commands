<?php

namespace $module_name%;

class ConfigProvider
{
    /**
     * Provide dependency configuration for an application integrating i18n.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'view_manager' => $this->getViewManagerConfig(),
            'admin' => $this->getModuleConfig(),
        ];
    }
    /**
     * Provide dependency configuration for an application.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                'admin_navigation' => Navigation\Service\AdminNavigationFactory::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getViewManagerConfig()
    {
        return [
            'template_path_stack' => [
                __DIR__ . '/../view',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getModuleConfig()
    {
        return [
            'use_admin_layout' => true,
            'admin_layout_template' => 'layout/admin',
        ];
    }
}
