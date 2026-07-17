<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Koordinatės (signed, degrees WGS84) saugomos kaip NUMERIC(10,7) vietoj DOUBLE PRECISION.
 */
final class Version20260717000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'telemetry_record latitude/longitude: DOUBLE PRECISION -> NUMERIC(10,7)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telemetry_record ALTER latitude TYPE NUMERIC(10, 7)');
        $this->addSql('ALTER TABLE telemetry_record ALTER longitude TYPE NUMERIC(10, 7)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telemetry_record ALTER latitude TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE telemetry_record ALTER longitude TYPE DOUBLE PRECISION');
    }
}
