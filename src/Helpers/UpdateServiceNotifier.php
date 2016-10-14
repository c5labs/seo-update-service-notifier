<?php
/**
 * Update Service Notifier.
 * Where the 'pinging' magic happens.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
namespace Concrete\Package\SeoUpdateServiceNotifier\Src\Helpers;

use Log;
use Config;
use Core;
use Events;
use Page;
use View;
use Exception;

defined('C5_EXECUTE') or die('Access Denied.');

class UpdateServiceNotifier
{
     /**
      * Get the current configuration.
      * 
      * @return array
      */
    protected function getConfiguration()
    {
        return Config::get('concrete.seo.notify', []);
    }

    /**
     * Check that the current configuration is valid.
     * 
     * @return boolean 
     */
    protected function hasValidConfiguration()
    {
        $config = $this->getConfiguration();

        if (! isset($config['hosts']) || ! is_array($config['hosts']) || 0 === count($config['hosts']) ) {
            return false;
        }

        foreach ($config['hosts'] as $host) {
            if (! filter_var($host, FILTER_VALIDATE_URL)) {
                return false;
            }
        }

        if (! array_key_exists('log', $config) || ! array_key_exists('enabled', $config)) {
            return false;
        }

        if (! array_key_exists('ttl', $config) || ! is_int($config['ttl'])) {
            return false;
        }

        return true;
    }

    /**
     * Should we log requests & responses.
     * 
     * @return boolean
     */
    public function shouldLog()
    {
        return $this->getConfiguration()['log'];
    }

    /**
     * Wires the page event listeners to the update service
     * notification service.
     *
     * @return  void
     */
    public function listen()
    {
        if ($this->hasValidConfiguration()) {
            Events::addListener('on_page_version_approve', function($event) {
                $page_id = $event->getCollectionVersionObject()->getCollectionID();
                $page = Page::getById($page_id);
                $this->pingPage($page);
            });
        }
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
        $rate_limit_ttl = $this->getConfiguration()['ttl'];

        if ($item->isMiss()) {
            $item->set(time() + $rate_limit_ttl, $rate_limit_ttl);

            return true;
        }

        return false;
    }

    /**
     * Notifys update services that a page has been added / updated
     * and should be (re)indexed.
     *
     * @param  Page  $page [description]
     * @return boolean
     */
    protected function pingPage(Page $page)
    {
        if ($this->canPing()) {
            return $this->ping(
                $this->getConfiguration()['hosts'],
                Config::get('concrete.site', 'A concrete5 site'),
                View::url('/'),
                View::url($page->getCollectionPath())
            );
        }
    }

    /**
     * Ping an update notification service endpoint.
     *
     * @param  string $url
     * @param  string $site_name
     * @param  string $site_url
     * @param  string $content_url
     * @param  string $rss_url
     * @return boolean
     */
    public function ping($url, $site_name, $site_url, $content_url = null, $rss_url = null)
    {
        $result = true;

        $data = [
            'methodCall' => [
                'methodName' => 'weblogUpdates.ping',
                'params' => [
                    ['param' => ['value' => ['string' => $site_name]]],
                    ['param' => ['value' => ['string' => $site_url]]],
                ],
            ]
        ];

        // Add optional fields to the request data array.
        foreach (['content_url', 'rss_url'] as $key) {
            $data['methodCall']['methodName'] = 'weblogUpdates.extendedPing';

            if (! empty($$key)) {
                $data['methodCall']['params'][] = [
                    ['param' => ['value' => ['string' => $$key]]],
                ];
            }
        }

        // Ping the services
        foreach ((array) $url as $url) {
            $result = $this->xmlPostRequest($url, $data) ? $result : false;
        }

        return $result;
    }

    /**
     * Send an XML formatted POST request.
     *
     * @param  string $url
     * @param  array  $data
     * @return boolean
     */
    protected function xmlPostRequest($url, $data = [])
    {
        $result = false;

        // Create the XML request body via our custom
        // DOMDocument implementation.
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->fillFromArray($data);
        $body = $dom->saveXML();

        // Log the request.
        if ($this->shouldLog()) {
            $msg = sprintf(t("Page approvded, sending ping request to %s:"), $url);
            Log::addEntry($msg."\r\n\r\n".htmlentities($body));
        }

        // Setup the request options.
        $context  = stream_context_create([
            'http' => [
                'method' => 'POST',
                'user_agent' => 'concrete5/'.APP_VERSION,
                'header' => [
                    'Content-Type: text/xml',
                    'Content-Length: '.strlen($body),
                ],
                'content' => $body,
            ]
        ]);

        try {
            // Make the request.
            $fp = fopen($url, 'r', false, $context);

            // Get the response.
            $response = stream_get_contents($fp);
            $data = stream_get_meta_data($fp);

            // Cleanup.
            fclose($fp);

            // Log the response.
            if ($this->shouldLog()) {
                $msg = sprintf(t("%s responded:"), $url);
                Log::addEntry($msg."\r\n\r\n".htmlentities($response));
            }

            $result = strpos($data['wrapper_data'][0], '200') > -1;

        } catch(Exception $ex) {
            $result = false;
            Log::addError('UpdateServiceNotifier: '.$ex->getMessage(), [$ex]);
        }

        return $result;
    }
}
