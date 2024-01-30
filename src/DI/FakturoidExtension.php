<?php

namespace Fakturoid\Nette\DI;

use Fakturoid\Auth\AuthProvider;
use Fakturoid\Dispatcher;
use Fakturoid\DispatcherInterface;
use Fakturoid\Provider\EventProvider;
use Fakturoid\Provider\ExpenseProvider;
use Fakturoid\Provider\GeneratorProvider;
use Fakturoid\Provider\InboxFileProvider;
use Fakturoid\Provider\InventoryItemProvider;
use Fakturoid\Provider\InvoiceProvider;
use Fakturoid\Provider\SettingProvider;
use Fakturoid\Provider\SubjectProvider;
use Fakturoid\Provider\TodoProvider;
use LogicException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Psr\Http\Client\ClientInterface;
use stdClass;

class FakturoidExtension extends CompilerExtension
{
    private const PROVIDER_MAPS = [
        'event' => EventProvider::class,
        'expense' => ExpenseProvider::class,
        'generator' => GeneratorProvider::class,
        'inboxFile' => InboxFileProvider::class,
        'inventoryItem' => InventoryItemProvider::class,
        'invoice' => InvoiceProvider::class,
        'setting' => SettingProvider::class,
        'subject' => SubjectProvider::class,
        'todo' => TodoProvider::class,
    ];

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'clientId' => Expect::string()->required(),
            'clientSecret' => Expect::string()->required(),
            'userAgent' => Expect::string()->required(),
            'accountSlug' => Expect::anyOf(Expect::string(), Expect::null()),
            'redirectUri' => Expect::anyOf(Expect::string(), Expect::null()),
            'providers' => Expect::anyOf(Expect::listOf('string'), Expect::string()->castTo('array'))->default(
                array_keys(self::PROVIDER_MAPS)
            )
                ->assert(
                    static fn(array $providers): bool => array_diff($providers, array_keys(self::PROVIDER_MAPS)) === [],
                    'Choose anything from list "' . implode('","', array_keys(self::PROVIDER_MAPS)) . '".'
                )
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        /** @var stdClass $config */
        $config = $this->getConfig();
        $httpClient = $builder->getByType(ClientInterface::class);
        if ($httpClient === null) {
            throw new LogicException(ClientInterface::class . ' not found');
        }

        $authProvider = $builder->addDefinition($this->prefix('authProvider'))
            ->setFactory(AuthProvider::class, [
                'clientId' => $config->clientId,
                'clientSecret' => $config->clientSecret,
                'redirectUri' => $config->redirectUri,
                'client' => $builder->getDefinition($httpClient)
            ]);

        $dispatcher = $builder->addDefinition($this->prefix('dispatcher'))
            ->setType(DispatcherInterface::class)
            ->setFactory(Dispatcher::class, [
                'userAgent' => $config->userAgent,
                'authorization' => $authProvider,
                'accountSlug' => $config->accountSlug,
                'client' => $builder->getDefinition($httpClient)
            ])->setAutowired(false);

        foreach ($config->providers as $provider) {
            if ($builder->hasDefinition($this->prefix($provider . 'Provider'))) {
                continue;
            }
            $builder->addDefinition($this->prefix($provider . 'Provider'))
                ->setFactory(self::PROVIDER_MAPS[$provider], [
                    'dispatcher' => $dispatcher
                ]);
        }
    }
}
