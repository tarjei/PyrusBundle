<?php
/*
 * This file is part of phpBB.
 *
 * (c) phpBB Ltd.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\Phpbb\PyrusBundle;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Provides simplified access to Pyrus in symfony environment
 */
class Pyrus
{
    protected $kernel;
    protected $frontend;
    protected $config;

    /**
     * Sets Pyrus up for later use
     *
     * @param Kernel $kernel The application's kernel
     */
    public function __construct(Kernel $kernel, \PEAR2\Pyrus\ScriptFrontend\Commands $frontend = null)
    {
        if (!$frontend) {
            spl_autoload_register(array($this, 'pyrus_autoload'));
            $frontend = new \PEAR2\Pyrus\ScriptFrontend\Commands;
        }

        $this->kernel = $kernel;
        $this->frontend = $frontend;

        $this->config = \PEAR2\Pyrus\Config::singleton($this->getPyrusDir(), $this->getPearConfig());

        $this->setupPyrus();
    }

    public function setupPyrus()
    {
        if (!file_exists($this->getPearConfig()))
        {
            $config->saveConfig($this->getPearConfig());
        }
/*
        $settings = array(
            //'php_dir' => '@php_dir@',
            'ext_dir' => '@php_dir@/pyrus/ext',
            'doc_dir' => '@php_dir@/pyrus/docs',
            'bin_dir' => $this->kernel->getRootDir() . '/bin',
            //'data_dir' => '@php_dir@/pyrus/data',
            'cfg_dir' => '@php_dir@/pyrus/cfg',
            'www_dir' => $this->kernel->getRootDir() . '/web',
            'test_dir' => '@php_dir@/pyrus/tests',
            'src_dir' => '@php_dir@/pyrus/src',
            'auto_discover' => 1,
            'cache_dir' => '@php_dir@/pyrus/cache',
            'temp_dir' => '@php_dir@/pyrus/temp',
            'download_dir' => '@php_dir@/pyrus/downloads',
            'plugins_dir' => '@default_config_dir@',
        );

        $options = array('plugin' => false);

        foreach ($settings as $name => $desiredValue) {
            $currentValue = $this->getConfig($name);

            if ($currentValue != $desiredValue) {
                $this->setConfig($name, $desiredValue);
            }
        }*/

        $this->installCustomRole();
    }

    /**
     * Retrieves a value from the Pyrus configuration.
     *
     * @param  string $name The variable name
     * @return mixed        The configuration value
     */
    public function getConfig($name)
    {
        $this->config = \PEAR2\Pyrus\Config::current();
        return $this->config->__get($name);
    }

    /**
     * Overwrites a configuration value.
     *
     * @param  string $name  The variable name
     * @param  mixed  $value New value
     */
    public function setConfig($name, $value)
    {
        $this->config->__set($name, $value);
        $this->config->saveConfig($this->getPearConfig());
    }

    public function installCustomRole()
    {
        $pf = new \PEAR2\Pyrus\PackageFile\v2;

        $pf->name = 'PyrusBundlePlugin';
        $pf->channel = 'pear2.php.net';
        $pf->summary = 'Local';
        $pf->description = 'Description';
        $pf->notes = 'My Notes';
        $pf->maintainer['naderman']->role('lead')->email('naderman@phpbb.com')->active('yes')->name('Nils Adermann');
        $pf->files['bundle.xml'] = array(
            'attribs' => array('role' => 'customrole'),
        );
        $pf->files['PyrusBundlePlugin/BundleRole.php'] = array(
            'attribs' => array('role' => 'php'),
        );

        $package_xml = __DIR__ . '/plugin/package.xml';
        $pf->setPackagefile($package_xml);

        $package = new \PEAR2\Pyrus\Package(false);
        $xmlcontainer = new \PEAR2\Pyrus\PackageFile($pf);
        $xml = new \PEAR2\Pyrus\Package\Xml($package_xml, $package, $xmlcontainer);
        $package->setInternalPackage($xml);
        \PEAR2\Pyrus\Main::$options['install-plugins'] = true;

        \PEAR2\Pyrus\Installer::begin();
        \PEAR2\Pyrus\Installer::prepare($package);
        \PEAR2\Pyrus\Installer::commit();
    }

    /**
     * Returns the directory used for Pyrus packages
     *
     * @return string The vendor directory
     */
    public function getPyrusDir()
    {
        return $this->kernel->getRootDir() . '/../src/vendor';
    }

    /**
     * Retrieve path to pearconfig.xml user configuration
     *
     * @return string Path to pearconfig.xml
     */
    public function getPearConfig()
    {
        return $this->getPyrusDir() . '/pearconfig.xml';
    }

    /**
     * Returns the directory to be used for Pyrus installed Bundles.
     *
     * @return string The bundle directory
     */
    public function getBundleDir()
    {
        $bundleDirs = $this->kernel->getBundleDirs();

        if (!isset($bundleDirs['Bundle'])) {
            throw new \RunTimeException("No BundleDir for namespace Bundle defined in Application Kernel.");
        }

        return $bundleDirs['Bundle'];
    }

    /**
     * Executes a Pyrus command through its CLI frontend in the bundle directory.
     *
     * @param array $argv The list of options and arguments, not including the
     *                    executing script.
     */
    public function run(array $argv)
    {
        array_unshift($argv, $this->getPyrusDir());

        $this->frontend->run($argv);
    }

    /**
     * The Pyrus autoloader taking classes from the phar file within this Bundle.
     *
     * @param string $class The class name
     */
    public function pyrus_autoload($class)
    {
        $class = str_replace(array('_','\\'), '/', $class);
        if (file_exists('phar://' . __DIR__ . '/pyrus.phar/PEAR2_Pyrus-2.0.0a2/php/' . $class . '.php')) {
            include 'phar://' . __DIR__ . '/pyrus.phar/PEAR2_Pyrus-2.0.0a2/php/' . $class . '.php';
        }
    }
}
