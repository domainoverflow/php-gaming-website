<?php
declare(strict_types=1);

namespace Gaming\ConnectFour\Port\Adapter\Persistence\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20160903094031 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('game');

        $table->addColumn('id', 'uuid_binary_ordered_time');
        $table->addColumn('version', 'integer');
        $table->addColumn('aggregate', 'json');

        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('game');
    }
}
