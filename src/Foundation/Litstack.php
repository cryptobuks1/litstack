<?php

namespace Ignite\Foundation;

use Ignite\Application\Application;
use Ignite\Contracts\Foundation\Litstack as LitstackContract;
use Ignite\Support\Facades\Config;
use Ignite\Translation\Translator;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Traits\ForwardsCalls;

class Litstack implements LitstackContract
{
    use ForwardsCalls,
        Concerns\ManagesPaths;

    /**
     * Lit Application.
     *
     * @var \Ignite\Application\Application
     */
    protected $app;

    /**
     * Laravel application instance.
     *
     * @var LaravelApplication
     */
    protected $laravel;

    /**
     * Create Lit application.
     *
     * @param LaravelApplication $laravel
     */
    public function __construct(LaravelApplication $laravel)
    {
        $this->laravel = $laravel;

        $this->setBasePath(base_path('lit'));
        $this->setVendorPath(realpath(__DIR__.'/../../'));
        $this->registerLitstackContainerAliases();
    }

    /**
     * Determines if the application is translatable.
     *
     * @return bool
     */
    public function isAppTranslatable()
    {
        return count(config('translatable.locales')) > 1;
    }

    /**
     * Bind Lit Application instance when Lit is installed.
     *
     * @param  \Ignite\Application\Application $app
     * @return void
     */
    public function bindApp(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get Lit application.
     *
     * @return \Ignite\Application\Application $app
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * Get Lit url.
     *
     * @param  string $url
     * @return string
     */
    public function url(string $url)
    {
        return strip_slashes(
            '/'.config('lit.route_prefix').'/'.$url
        );
    }

    /**
     * Get Lit route by name.
     *
     * @param  string $name
     * @return string
     */
    public function route(string $name)
    {
        return route("lit.{$name}");
    }

    /**
     * Get translation for Lit application.
     *
     * @param  string      $key
     * @param  array       $replace
     * @param  string|null $locale
     * @return string
     */
    public function trans(string $key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return $this->laravel[Translator::class]->trans(
            $key, $replace, $locale
        );
    }

    /**
     * Get choice translation for Lit application.
     *
     * @param  string      $key
     * @param  int         $number
     * @param  array       $replace
     * @param  string|null $locale
     * @return string
     */
    public function transChoice(string $key = null, $number, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return $this->laravel[Translator::class]->choice(
            $key, $number, $replace, $locale
        );
    }

    /**
     * Get locale for Lit application.
     *
     * @return void
     */
    public function getLocale()
    {
        return $this->laravel[Translator::class]->getLocale();
    }

    /**
     * Get translation for Lit application.
     *
     * @param  string      $key
     * @param  array       $replace
     * @param  string|null $locale
     * @return string
     */
    public function __(string $key = null, $replace = [], $locale = null)
    {
        return $this->trans($key, $replace, $locale);
    }

    /**
     * Load config.
     *
     * @param  string             $key
     * @param  array              $params
     * @return ConfigHandler|null
     */
    public function config($key, ...$params)
    {
        return Config::get($key, ...$params);
    }

    /**
     * Gets the  authenticated Lit user.
     *
     * @return \Lit\Models\User|null
     */
    public function user()
    {
        return lit_user();
    }

    /**
     * Add css file to the application.
     *
     * @param  string $path
     * @return void
     */
    public function style($path)
    {
        if (! $this->app) {
            return;
        }

        $this->app->style($path);
    }

    /**
     * Add script to the application.
     *
     * @param  string $src
     * @return void
     */
    public function script($src)
    {
        if (! $this->app) {
            return;
        }

        $this->app->script($src);
    }

    /**
     * Get litstack application namesapce.
     *
     * @return string
     */
    public function getNamespace()
    {
        return 'Lit\\';
    }

    /**
     * Get litstack path.
     *
     * @return string
     */
    public function getPath()
    {
        return base_path('lit');
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerLitstackContainerAliases()
    {
        $groups = [
            'lit'            => [self::class, \Ignite\Contracts\Foundation\Litstack::class],
            'lit.app'        => [\Ignite\Application\Application::class],
            'lit.config'     => [\Ignite\Config\ConfigLoader::class],
            'lit.crud'       => [\Ignite\Crud\Crud::class],
            'lit.nav'        => [\Ignite\Application\Navigation\PresetFactory::class],
            'lit.router'     => [\Ignite\Routing\Router::class],
            'lit.translator' => [\Ignite\Translation\Translator::class],
            'lit.vue'        => [\Ignite\Vue\Vue::class, \Ignite\Contracts\Vue\Vue::class],
        ];

        foreach ($groups as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->laravel->alias($key, $alias);
            }
        }
    }

    /**
     * Checks if lit has been installed.
     *
     * @return bool
     */
    public function installed()
    {
        if (! realpath(lit_base_path())) {
            return false;
        }

        if (! config()->has('lit')) {
            return false;
        }

        if (! class_exists(\Lit\Kernel::class)) {
            return false;
        }

        return true;
    }

    /**
     * Determines if Litstack uses livewire.
     *
     * @return bool
     */
    public function usesLivewire()
    {
        return class_exists(\Livewire\Livewire::class);
    }

    /**
     * Forward call to Lit Application.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->app, $method, $parameters);
    }
}
