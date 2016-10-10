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
     * Package class autoloader registrations
     * The package install helper class, included with this boilerplate,
     * is activated by default.
     *
     * @see https://goo.gl/4wyRtH
     * @var array
     */
    protected $pkgAutoloaderRegistries = [
        //'src/MyVendor/Statistics' => '\MyVendor\ConcreteStatistics'
    ];

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
        // Register your concrete5 service providers here
        'Concrete\Package\SeoUpdateServiceNotifier\Src\Providers\UpdateServiceNotifierServiceProvider',
    ];

    /**
     * How often should we allow pinging?
     *
     * @var integer
     */
    protected $rate_limit_ttl = 43200; // Every 12 hours

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

        // Plugin the event listeners.
        $this->addEventListeners();
    }

    /**
     * Get the list of update service URLs to ping.
     *
     * @return array
     */
    protected function getUpdateServiceUrls()
    {
        return [
            'https://requestb.in/1i2ftut1'
        ];
    }

    /**
     * Checks to see whether we should ping the update services.
     * Currently this only checks that we are not above our rate limit.
     *
     * @return boolean
     */
    protected function canPing()
    {
        $cache = Core::make('cache/expensive');
        $item = $cache->getItem('seo.update.notifier.expiry');

        if ($item->isMiss()) {
            $item->set(time() + $this->rate_limit_ttl, $this->rate_limit_ttl);

            return true;
        }

        return false;
    }

    /**
     * Notifys update services that a page has been added / updated
     * and should be (re)indexed.
     *
     * @param  \Page  $page [description]
     * @return boolean
     */
    protected function pingPage(\Page $page)
    {
        if ($this->canPing()) {
            $notifier = \Core::make('seo.update.notifier');

            return $notifier->ping(
                $this->getUpdateServiceUrls(),
                \Config::get('concrete.site'),
                \View::url('/'),
                \View::url($page->getCollectionPath())
            );
        }
    }

    /**
     * Wires the page event listeners to the update service
     * notification service.
     *
     * @return  void
     */
    public function addEventListeners()
    {
        \Events::addListener('on_page_version_approve', function($event) {
            $page_id = $event->getCollectionVersionObject()->getCollectionID();
            $page = \Page::getById($page_id);
            $this->pingPage($page);
        });
    }

    /**
     * The packages install routine.
     *
     * @return \Concrete\Core\Package\Package
     */
    public function install()
    {
        // Add your custom logic here that needs to be executed BEFORE package install.

        $pkg = parent::install();

        // Add your custom logic here that needs to be executed AFTER package install.

        return $pkg;
    }

    /**
     * The packages upgrade routine.
     *
     * @return void
     */
    public function upgrade()
    {
        // Add your custom logic here that needs to be executed BEFORE package install.

        parent::upgrade();

        // Add your custom logic here that needs to be executed AFTER package upgrade.
    }

    /**
     * The packages uninstall routine.
     *
     * @return void
     */
    public function uninstall()
    {
        // Add your custom logic here that needs to be executed BEFORE package uninstall.

        parent::uninstall();

        // Add your custom logic here that needs to be executed AFTER package uninstall.
    }
}
