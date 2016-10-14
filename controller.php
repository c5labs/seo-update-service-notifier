<?php
/**
 * SEO Update Service Notifier Controller File.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
namespace Concrete\Package\SeoUpdateServiceNotifier;

use Core;
use Concrete\Core\Foundation\Service\ProviderList;
use Concrete\Core\Package\Package;
use Illuminate\Filesystem\Filesystem;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Package Controller Class.
 *
 * Start building standards complient concrete5 pacakges from me.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
class Controller extends Package
{
    /**
     * Minimum version of concrete5 required to use this package.
     *
     * @var string
     */
    protected $appVersionRequired = '5.7.0';

    /**
     * Does the package provide a full content swap?
     * This feature is often used in theme packages to install 'sample' content on the site.
     *
     * @see https://goo.gl/C4m6BG
     * @var bool
     */
    protected $pkgAllowsFullContentSwap = false;

    /**
     * Does the package provide thumbnails of the files
     * imported via the full content swap above?
     *
     * @see https://goo.gl/C4m6BG
     * @var bool
     */
    protected $pkgContentProvidesFileThumbnails = false;

    /**
     * Should we remove 'Src' from classes that are contained
     * ithin the packages 'src/Concrete' directory automatically?
     *
     * '\Concrete\Package\MyPackage\Src\MyNamespace' becomes '\Concrete\Package\MyPackage\MyNamespace'
     *
     * @see https://goo.gl/4wyRtH
     * @var bool
     */
    protected $pkgAutoloaderMapCoreExtensions = false;

    /**
     * The packages handle.
     * Note that this must be unique in the
     * entire concrete5 package ecosystem.
     *
     * @var string
     */
    protected $pkgHandle = 'seo-update-service-notifier';

    /**
     * The packages version.
     *
     * @var string
     */
    protected $pkgVersion = '0.9.0';

    /**
     * The packages name.
     *
     * @var string
     */
    protected $pkgName = 'SEO Update Service Notifier';

    /**
     * The packages description.
     *
     * @var string
     */
    protected $pkgDescription = 'Notify popular update services that you have updated your site.';

    /**
     * Package service providers to register.
     *
     * @var array
     */
    protected $providers = [
        'Concrete\Package\SeoUpdateServiceNotifier\Src\Providers\UpdateServiceNotifierServiceProvider',
    ];

    /**
     * Register the packages defined service providers.
     *
     * @return void
     */
    protected function registerServiceProviders()
    {
        $list = new ProviderList(Core::getFacadeRoot());

        foreach ($this->providers as $provider) {
            $list->registerProvider($provider);
        }
    }

    /**
     * The packages on start hook that is fired as the CMS is booting up.
     *
     * @return void
     */
    public function on_start()
    {
        // Register defined service providers
        $this->registerServiceProviders();

        // Listen for page approvals so we can ping the update services.
        Core::make('seo.update.notifier')->listen();
    }

    /**
     * The packages install routine.
     *
     * @return \Concrete\Core\Package\Package
     */
    public function install()
    {
        $pkg = parent::install();

        // Install the dashboard page.
        $sp = \Concrete\Core\Page\Single::add('/dashboard/system/seo/notifications', $pkg);
        $sp->update([
            'cName' => 'Update Services',
        ]);

        return $pkg;
    }

    /**
     * The packages upgrade routine.
     *
     * @return void
     */
    public function upgrade()
    {
        parent::upgrade();
    }

    /**
     * The packages uninstall routine.
     *
     * @return void
     */
    public function uninstall()
    {
        parent::uninstall();
    }
}
