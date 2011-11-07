<?php

    require_once $_SERVER['KERNEL_DIR'] . 'bootstrap.php.cache';

    use Symfony\Component\ClassLoader\UniversalClassLoader;

    $loader = new UniversalClassLoader();
    $loader->registerNamespace('Adticket\\Sf2BundleOS\\Elvis\\JobBundle', __DIR__ .  DIRECTORY_SEPARATOR . '..');
    $loader->register();