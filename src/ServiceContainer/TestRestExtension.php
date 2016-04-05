<?php

namespace DataSift\TestRestExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use DataSift\TestRestExtension\ServiceContainer\CacheDriver\CacheDriverFactory;
use DataSift\TestRestExtension\ServiceContainer\CacheDriver\MemcachedFactory;
use DataSift\TestRestExtension\ServiceContainer\DatabaseDriver\MySQLFactory;
use DataSift\TestRestExtension\ServiceContainer\DatabaseDriver\DatabaseDriverFactory;
use DataSift\TestRestExtension\ServiceContainer\DatabaseDriver\SQLiteFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TestRestExtension implements ExtensionInterface
{
    const CLIENT_ID = 'test_rest.client';
    const DB_DRIVER = 'test_rest.dbdriver';
    const CACHE_DRIVER = 'test_rest.cachedriver';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'test_rest';
    }

    /**
     * @var DatabaseDriverFactory[]
     */
    private $databaseDriverFactories = array();

    /**
     * @var CacheDriverFactory[]
     */
    private $cacheDriverFactories = array();

    public function __construct()
    {
        // Supported database drivers
        $this->registerDatabaseDriverFactory(new MySQLFactory());
        $this->registerDatabaseDriverFactory(new SQLiteFactory());

        // Supported cache drivers
        $this->registerCacheDriverFactory(new MemcachedFactory());
    }

    public function registerDatabaseDriverFactory(DatabaseDriverFactory $driverFactory)
    {
        $this->databaseDriverFactories[$driverFactory->getDriverName()] = $driverFactory;
    }

    public function registerCacheDriverFactory(CacheDriverFactory $driverFactory)
    {
        $this->cacheDriverFactories[$driverFactory->getDriverName()] = $driverFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->beforeNormalization()
            ->always()
            ->then(function ($v) {
                if (isset($v['database']['driver'])) {
                    $db = $v['database'];
                    unset($v['database']);

                    $v['database'][$db['driver']] = $db;
                    unset($v['database'][$db['driver']]['driver']);
                }
                if (isset($v['cache']['driver'])) {
                    $db = $v['cache'];
                    unset($v['cache']);

                    $v['cache'][$db['driver']] = $db;
                    unset($v['cache'][$db['driver']]['driver']);
                }
                return $v;
            })
            ->end()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('base_url')->defaultValue('http://localhost:8080/')->end()
            ->end()
        ;

        $databaseBuilder = $builder
            ->children()
            ->arrayNode('database')
        ;
        foreach ($this->databaseDriverFactories as $factory) {
            $factoryNode = $databaseBuilder->children()->arrayNode($factory->getDriverName())->canBeUnset();
            $factory->configure($factoryNode);
        }

        $cacheBuilder = $builder
            ->children()
            ->arrayNode('cache')
        ;
        foreach ($this->cacheDriverFactories as $factory) {
            $factoryNode = $cacheBuilder->children()->arrayNode($factory->getDriverName())->canBeUnset();
            $factory->configure($factoryNode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadClient($container, $config);
        $this->loadDatabase($container, $config);
        $this->loadCache($container, $config);
        $this->loadContextInitializer($container, $config);
    }

    private function loadClient(ContainerBuilder $container, $config)
    {
        $container->setDefinition(self::CLIENT_ID, new Definition('GuzzleHttp\Client', array($config)));
    }

    private function loadContextInitializer(ContainerBuilder $container, $config)
    {
        $definition = new Definition('DataSift\TestRestExtension\Context\Initializer\ApiClientAwareInitializer', array(
            new Reference(self::CLIENT_ID),
            $config
        ));
        $definition->addTag(ContextExtension::INITIALIZER_TAG);
        $container->setDefinition('test_rest.context_initializer', $definition);

        if ($container->hasDefinition(self::DB_DRIVER)) {
            $definition = new Definition('DataSift\TestRestExtension\Context\Initializer\DatabaseAwareInitializer', array(
                new Reference(self::DB_DRIVER),
                $config
            ));
            $definition->addTag(ContextExtension::INITIALIZER_TAG);
            $container->setDefinition('test_rest.db.context_initializer', $definition);
        }

        if ($container->hasDefinition(self::CACHE_DRIVER)) {
            $definition = new Definition('DataSift\TestRestExtension\Context\Initializer\CacheAwareInitializer', array(
                new Reference(self::CACHE_DRIVER),
                $config
            ));
            $definition->addTag(ContextExtension::INITIALIZER_TAG);
            $container->setDefinition('test_rest.cache.context_initializer', $definition);
        }
    }

    private function loadDatabase(ContainerBuilder $container, $config)
    {
        if (isset($config['database'])) {
            foreach ($config['database'] as $driver => $config) {
                $factory = $this->databaseDriverFactories[$driver];
                $container->setDefinition(self::DB_DRIVER, $factory->buildDriver($config));
                break;
            }
        }
    }

    private function loadCache(ContainerBuilder $container, $config)
    {
        if (isset($config['cache'])) {
            foreach ($config['cache'] as $driver => $config) {
                $factory = $this->cacheDriverFactories[$driver];
                $container->setDefinition(self::CACHE_DRIVER, $factory->buildDriver($config));
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}