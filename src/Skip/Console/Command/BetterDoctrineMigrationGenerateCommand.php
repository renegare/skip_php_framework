<?php

    namespace Skip\Console\Command;
    
    use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Skip\ServiceContainerInterface,
    Doctrine\DBAL\Migrations\MigrationException,
    Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;

    class BetterDoctrineMigrationGenerateCommand extends GenerateCommand
    {

        protected static $_custom_template = 
            '<?php

namespace <namespace>;

use Skip\AbstractDoctrineMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version<version> extends AbstractDoctrineMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $to_schema = clone $schema;

<up>

        $this->addSchemaChange( $schema, $to_schema );
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $to_schema = clone $schema;

<down>

        $this->addSchemaChange( $schema, $to_schema );
    }
}
';
        protected function generateMigration(Configuration $configuration, InputInterface $input, $version, $up = null, $down = null)
        {
            $placeHolders = array(
                '<namespace>',
                '<version>',
                '<up>',
                '<down>'
            );

            $replacements = array(
                $configuration->getMigrationsNamespace(),
                $version,
                $up ? "        " . implode("\n        ", explode("\n", $up)) : null,
                $down ? "        " . implode("\n        ", explode("\n", $down)) : null
            );
            $code = str_replace($placeHolders, $replacements, self::$_custom_template);
            $dir = $configuration->getMigrationsDirectory();
            $dir = $dir ? $dir : getcwd();
            $dir = rtrim($dir, '/');
            $path = $dir . '/Version' . $version . '.php';

            if (!file_exists($dir)) {
                throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
            }

            file_put_contents($path, $code);

            if ($editorCmd = $input->getOption('editor-cmd')) {
                shell_exec($editorCmd . ' ' . escapeshellarg($path));
            }

            return $path;
        }
    }
