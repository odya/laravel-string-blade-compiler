<?php 
namespace Digivo\StringBladeCompiler;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;

class StringBladeCompiler extends BladeCompiler implements CompilerInterface
{
    /** @var \Illuminate\Config\Repository */
    protected $config;

    public function __construct($filesystem, $cache_path, $config, $app)
    {
        // Get Current Blade Instance
        $blade = app('view')->getEngineResolver()->resolve('blade')->getCompiler();

        parent::__construct($filesystem, $cache_path);
        $this->rawTags = $blade->getRawTags();
        $this->contentTags = $blade->getContentTags();
        $this->escapedTags = $blade->getEscapedContentTags();
        $this->extensions = $blade->getExtensions();
        $this->customDirectives = $blade->getCustomDirectives();
        $this->config = $config;
    }

    /**
     * Compile the view at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path = null)
    {
        $contents = $this->compileString($path);

        if (!is_null($this->cachePath)) {
            $this->files->put($this->getCompiledPath($path), $contents);
        }
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path)
    {
        return $this->cachePath . '/' . md5($path);
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        if (!$this->config->get('string-blade-compiler.cache') || !$this->config->get('string-blade-compiler.cache_time') || !is_int($this->config->get('string-blade-compiler.cache_time'))) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if (!$this->cachePath || !$this->files->exists($compiled)) {
            return true;
        }

        return $this->files->lastModified($compiled) >= strtotime('-'.$this->config->get('string-blade-compiler.cache_time').' minutes');
    }
}
