<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command\Make;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'make:provider',
    description: 'Generate a service provider class',
)]
final class MakeProviderCommand extends AbstractMakeCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The provider class name (e.g. "Blog" or "BlogServiceProvider")');
        $this->addOption('domain', 'd', InputOption::VALUE_NONE, 'Generate a domain provider with entity type registration boilerplate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $isDomain = $input->getOption('domain');

        // Normalize: "Blog" → "BlogServiceProvider", "BlogServiceProvider" → as-is.
        $className = $this->toPascalCase($name);
        if (!str_ends_with($className, 'ServiceProvider')) {
            $className .= 'ServiceProvider';
        }

        // Extract domain name from class: "BlogServiceProvider" → "Blog".
        $domain = str_replace('ServiceProvider', '', $className);

        if ($isDomain) {
            $entityTypeId = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $domain));
            $rendered = $this->renderStub('provider-domain', [
                'class' => $className,
                'domain' => $domain,
                'entity_namespace' => 'App\\Entity',
                'entity_class' => $domain,
                'entity_type_id' => $entityTypeId,
                'entity_label' => $domain,
            ]);
        } else {
            $rendered = $this->renderStub('provider', [
                'class' => $className,
            ]);
        }

        $output->write($rendered);

        return self::SUCCESS;
    }
}
