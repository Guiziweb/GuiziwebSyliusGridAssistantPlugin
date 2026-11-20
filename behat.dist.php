<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\PrettyFormatter;
use Behat\Config\Profile;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension;
use FriendsOfBehat\MinkDebugExtension\ServiceContainer\MinkDebugExtension;
use FriendsOfBehat\SuiteSettingsExtension\ServiceContainer\SuiteSettingsExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use FriendsOfBehat\VariadicExtension\ServiceContainer\VariadicExtension;
use SyliusLabs\SuiteTagsExtension\ServiceContainer\SuiteTagsExtension;

// Sylius 2.3 ships its Behat suites as PHP; 2.1 and 2.2 ship them as YAML.
$syliusConfig = 'vendor/sylius/sylius/src/Sylius/Behat/Resources/config/';
$syliusSuites = file_exists(__DIR__ . '/' . $syliusConfig . 'suites.php')
    ? $syliusConfig . 'suites.php'
    : $syliusConfig . 'suites.yml';

return (new Config())
    ->import([$syliusSuites, 'tests/Behat/Resources/suites.yml'])
    ->withProfile(
        (new Profile('default'))
            ->withFormatter(new PrettyFormatter(verbose: true, paths: false, snippets: false))
            ->withExtension(new Extension(ChromeExtension::class))
            ->withExtension(new Extension(MinkDebugExtension::class, [
                'directory' => 'etc/build',
                'clean_start' => false,
                'screenshot' => true,
            ]))
            ->withExtension(new Extension(MinkExtension::class, [
                'files_path' => '%paths.base%/vendor/sylius/sylius/src/Sylius/Behat/Resources/fixtures/',
                'base_url' => '%env(BEHAT_BASE_URL)%',
                'default_session' => 'symfony',
                'javascript_session' => 'chrome',
                'sessions' => [
                    'symfony' => ['symfony' => null],
                    'chrome' => ['chrome' => ['api_url' => '%env(CHROME_API_URL)%', 'validate_certificate' => false]],
                ],
                'show_auto' => false,
            ]))
            ->withExtension(new Extension(SymfonyExtension::class, [
                'bootstrap' => 'vendor/sylius/test-application/config/bootstrap.php',
                'kernel' => ['class' => 'Sylius\TestApplication\Kernel', 'environment' => 'test'],
            ]))
            ->withExtension(new Extension(VariadicExtension::class))
            ->withExtension(new Extension(SuiteSettingsExtension::class, ['paths' => ['features']]))
            ->withExtension(new Extension(SuiteTagsExtension::class)),
    );
