<?php

/**
 * Plugin Name:     Book Store
 * Plugin URI:      https://www.veronalabs.com
 * Plugin Prefix:   WP_BS
 * Description:     Book Store Plugin Based on Rabbit Framework!
 * Author:          Ehsan Ravanbakhsh
 * Author URI:      https://www.linkedin.com/in/ehsan-ravanbakhsh/
 * Text Domain:     book-store
 * Domain Path:     /languages/
 * Version:         1.0
 */

use Rabbit\Application;
use Rabbit\Redirects\RedirectServiceProvider;
use Rabbit\Database\DatabaseServiceProvider;
use Rabbit\Logger\LoggerServiceProvider;
use Rabbit\Plugin;
use Rabbit\Redirects\AdminNotice;
use Rabbit\Templates\TemplatesServiceProvider;
use Rabbit\Utils\Singleton;
use League\Container\Container;
use WPBookStore\Actions;



if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
}

/**
 * Class WPBookStore
 * @package ExamplePluginInit
 */
class WPBookStore extends Singleton
{
    /**
     * @var Container
     */
    private $application;
    private $actionsInstance;

    /**
     * ExamplePluginInit constructor.
     */
    public function __construct()
    {
        $this->application = Application::get()->loadPlugin(__DIR__, __FILE__, 'config');
        $this->actionsInstance = Actions::getInstance();
        $this->init();
    }

    public function init()
    {
        try {

            /**
             * Load service providers
             */
            $this->application->addServiceProvider(RedirectServiceProvider::class);
            $this->application->addServiceProvider(DatabaseServiceProvider::class);
            $this->application->addServiceProvider(TemplatesServiceProvider::class);
            $this->application->addServiceProvider(LoggerServiceProvider::class);
           
            /**
             * Activation hooks
             */
            $this->application->onActivation(function () {
                $this->actionsInstance->generateBookTable();
            });

            /**
             * Deactivation hooks
             */
            $this->application->onDeactivation(function () {
                // Clear events, cache or something else
            });

            $this->application->boot(function (Plugin $plugin) {
                // this method not working
                $plugin->loadPluginTextDomain();
                // add new textdomain
                load_plugin_textdomain($this->application->getHeader('text_domain'), false, dirname(plugin_basename(__FILE__)) . $this->application->getHeader( 'domain_path' ));

                global $textDomain;
                $textDomain = $this->application->getHeader('text_domain');

                $this->actionsInstance->addMainAdminMenu(__('book store',$textDomain), 'book-store');
                $this->actionsInstance->generatePostType();
                $this->actionsInstance->addMetaBox();
                $this->actionsInstance->saveMetaBoxData();
            });
        } catch (Exception $e) {
            /**
             * Print the exception message to admin notice area
             */
            add_action('admin_notices', function () use ($e) {
                AdminNotice::permanent(['type' => 'error', 'message' => $e->getMessage()]);
            });

            /**
             * Log the exception to file
             */
            add_action('init', function () use ($e) {
                if ($this->application->has('logger')) {
                    $this->application->get('logger')->warning($e->getMessage());
                }
            });
        }
    }

    /**
     * @return Container
     */
    public function getApplication()
    {
        return $this->application;
    }
}

/**
 * Returns the main instance of WPBookStore.
 *
 * @return WPBookStore
 */
function wpBookStore()
{
    return WPBookStore::get();
}

wpBookStore();
