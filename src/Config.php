<?php
namespace alejan\yii2confload;

class Config
{
    /**
     * @var bool|null $local whether to check for local configuration overrides.
     * The default is `null`, which will check `ENABLE_LOCALCONF` env var.
     */
    private $local;
    
    /**
     * @var string the configs directory
     */
    private $directory;

    /**
     * @var array common file names
     */
    private $commonConfigFiles = ['main'];
    
    /**
     * Initialize the app directory path and the Yii environment.
     *
     * If an `.env` file is found in the app directory, it's loaded with `Dotenv`.
     * If a `YII_DEBUG` or `YII_ENV` environment variable is set, the Yii constants
     * are set accordingly. In debug mode the error reporting is also set to `E_ALL`.
     *
     * @param string $directory the application base directory
     * @param array $commonConfigFiles List of names common config files
     * @param bool|null $local whether to check for local configuration overrides.
     * The default is `null`, which will check `ENABLE_LOCALCONF` env var.
     * @param bool $initEnv whether to initialize the Yii environment. Default is `true`.
     */
    public function __construct($directory, array $commonConfigFiles = [], $local = null, $initEnv = true)
    {
        $this->directory = $directory;
        $this->local = $local;
        $this->commonConfigFiles = array_merge($this->commonConfigFiles, $commonConfigFiles);

        if ($initEnv) {
            self::initEnv($directory);
        }
    }

    /**
     * Builds the web configuration.
     *
     * @return array the web configuration array
     * @throws \Exception
     */
    public function __get($configType)
    {
        return $this->getConfig($configType);
    }

    public function loadConfig($configType, array $config = [])
    {
        return $this->getConfig($configType, $config);
    }
    
    /**
     * Init the Yii environment from environment variables.
     *
     * If $directory is passed and contains a `.env` file, that file is loaded
     * with `Dotenv` first.
     *
     * @param string|null $directory the directory to check for an `.env` file
     */
    public static function initEnv($directory = null)
    {
        if ($directory !== null && file_exists($directory . DIRECTORY_SEPARATOR . '.env')) {
            \Dotenv::load($directory);
        }

        // Define main Yii environment variables
        if (getenv('YII_DEBUG') !== false) {
            define('YII_DEBUG', (bool)getenv('YII_DEBUG'));
            if (YII_DEBUG) {
                error_reporting(E_ALL);
            }
        }
        if (getenv('YII_ENV') !== false) {
            define('YII_ENV', getenv('YII_ENV'));
        }
    }

    /**
     * Get either an env var or a default value if the var is not set.
     *
     * @param string $name the name of the variable to get
     * @param mixed $default the default value to return if variable is not set.
     * Default is `null`.
     * @param bool $required whether the var must be set. $default is ignored in
     * this case. Default is `false`.
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
     * @param array $config additional configuration to merge into the result
     * Configuration from later files will override earlier values.
     * @return array the resulting configuration array
     */
    protected function mergeFiles($files, array $config = [])
    {
        $configs = array_map(function ($file) { return require($file); }, $files);
        $configs[] = $config;
        if (class_exists('yii\helpers\ArrayHelper', false)) {
            return call_user_func_array('yii\helpers\ArrayHelper::merge', $configs);
        } else {
            return call_user_func_array('array_merge', $configs);
        }
    }

    /**
     * Gets the filename for a config file
     *
     * @param string $name
     * @param bool $required whether the file must exist. Default is `true`.
     * @return string|null the full path to the config file or `null` if $required
     * is set to `false` and the file does not exist
     * @throws \Exception
     */
    protected function getConfigFile($name, $required = true)
    {
        $sep = DIRECTORY_SEPARATOR;
        $path = rtrim($this->directory, $sep) . $sep . $name;
        if (!file_exists($path)) {
            if ($required) {
                throw new \Exception("Config file '$path' does not exist");
            } else {
                return null;
            }
        }
        return $path;
    }
    
    protected function getConfig($configType, array $config = []) 
    {
        $files = [];
        
        if ($this->local === null) {
            $this->local = self::env('ENABLE_LOCALCONF', false);
        }
        
        $env = defined('YII_ENV') ? YII_ENV : 'dev';
        
        $configsNames = $this->commonConfigFiles;
        $configsNames[] = $configType;
        $configsNames[] = sprintf('%s_%s', $configType, $env);
        if ($this->local) {
            $configsNames[] = sprintf('local_%s', $configType);
            $configsNames[] = sprintf('local_%s_%s', $configType, $env);
        }
        
        foreach ($configsNames as $name) {
            if(!is_null($file = $this->getConfigFile(sprintf('%s_%s.php', $configType, $env), $name == $configType))) {
                $files[] = $file;
            }
        }

        return $this->mergeFiles($files, $config);
    }
}
