<?php
/**
 * Demonstration Helper File.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
namespace Concrete\Package\SeoUpdateServiceNotifier\Src\Helpers;

defined('C5_EXECUTE') or die('Access Denied.');

class UpdateServiceNotifier
{
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

            $result = strpos($data['wrapper_data'][0], '200') > -1;

        } catch(\Exception $ex) {
            $result = false;
            $logger = \Core::make('Concrete\Core\Logging\Logger');
            $logger->addError('UpdateServiceNotifier: '.$ex->getMessage(), [$ex]);
        }

        return $result;
    }
}
