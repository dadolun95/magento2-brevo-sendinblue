<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Dadolun_SibOrderSync',
    isset($file) && realpath($file) == __FILE__ ? dirname($file) : __DIR__
);
