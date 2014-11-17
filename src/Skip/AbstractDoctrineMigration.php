<?php
    
    namespace Skip;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema,
        SqlFormatter;

    abstract class AbstractDoctrineMigration extends AbstractMigration{

        public function addSchemaChange( Schema $from_schema, $to_schema ) {

            $sql = $from_schema->getMigrateToSql( $to_schema, $this->connection->getDatabasePlatform() );
            foreach( $sql as $key => $part ) {
                $sql[$key] = SqlFormatter::format( $part, false );
            }
            $this->addSql( $sql );

        }
    }