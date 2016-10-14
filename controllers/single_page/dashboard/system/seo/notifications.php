<?php
namespace Concrete\Package\SeoUpdateServiceNotifier\Controller\SinglePage\Dashboard\System\Seo;

use Loader;
use Core;
use Concrete\Core\Page\Controller\DashboardPageController;

class Notifications extends DashboardPageController
{
    /**
     * Show the form.
     * 
     * @param  string $param
     * @return void
     */
    public function view($param = '')
    {
        // Fire up and / or set some helpers.
        $this->set('fh', Core::make('helper/form'));
        $this->set('interface', Loader::helper('concrete/ui'));
        $config = Core::make(\Concrete\Core\Config\Repository\Repository::class);

        // Show the successful save message.
        if ('saved' === $param) {
            $this->set('message', ('Notification settings saved.'));
        }
        
        // Set the page title.
        $this->set('pageTitle', t('Update Services'));

        // Set the current configuration
        $hosts = $config->get('concrete.seo.notify.hosts', ['http://rpc.pingomatic.com']);
        $this->set('enabled', $config->get('concrete.seo.notify.enabled', false));
        $this->set('hosts', implode(PHP_EOL, $hosts));
        $this->set('log', $config->get('concrete.seo.notify.log', false));
        $this->set('ttl', $config->get('concrete.seo.notify.ttl', 900));

        // Dashboard help text.
        Core::make('help/dashboard')->registerMessages([
            '/dashboard/system/seo/notifications' => 
            t('Notifying update services of new content helps improve your sites ranking and also helps new content rank faster.'),
        ]);
    }

    /**
     * Save the POST data.
     * 
     * @return void
     */
    public function save_notifications()
    {
        if ($this->isPost()) {
            // Get the data and split the host URLs.
            $data = $this->post('concrete')['seo']['notify'];
            $data['hosts'] = explode(PHP_EOL, $data['hosts']);

            // Validate token.
            if (! $this->token->validate('save_notifications')) {
                $this->error->add($this->token->getErrorMessage());
            }

            // Validate hosts.
            foreach ($data['hosts'] as $host) {
                if (! filter_var($host, FILTER_VALIDATE_URL)) {
                    $this->error->add(sprintf(
                        t('The host [%s] is invalid.'), 
                        htmlentities($host))
                    );
                }
            }

            if (! $this->error->has()) {
                // If checkboxes aren't set, set them as false.
                foreach (['enabled', 'log'] as $key) {
                    if (empty($data[$key])) {
                        $data[$key] = false;
                    }
                }

                // Save the configuration.
                $config = Core::make(\Concrete\Core\Config\Repository\Repository::class);
                $config->save('concrete.seo.notify.enabled', boolval($data['enabled']));
                $config->save('concrete.seo.notify.log', boolval($data['log']));
                $config->save('concrete.seo.notify.hosts', $data['hosts']);
                $config->save('concrete.seo.notify.ttl', intval($data['ttl']));

                $this->redirect('/dashboard/system/seo/notifications', 'saved');
            }
        }

        $this->view();
    }
}