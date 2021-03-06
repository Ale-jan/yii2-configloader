<?php
namespace alejan\yii2confload;

class Config
{
    /**
     * Whether to check for local configuration overrides
     * @var bool|null The default is `null`, which will check `ENABLE_LOCALCONF` env var.
     */
    private $local;
    
    /**
     * The directory where configuration files is located 
     * @var string the directory path
     */
    private $directory;

    /**
     * The list of common configuration files name
     * @var array common file names
     */
    private $commonConfigFiles = ['main'];
    
    /**
     * Initialize the configuration directory path and the Yii environment.
     *
     * If an `.env` file is found in the configuration directory, it's loaded with `Dotenv`.
     * If a `YII_DEBUG` or `YII_ENV` environment variable is set, the Yii constants
     * are set accordingly. In debug mode the error reporting is also set to `E_ALL`.
     *
     * @param string $directory The configuration files directory path
     * @param array $commonConfigFiles List of names common configuration files
     * @param bool|null $local Whether to check for local configuration overrides
     * The default is `null`, which will check `ENABLE_LOCALCONF` env var.
     * @param bool $initEnv whether to initialize the Yii environment. Default is `true`.
     */
    public function __construct($directory, array $commonConfigFiles = [], $local = null, $initEnv = true)
    {
        $this->directory = $directory;
        $this->local = $local;
        $this->commonConfigFiles = array_merge($this->commonConfigFiles, $commonConfigFiles);

        if ($initEnv) {
            $this->initEnv($directory);
        }
    }

    /**
     * Magic method for build configuration for different app parts
     * 
     * @param type $appPart
     * You can get configuration for frontend, backend or console part of app
     *
     * @return array the app configuration array
     * 
     * @throws \Exception
     */
    public function __get($appPart)
    {
        return $this->loadConfig($appPart);
    }

    /**
     * Build configuration for different app parts
     * @param string $appPart 
     * @param array $config additional configuration to merge into the result
     * 
     * @return array the app part configuration array
     */
    public function loadConfig($appPart, array $config = [])
    {
        $files = [];
        
        if ($this->local === null) {
            $this->local = self::env('ENABLE_LOCALCONF', false);
        }
        
        $env = defined('YII_ENV') ? YII_ENV : 'dev';
        
        $configsNames = $this->commonConfigFiles;
        $configsNames[] = $appPart;
        $configsNames[] = sprintf('%s_%s', $appPart, $env);
        if ($this->local) {
            $configsNames[] = sprintf('local_%s', $appPart);
            $configsNames[] = sprintf('local_%s_%s', $appPart, $env);
        }
        
        foreach ($configsNames as $name) {
            if(!is_null($file = $this->getConfigFilePath($name, $name == $appPart))) {
                $files[] = $file;
            }
        }

        return $this->mergeFiles($files, $config);
    }
    
    /**
     * Init the Yii environment from environment variables.
     *
     * If $directory is passed and contains a `.env` file, that file is loaded
     * with `Dotenv` first.
     *
     * @param string|null $directory The directory to check for an `.env` file
     * The default is `null`, it means that will be check directory which set in constructor
     */
    public function initEnv($directory = null)
    {
        $directory = is_null($directory) ? $this->directory : $directory;
        
        if (file_exists($directory . DIRECTORY_SEPARATOR . '.env')) {
            \Dotenv::load($directory);
        }

        if (!defined('YII_DEBUG') && getenv('YII_DEBUG') !== false) {
            define('YII_DEBUG', (bool)getenv('YII_DEBUG'));
        }
        
        if (defined('YII_DEBUG') && YII_DEBUG) {
            error_reporting(E_ALL);
        }
        
        if (getenv('YII_ENV') !== false) {
            define('YII_ENV', getenv('YII_ENV'));
        }
    }

    /**
     * Get either an env var or a default value if the var is not set.
     *
     * @param string $name the name of the variable to get
     * @param mixed $default the default value to return if variable is not set. Default is `null`.
     * @param bool $required whether the var must be set. $default is ignored in this case. Default is `false`.
     * 
     * @return mixed the content of the environment variable or $default if not set
     */
    public static function env($name, $default = null, $required = false)
    {
        if ($required) {
            \Dotenv::required($name);
        }
        
        return getenv($name) === false ? $default : getenv($name);
    }
    
    /**
     * Load configuration files and merge them together.
     *
     * The files are loaded in the context of this class. So you can use `$this`
     * and `self` to access instance / class methods.
     *
     * @param array $files list of configuration files to load and merge.
     * Configuration from later files will override earlier values.
     * @param array $config additional configuration to merge into the result
     * 
     * @return array the resulting configuration array
     */
    protected function mergeFiles($files, array $config = [])
    {
        $configs = array_map(function ($file) { return require($file); }, $files);
        $configs[] = $config;
        
        return call_user_func_array('yii\helpers\ArrayHelper::merge', $configs);
    }

    /**
     * Gets the file path for a config file
     *
     * @param string $name Filename without extension
     * @param bool $required Whether the file must exist. Default is `true`.
     * 
     * @return string|null The full path to the config file or `null` if $required
     * is set to `false` and the file does not exist
     * 
     * @throws \Exception
     */
    protected function getConfigFilePath($name, $required = true)
    {
        $path = rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . sprintf('%s.php', $name);
        if (!file_exists($path)) {
            if ($required) {
                throw new \Exception("Config file '$path' does not exist");
            } else {
                return null;
            }
        }
        
        return $path;
    }
}
